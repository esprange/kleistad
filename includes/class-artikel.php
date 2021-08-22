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
abstract class Artikel {

	public const DEFINITIE = [
		'prefix' => '',
		'naam'   => '',
		'pcount' => 0,
	];

	/**
	 * De artikel data
	 *
	 * @access protected
	 * @var array $data welke de attributen van het artikel bevat.
	 */
	protected array $data = [];

	/**
	 * De klant.
	 *
	 * @var int $klant_id
	 */
	public int $klant_id;

	/**
	 * De betaal link.
	 *
	 * @var string $betaal_link De url om te betalen.
	 */
	public string $betaal_link = '';

	/**
	 * Bij artikelen kan aangegeven worden welk type order afgehandeld moet worden.
	 *
	 * @var string $artikel_type Bijvoorbeeld bij abonnementen het type start, overbrugging of regulier.
	 */
	public string $artikel_type = '';

	/**
	 * Het Betaling object
	 *
	 * @var object $betaling De betaling acties.
	 */
	public object $betaling;

	/**
	 * Geef de code van het artikel
	 *
	 * @return string De referentie.
	 */
	abstract public function geef_referentie(): string;

	/**
	 * Bestelling
	 *
	 * @return array|Orderregel Een array van orderregels of maar één regel.
	 */
	abstract protected function geef_factuurregels();

	/**
	 * Een bestelling annuleren.
	 *
	 * @param Order  $order   De order.
	 * @param float  $restant   Het te betalen bedrag bij annulering.
	 * @param string $opmerking De opmerkingstekst in de factuur.
	 * @return string|bool De url van de creditfactuur of false indien annulering niet mogelijk.
	 */
	final public function annuleer_order( Order $order, float $restant, string $opmerking ) {
		if ( $order->credit_id || $order->origineel_id ) {
			return false;  // De relatie id's zijn ingevuld dus er is al een credit factuur of dit is een creditering.
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
		$credit_order->transactie_id = $order->transactie_id;
		$order->credit_id            = $credit_order->save( 'Order en credit factuur aangemaakt' );
		$order->betaald              = 0;
		$order->save( sprintf( 'Geannuleerd, credit factuur %s aangemaakt', $credit_order->factuurnummer() ) );
		$this->betaal_link = $this->maak_link(
			[
				'order' => $order->credit_id,
				'art'   => $this->artikel_type,
			],
			'betaling'
		);
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
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	final public function bestel_order( float $bedrag, int $verval_datum, string $opmerking = '', string $transactie_id = '', bool $factuur = true ): string {
		$order                = new Order();
		$order->betaald       = $bedrag;
		$order->klant         = $this->naw_klant();
		$order->opmerking     = $opmerking;
		$order->referentie    = $this->geef_referentie();
		$order->transactie_id = $transactie_id;
		$order->verval_datum  = $verval_datum;
		$order->orderregels->toevoegen( $this->geef_factuurregels() );
		$order->save( $factuur ? sprintf( 'Order en factuur aangemaakt, nieuwe status betaald is € %01.2f', $bedrag ) : 'Order aangemaakt' );
		$this->betaal_link = $this->maak_link(
			[
				'order' => $order->id,
				'art'   => $this->artikel_type,
			],
			'betaling'
		);
		return $factuur ? $this->maak_factuur( $order, '' ) : '';
	}

	/**
	 * Een bestelling wijzigen ivm korting.
	 *
	 * @param Order  $order     De order.
	 * @param float  $korting   De te geven korting.
	 * @param string $opmerking De opmerking in de factuur.
	 * @return bool|string De url van de factuur of fout.
	 */
	final public function korting_order( Order $order, float $korting, string $opmerking ) {
		if ( $order->is_geblokkeerd() ) {
			return false;
		}
		$order->orderregels->toevoegen( new Orderregel( Orderregel::KORTING, 1, - $korting ) );
		$order->opmerking = $opmerking;
		$order->save( sprintf( 'Correctie factuur i.v.m. korting € %01.2f', $korting ) );
		$this->betaal_link = $this->maak_link(
			[
				'order' => $order->id,
				'art'   => $this->artikel_type,
			],
			'betaling'
		);
		return $this->maak_factuur( $order, 'correctie' );
	}

	/**
	 * Een bestelling betalen.
	 *
	 * @param Order  $order         De order.
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param string $transactie_id De betalings id.
	 * @param bool   $factuur       Of er wel / niet een factuur aangemaakt moet worden.
	 * @return string Pad naar de factuur of leeg.
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	final public function ontvang_order( Order $order, float $bedrag, string $transactie_id, bool $factuur = false ): string {
		$order->betaald      += $bedrag;
		$order->transactie_id = $transactie_id;
		$order->save( sprintf( '%s bedrag € %01.2f nieuwe status betaald is € %01.2f', 0 <= $bedrag ? 'Betaling' : 'Stornering', abs( $bedrag ), $order->betaald ) );
		return ( $factuur ) ? $this->maak_factuur( $order, '' ) : '';
	}

	/**
	 * Een bestelling wijzigen.
	 *
	 * @param Order  $originele_order De order.
	 * @param string $opmerking       De optionele opmerking in de factuur.
	 * @return bool|string De url van de factuur of false.
	 * @noinspection PhpNonStrictObjectEqualityInspection
	 */
	final public function wijzig_order( Order $originele_order, string $opmerking = '' ) {
		if ( $originele_order->is_geblokkeerd() ) {
			return false;
		}
		$order = clone $originele_order;
		$order->orderregels->vervangen( $this->geef_factuurregels() );
		$order->klant      = $this->naw_klant();
		$order->referentie = $this->geef_referentie();
		if ( $originele_order == $order ) { // phpcs:ignore
			return ''; // Als er niets gewijzigd is aan de order heeft het geen zin om een nieuwe factuur aan te maken.
		}
		$order->opmerking = $opmerking;
		$order->save( 'Order gewijzigd' );
		$this->betaal_link = $this->maak_link(
			[
				'order' => $order->id,
				'art'   => $this->artikel_type,
			],
			'betaling'
		);
		return $this->maak_factuur( $order, 'correctie' );
	}

	/**
	 * Maak een controle string aan.
	 *
	 * @since  6.1.0
	 *
	 * @return string Hash string.
	 */
	final public function controle() : string {
		return hash( 'sha256', "KlEiStAd{$this->code}cOnTrOlE3812LE" );
	}

	/**
	 * Geef de naam van het artikel, kan nader ingevuld worden.
	 *
	 * @return string
	 */
	public function geef_artikelnaam(): string {
		return static::DEFINITIE['naam'];
	}

	/**
	 * Klant gegevens voor op de factuur, kan eventueel aangepast worden zoals bijvoorbeeld voor de contact van een workshop.
	 *
	 * @return array De naw gegevens.
	 */
	public function naw_klant() : array {
		$klant = get_userdata( $this->klant_id );
		return [
			'naam'  => "{$klant->first_name}  {$klant->last_name}",
			'adres' => "{$klant->straat} {$klant->huisnr}\n{$klant->pcode} {$klant->plaats}",
			'email' => "$klant->display_name <$klant->user_email>",
		];
	}

	/**
	 * De link die in een email als parameter meegegeven kan worden.
	 *
	 * @param array  $args   Een array met parameters.
	 * @param string $pagina De pagina waar geland moet worden.
	 * @return string De html link.
	 */
	public function maak_link( array $args, string $pagina ) : string {
		$url = add_query_arg( array_merge( $args, [ 'hsh' => $this->controle() ] ), home_url( "/kleistad-$pagina" ) );
		return "<a href=\"$url\" >Kleistad pagina</a>";
	}

	/**
	 * Maak een factuur aan.
	 *
	 * @param Order  $order De order.
	 * @param string $type  Het type factuur.
	 * @return string Het pad naar de factuur.
	 */
	protected function maak_factuur( Order $order, string $type ): string {
		$factuur = new Factuur();
		return $factuur->run( $order, $type );
	}

	/**
	 * Maak opnieuw de factuur aan
	 *
	 * @param Order $order De order.
	 *
	 * @return string
	 */
	public function herzenden( Order $order ) : string {
		$this->betaal_link = $this->maak_link(
			[
				'order' => $order->id,
				'art'   => $this->artikel_type,
			],
			'betaling'
		);
		return $this->maak_factuur( $order, $order->is_credit() ? 'credit' : '' );
	}

}
