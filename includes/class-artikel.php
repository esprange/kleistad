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
 * @since 6.1.0
 */
abstract class Artikel extends Entity {

	const BTW = 0.21; // 21 procent.

	/**
	 * De klant.
	 *
	 * @var int $klant_id
	 */
	protected $klant_id;

	/**
	 * Bij artikelen kan aangegeven worden welk type order afgehandeld moet worden.
	 *
	 * @var string $artikel_type Bijvoorbeeld bij abonnementen het type start, overbrugging of regulier.
	 */
	public $artikel_type = '';

	/**
	 * Geef de naam van het artikel.
	 *
	 * @return string
	 */
	abstract public function artikel_naam();

	/**
	 * Betaal het artikel per ideal.
	 *
	 * @param  string $bericht Het bericht na succesvolle betaling.
	 * @return string|bool De redirect uri of het is fout gegaan.
	 */
	abstract public function betalen( $bericht );

	/**
	 * Aanroep vanuit betaling per ideal.
	 *
	 * @param array $parameters De parameters 0: workshop-id.
	 * @param float $bedrag     Het betaalde bedrag.
	 * @param bool  $betaald    Of er werkelijk betaald is.
	 */
	abstract public static function callback( $parameters, $bedrag, $betaald );

	/**
	 * Geef de code van het artikel
	 *
	 * @return string De referentie.
	 */
	abstract public function code();

	/**
	 * Dagelijks uit te voeren handelingen, in te vullen door het artikel.
	 */
	abstract public static function dagelijks();

	/**
	 * Email function
	 *
	 * @param string $type    Het soort email.
	 * @param string $bijlage De eventueel te versturen bijlage.
	 */
	abstract public function email( $type, $bijlage = '' );

	/**
	 * Bestelling
	 *
	 * @return array
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
	 * @param int   $id        Het id van de order.
	 * @param float $restant   Het eventueel te betalen bedrag bij annulering.
	 * @return string De url van de creditfactuur of lege string.
	 */
	final public function annuleer_order( $id, $restant = 0.0 ) {
		if ( ! $this->afzeggen() ) {
			return '';
		}
		$order = new \Kleistad\Order( $id );
		if ( $order->credit_id || $order->origineel_id ) {
			return '';  // De relatie id's zijn ingevuld dus er is al een credit factuur of dit is een creditering.
		}
		$credit_order               = new \Kleistad\Order();
		$credit_order->referentie   = $order->referentie;
		$credit_order->betaald      = $order->betaald;
		$credit_order->klant        = $order->klant;
		$credit_order->origineel_id = $order->id;
		$regels                     = $order->regels;
		foreach ( $regels as &$regel ) {
			$regel['artikel'] = 'annulering ' . $regel['artikel'];
			$regel['aantal']  = - $regel['aantal'];
		}
		if ( 0.0 < $restant ) {
			$prijs    = round( $restant / ( 1 + self::BTW ), 2 );
			$btw      = round( $restant - $prijs, 2 );
			$regels[] = [
				'artikel' => 'kosten i.v.m. annulering',
				'aantal'  => 1,
				'prijs'   => $prijs,
				'btw'     => $btw,
			];
		}

		// Nog te betalen is negatief als er meer betaald is dan het restant.
		$nog_te_betalen          = $restant - $credit_order->betaald;
		$credit_order->gesloten  = ( 0.01 >= abs( $nog_te_betalen ) );
		$credit_order->regels    = $regels;
		$credit_order->opmerking = 'Vanwege annulering';
		$credit_order->historie  = 'order en credit factuur aangemaakt';
		$order->credit_id        = $credit_order->save();
		$order->betaald          = 0;
		$order->gesloten         = true;
		$order->historie         = 'geannuleerd, credit factuur ' . $credit_order->factuurnr() . ' aangemaakt';
		$order->save();
		return $this->maak_factuur( $credit_order, $nog_te_betalen );
	}

	/**
	 * Een bestelling aanmaken.
	 *
	 * @param float  $bedrag       Het betaalde bedrag.
	 * @param string $artikel_type De optionele parameter voor de factuur regels.
	 * @param string $opmerking    De optionele opmerking in de factuur.
	 * @return string De url van de factuur.
	 */
	final public function bestel_order( $bedrag = 0.0, $artikel_type = '', $opmerking = '' ) {
		$this->artikel_type = $artikel_type;
		$order              = new \Kleistad\Order();
		$order->betaald     = $bedrag;
		$order->regels      = $this->factuurregels();
		$order->historie    = 'order en factuur aangemaakt,  nieuwe status betaald is € ' . number_format_i18n( $bedrag, 2 );
		$order->klant       = $this->naw_klant();
		$order->opmerking   = $opmerking;
		$order->referentie  = $this->code();
		$order->save();
		return $this->maak_factuur( $order );
	}

	/**
	 * Een bestelling wijzigen ivm korting.
	 *
	 * @param int    $id        Het id van de order.
	 * @param float  $korting   De te geven korting.
	 * @param string $opmerking De optionele opmerking in de factuur.
	 * @return string De url van de factuur.
	 */
	final public function korting_order( $id, $korting, $opmerking = '' ) {
		$order            = new \Kleistad\Order( $id );
		$regels           = $order->regels;
		$prijs            = round( $korting / ( 1 + self::BTW ), 2 );
		$btw              = round( $korting - $prijs, 2 );
		$regels[]         = [
			'artikel' => 'korting',
			'aantal'  => 1,
			'prijs'   => - $prijs,
			'btw'     => - $btw,
		];
		$order->regels    = $regels;
		$order->klant     = $this->naw_klant();
		$order->historie  = 'Correctie factuur i.v.m. korting € ' . number_format_i18n( $korting, 2 );
		$order->opmerking = $opmerking;
		$order->save();
		return $this->maak_factuur( $order );
	}

	/**
	 * Een bestelling betalen.
	 *
	 * @param int   $id Het id van de order.
	 * @param float $bedrag Het betaalde bedrag.
	 */
	final public function ontvang_order( $id, $bedrag ) {
		$order           = new \Kleistad\Order( $id );
		$order->betaald += $bedrag;
		$order->historie = 'betaling bedrag € ' . number_format_i18n( $bedrag, 2 ) . ' nieuwe status betaald is € ' . number_format_i18n( $order->betaald, 2 );
		$order->save();
		$this->betaalactie( $bedrag );
	}

	/**
	 * Een bestelling wijzigen.
	 *
	 * @param int    $id        Het id van de order.
	 * @param string $opmerking De optionele opmerking in de factuur.
	 * @return string De url van de factuur.
	 */
	final public function wijzig_order( $id, $opmerking = '' ) {
		$order            = new \Kleistad\Order( $id );
		$order->regels    = $this->factuurregels();
		$order->klant     = $this->naw_klant();
		$order->historie  = 'Order gewijzigd';
		$order->opmerking = $opmerking;
		$order->save();
		return $this->maak_factuur( $order );
	}

	/**
	 * Maak een controle string aan.
	 *
	 * @since  6.1.0
	 *
	 * @param  int $order_id Het id van de order.
	 * @return string Hash string.
	 */
	final public function controle( $order_id = 0 ) {
		if ( ! $order_id ) {
			$order_id = \Kleistad\Order::zoek_order( $this->code() );
		}
		return hash( 'sha256', "KlEiStAd{$order_id}cOnTrOlE3812LE" );
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
	 * Voer een actie uit bij betaling, kan nader ingevuld worden.
	 *
	 * @since 6.1.0
	 *
	 * @param float $bedrag Het ontvangen bedrag.
	 */
	protected function betaalactie( $bedrag ) {
	}

	/**
	 * Bepaal het Kleistad artikel a.d.h.v. de referentie.
	 *
	 * @param string $referentie De artikel referentie.
	 * @return \Kleistad\Artikel Een van de kleistad Artikel objecten.
	 */
	public static function get_artikel( $referentie ) {
		$parameters = explode( '-', substr( $referentie, 1 ) );
		switch ( $referentie[0] ) {
			case 'A':
				return new \Kleistad\Abonnement( (int) $parameters[0] );
			case 'C':
				return new \Kleistad\Inschrijving( (int) $parameters[1], (int) $parameters[0] );
			case 'K':
				return new \Kleistad\Dagdelenkaart( (int) $parameters[0] );
			case 'S':
				return new \Kleistad\Saldo( (int) $parameters[0] );
			case 'W':
				return new \Kleistad\Workshop( (int) $parameters[0] );
			default:
				return null;
		}
	}

	/**
	 * De link die in een email als parameter meegegeven kan worden.
	 *
	 * @return string De html link.
	 */
	protected function betaal_link() {
		$url = add_query_arg(
			[
				'order' => \Kleistad\Order::zoek_order( $this->code() ),
				'hsh'   => $this->controle(),
				'art'   => $this->artikel_type,
			],
			home_url( '/kleistad_betaling' )
		);
		return "<a href=\"$url\" >Kleistad pagina</a>";
	}

	/**
	 * Klant gegevens voor op de factuur, kan eventueel aangepast worden zoals bijvoorbeeld voor de contact van een workshop.
	 *
	 * @return array De naw gegevens.
	 */
	protected function naw_klant() {
		$klant = get_userdata( $this->klant_id );
		return [
			'naam'  => "{$klant->first_name}  {$klant->last_name}",
			'adres' => "{$klant->straat} {$klant->huisnr}\n{$klant->pcode} {$klant->plaats}",
		];
	}

	/**
	 * Te betalen bedrag, kan eventueel aangepast worden zoals bijvoorbeeld voor de inschrijfkosten van de cursus.
	 *
	 * @return float
	 */
	protected function te_betalen() {
		$order_id = \Kleistad\Order::zoek_order( $this->code() );
		$order    = new \Kleistad\Order( $order_id );
		return $order->bruto() - $order->betaald;
	}

	/**
	 * Maak een factuur aan.
	 *
	 * @param \Kleistad\Order $order De order.
	 * @param float           $nog_te_betalen Het nog te betalen bedrag ingeval van een credit factuur.
	 * @return string Het pad naar de factuur.
	 */
	private function maak_factuur( $order, $nog_te_betalen = null ) {
		$options = \Kleistad\Kleistad::get_options();
		if ( ! $options['factureren'] ) {
			return '';
		}
		$factuur = new \Kleistad\Factuur();
		return $factuur->run(
			$order->factuurnr(),
			$order->klant,
			$order->referentie,
			$order->regels,
			$order->betaald,
			$order->opmerking,
			boolval( $order->mutatie_datum ),
			$nog_te_betalen
		);
	}

}
