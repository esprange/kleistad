<?php
/**
 * De definitie van de artikel class
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Artikel class.
 *
 * @property string code
 * @property int    datum
 *
 * @since 6.1.0
 */
abstract class Artikel extends Entity {

	/**
	 * Het betaal object.
	 *
	 * @var Betalen $betalen
	 */
	protected $betalen;

	/**
	 * De klant.
	 *
	 * @var int $klant_id
	 */
	public $klant_id;

	/**
	 * De betaal link.
	 *
	 * @var string $betaal_link De url om te betalen.
	 */
	public $betaal_link = '';

	/**
	 * Bij artikelen kan aangegeven worden welk type order afgehandeld moet worden.
	 *
	 * @var string $artikel_type Bijvoorbeeld bij abonnementen het type start, overbrugging of regulier.
	 */
	public $artikel_type = '';

	/**
	 * De artikelen.
	 *
	 * @var array $artikelen De parameters behorende bij de artikelen.
	 */
	public static $artikelen = [];

	/**
	 * Geef de naam van het artikel.
	 *
	 * @return string
	 */
	abstract public function artikel_naam();

	/**
	 * Betaal het artikel per ideal.
	 *
	 * @param  string $bericht    Het bericht na succesvolle betaling.
	 * @param  string $referentie De referentie van het artikel.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect uri of het is fout gegaan.
	 */
	abstract public function ideal( $bericht, $referentie, $openstaand = null );

	/**
	 * Aanroep vanuit betaling per ideal of sepa incasso.
	 *
	 * @param int    $order_id      De order id.
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Een betaling per bank, ideal of incasso.
	 * @param string $transactie_id De betalings id.
	 */
	abstract public function verwerk_betaling( $order_id, $bedrag, $betaald, $type, $transactie_id = '' );

	/**
	 * Geef de code van het artikel
	 *
	 * @return string De referentie.
	 */
	abstract public function referentie();

	/**
	 * Dagelijks uit te voeren handelingen, in te vullen door het artikel.
	 */
	abstract public static function dagelijks();

	/**
	 * Email function
	 *
	 * @param string $type    Het soort email.
	 * @param string $factuur De eventueel te versturen factuur.
	 */
	abstract public function email( $type, $factuur = '' );

	/**
	 * Bestelling
	 *
	 * @return array|Orderregel Een array van orderregels of maar één regel.
	 */
	abstract protected function factuurregels();

	/**
	 * Geef de status van het artikel als een tekst terug.
	 *
	 * @param  boolean $uitgebreid Uitgebreide tekst of korte tekst.
	 * @return string De status tekst.
	 */
	abstract public function status( $uitgebreid = false );

	/**
	 * Een bestelling annuleren.
	 *
	 * @param int    $id        Het id van de order.
	 * @param float  $restant   Het te betalen bedrag bij annulering.
	 * @param string $opmerking De opmerkingstekst in de factuur.
	 * @return string De url van de creditfactuur of lege string.
	 */
	final public function annuleer_order( $id, $restant, $opmerking ) {
		if ( ! $this->afzeggen() ) {
			return '';
		}
		$order = new Order( $id );
		if ( $order->credit_id || $order->origineel_id ) {
			return '';  // De relatie id's zijn ingevuld dus er is al een credit factuur of dit is een creditering.
		}
		$credit_order               = new Order();
		$credit_order->referentie   = $order->referentie;
		$credit_order->betaald      = $order->betaald;
		$credit_order->klant        = $order->klant;
		$credit_order->origineel_id = $order->id;
		$credit_order->verval_datum = strtotime( 'tomorrow' );

		foreach ( $order->orderregels as $orderregel ) {
			$credit_order->orderregels->toevoegen( new Orderregel( "annulering {$orderregel->artikel}", - $orderregel->aantal, $orderregel->prijs, $orderregel->btw ) );
		}
		if ( 0.0 < $restant ) {
			$credit_order->orderregels->toevoegen( new Orderregel( 'kosten i.v.m. annulering', 1, $restant ) );
		}
		$credit_order->opmerking     = $opmerking;
		$credit_order->historie      = 'order en credit factuur aangemaakt';
		$credit_order->transactie_id = $order->transactie_id;
		$order->credit_id            = $credit_order->save();
		$order->betaald              = 0;
		$order->gesloten             = true;
		$order->historie             = 'geannuleerd, credit factuur ' . $credit_order->factuurnummer() . ' aangemaakt';
		$order->save();
		$this->maak_link( $order->credit_id );
		$this->betaalactie( $credit_order->betaald );
		return $this->maak_factuur( $credit_order, 'credit' );
	}

	/**
	 * Een bestelling aanmaken.
	 *
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param int    $verval_datum  De datum waarop de factuur vervalt.
	 * @param string $opmerking     De optionele opmerking in de factuur.
	 * @param string $transactie_id De betalings id.
	 * @param bool   $factuur       Of er een factuur aangemaakt moet worden.
	 * @return string De url van de factuur.
	 */
	final public function bestel_order( $bedrag, $verval_datum, $opmerking = '', $transactie_id = '', $factuur = true ) {
		$order                = new Order();
		$order->betaald       = $bedrag;
		$order->historie      = $factuur ? 'order en factuur aangemaakt,  nieuwe status betaald is € ' . number_format_i18n( $bedrag, 2 ) : 'order aangemaakt';
		$order->klant         = $this->naw_klant();
		$order->opmerking     = $opmerking;
		$order->referentie    = $this->referentie();
		$order->transactie_id = $transactie_id;
		$order->verval_datum  = $verval_datum;
		$order->orderregels->toevoegen( $this->factuurregels() );
		$order->save();
		$this->maak_link( $order->id );
		$this->betaalactie( $order->betaald );
		return $factuur ? $this->maak_factuur( $order, '' ) : '';
	}

	/**
	 * Een bestelling wijzigen ivm korting.
	 *
	 * @param int    $id        Het id van de order.
	 * @param float  $korting   De te geven korting.
	 * @param string $opmerking De opmerking in de factuur.
	 * @return bool|string De url van de factuur of fout.
	 */
	final public function korting_order( $id, $korting, $opmerking ) {
		$order = new Order( $id );
		if ( $order->is_geblokkeerd() ) {
			return false;
		}
		$order->orderregels->toevoegen( new Orderregel( Orderregel::KORTING, 1, - $korting ) );
		$order->historie  = 'Correctie factuur i.v.m. korting € ' . number_format_i18n( $korting, 2 );
		$order->opmerking = $opmerking;
		$order->save();
		$this->maak_link( $order->id );
		$this->betaalactie( $order->betaald );
		return $this->maak_factuur( $order, 'correctie' );
	}

	/**
	 * Een bestelling betalen.
	 *
	 * @param int    $id            Het id van de order.
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param string $transactie_id De betalings id.
	 * @param bool   $factuur       Of er wel / niet een factuur aangemaakt moet worden.
	 */
	final public function ontvang_order( $id, $bedrag, $transactie_id, $factuur = false ) {
		$order           = new Order( $id );
		$order->betaald += $bedrag;
		if ( 0 <= $bedrag ) {
			$order->historie = 'betaling bedrag € ' . number_format_i18n( $bedrag, 2 ) . ' nieuwe status betaald is € ' . number_format_i18n( $order->betaald, 2 );
		} else {
			$order->historie = 'stornering bedrag € ' . number_format_i18n( - $bedrag, 2 ) . ' nieuwe status betaald is € ' . number_format_i18n( $order->betaald, 2 );
		}
		$order->transactie_id = $transactie_id;
		$order->save();
		$this->betaalactie( $order->betaald );
		return ( $factuur ) ? $this->maak_factuur( $order, '' ) : '';
	}

	/**
	 * Een bestelling wijzigen.
	 *
	 * @param int    $id        Het id van de order.
	 * @param string $opmerking De optionele opmerking in de factuur.
	 * @return bool|string De url van de factuur of false.
	 */
	final public function wijzig_order( $id, $opmerking = '' ) {
		$originele_order = new Order( $id );
		$order           = clone $originele_order;
		if ( $order->is_geblokkeerd() ) {
			return false;
		}
		$order->orderregels->vervangen( $this->factuurregels() );
		$order->klant      = $this->naw_klant();
		$order->referentie = $this->referentie();
		if ( $order == $originele_order ) { // phpcs:ignore
			return ''; // Als er niets gewijzigd is aan de order heeft het geen zin om een nieuwe factuur aan te maken.
		}

		$order->historie  = 'Order gewijzigd';
		$order->opmerking = $opmerking;
		$order->save();
		$this->maak_link( $order->id );
		$this->betaalactie( $order->betaald );
		return $this->maak_factuur( $order, 'correctie' );
	}

	/**
	 * Maak een controle string aan.
	 *
	 * @since  6.1.0
	 *
	 * @return string Hash string.
	 */
	final public function controle() {
		return hash( 'sha256', "KlEiStAd{$this->code}cOnTrOlE3812LE" );
	}

	/**
	 * Zeg het artikel af, kan nader ingevuld worden.
	 *
	 * @since 6.1.0
	 *
	 * @return bool
	 */
	public function afzeggen() {
		return true;
	}

	/**
	 * Controleer of het artikel nog geleverd kan worden.
	 *
	 * @since 6.6.1
	 *
	 * @return string Als leeg dan beschikbaar, anders bevat foutmelding.
	 */
	public function beschikbaarcontrole() {
		return '';
	}

	/**
	 * Klant gegevens voor op de factuur, kan eventueel aangepast worden zoals bijvoorbeeld voor de contact van een workshop.
	 *
	 * @return array De naw gegevens.
	 */
	public function naw_klant() {
		$klant = get_userdata( $this->klant_id );
		return [
			'naam'  => "{$klant->first_name}  {$klant->last_name}",
			'adres' => "{$klant->straat} {$klant->huisnr}\n{$klant->pcode} {$klant->plaats}",
			'email' => "$klant->display_name <$klant->user_email>",
		];
	}

	/**
	 * Registreer het artikel
	 *
	 * @param string $key  De sleutelletter van het artikel.
	 * @param array  $args De parameters.
	 */
	public static function register( $key, $args ) {
		if ( array_key_exists( $key, self::$artikelen ) ) {
			return;
		}
		self::$artikelen[ $key ] = $args;
	}

	/**
	 * Bepaal het Kleistad artikel a.d.h.v. de referentie.
	 *
	 * @param string $referentie De artikel referentie.
	 * @return Artikel Een van de kleistad Artikel objecten.
	 */
	public static function get_artikel( $referentie ) {
		if ( ! empty( $referentie ) && array_key_exists( $referentie[0], self::$artikelen ) ) {
			$parameters = explode( '-', substr( $referentie, 1 ) );
			$class      = '\\' . __NAMESPACE__ . '\\' . self::$artikelen[ $referentie[0] ]['class'];
			if ( 1 === self::$artikelen[ $referentie[0] ]['pcount'] ) {
				$artikel               = new $class( (int) $parameters[0] );
				$artikel->artikel_type = $parameters[1] ?? $artikel->artikel_type;
			} else {
				$artikel               = new $class( (int) $parameters[0], (int) $parameters[1] );
				$artikel->artikel_type = $parameters[2] ?? $artikel->artikel_type;
			}
			return $artikel;
		}
		return null;
	}

	/**
	 * Voer een actie uit bij betaling, kan nader ingevuld worden.
	 *
	 * @since 6.1.0
	 *
	 * @param float $bedrag Het ontvangen bedrag.
	 */
	protected function betaalactie( $bedrag ) {
	}

	/**
	 * De link die in een email als parameter meegegeven kan worden.
	 *
	 * @param  int $order_id Het is van de order.
	 */
	protected function maak_link( $order_id ) {
		$url               = add_query_arg(
			[
				'order' => $order_id,
				'hsh'   => $this->controle(),
				'art'   => $this->artikel_type,
			],
			home_url( '/kleistad-betaling' )
		);
		$this->betaal_link = "<a href=\"$url\" >Kleistad pagina</a>";
	}

	/**
	 * Maak een factuur aan.
	 *
	 * @param Order  $order De order.
	 * @param string $type  Het type factuur.
	 * @return string Het pad naar de factuur.
	 */
	private function maak_factuur( $order, $type ) {
		$factuur = new Factuur();
		return $factuur->run( $order, $type );
	}

}
