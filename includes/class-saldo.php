<?php
/**
 * De definitie van de (stook) saldo class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Klasse voor het beheren van de stook saldo.
 *
 * @property float  bedrag
 * @property array  storting
 * @property float  prijs
 */
class Saldo extends Artikel {

	public const DEFINITIE = [
		'prefix' => 'S',
		'naam'   => 'stooksaldo',
		'pcount' => 1,
	];
	public const META_KEY  = 'kleistad_stooksaldo';

	/**
	 * De beginwaarden van een dagdelenkaart.
	 *
	 * @access private
	 * @var array $default_data de standaard waarden bij het aanmaken van een dagdelenkaart.
	 */
	private $default_data = [
		'storting' => [
			[],
		],
		'bedrag'   => 0.0,
	];

	/**
	 * De reden waarvoor het saldo gewijzigd wordt. Is alleen voor de logging bedoeld.
	 *
	 * @access public
	 * @var string $reden De reden.
	 */
	public $reden;

	/**
	 * Het actie object
	 *
	 * @var SaldoActie $actie De saldo acties.
	 */
	public SaldoActie $actie;

	/**
	 * De constructor
	 *
	 * @since      4.0.87
	 *
	 * @param int $klant_id De gebruiker waarvoor het saldo wordt gemaakt.
	 */
	public function __construct( $klant_id ) {
		$this->klant_id = $klant_id;
		$this->betalen  = new Betalen();
		$saldo          = get_user_meta( $this->klant_id, self::META_KEY, true ) ?: $this->default_data;
		$this->data     = wp_parse_args( $saldo, $this->default_data );
		$this->actie    = new SaldoActie( $this );
	}

	/**
	 * Getter magic functie
	 *
	 * @since      4.0.87
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt opgevraagd.
	 * @return mixed De waarde.
	 */
	public function &__get( $attribuut ) {
		if ( array_key_exists( $attribuut, $this->data ) ) {
			return $this->data[ $attribuut ];
		}
		$laatste_storting = end( $this->data['storting'] );
		if ( array_key_exists( $attribuut, $laatste_storting ) ) {
			return $laatste_storting[ $attribuut ];
		}
		return null;
	}

	/**
	 * Setter magic functie
	 *
	 * @since      4.0.87
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt aangepast.
	 * @param mixed  $waarde De nieuwe waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		if ( 'bedrag' === $attribuut ) {
			$this->data[ $attribuut ] = round( $waarde, 2 );
			return;
		}
		$this->data['storting'][ array_key_last( $this->data['storting'] ) ][ $attribuut ] = $waarde;
	}

	/**
	 * Betaal de bijstorting saldo met iDeal.
	 *
	 * @since      4.2.0
	 *
	 * @param  string $bericht Het bericht bij succesvolle betaling.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het niet lukt.
	 */
	public function doe_idealbetaling( string $bericht, float $openstaand = null ) {
		return $this->betalen->order(
			$this->klant_id,
			$this->geef_referentie(),
			$openstaand ?? $this->prijs,
			'Kleistad stooksaldo ' . $this->code,
			$bericht,
			false
		);
	}

	/**
	 * Geef de code terug.
	 *
	 * @return string
	 */
	public function geef_referentie() : string {
		return $this->code;
	}

	/**
	 * Verzenden van de saldo verhoging email.
	 *
	 * @since      4.0.87
	 *
	 * @param string $type    Direct betaald of melding van storting.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return boolean succes of falen van verzending email.
	 */
	public function verzend_email( $type, $factuur = '' ) {
		$emailer   = new Email();
		$gebruiker = get_userdata( $this->klant_id );
		return $emailer->send(
			[
				'to'          => "$gebruiker->display_name <$gebruiker->user_email>",
				'subject'     => 'Bijstorting stooksaldo',
				'slug'        => 'saldo' . $type,
				'attachments' => $factuur ?: [],
				'parameters'  => [
					'voornaam'   => $gebruiker->first_name,
					'achternaam' => $gebruiker->last_name,
					'bedrag'     => number_format_i18n( $this->prijs, 2 ),
					'saldo'      => number_format_i18n( $this->bedrag, 2 ),
					'saldo_link' => $this->betaal_link,
				],
			]
		);
	}

	/**
	 * Bewaar het aangepaste saldo
	 *
	 * @since      4.0.87
	 *
	 * @return bool True als saldo is aangepast.
	 */
	public function save() {
		$saldo        = get_user_meta( $this->klant_id, self::META_KEY, true );
		$huidig_saldo = $saldo ? (float) $saldo['bedrag'] : 0.0;
		if ( 0.01 > abs( $huidig_saldo - $this->bedrag ) ) {
			return true;
		}
		if ( update_user_meta( $this->klant_id, self::META_KEY, $this->data ) ) {
			$tekst = get_userdata( $this->klant_id )->display_name . ' nu: ' . number_format_i18n( $huidig_saldo, 2 ) . ' naar: ' . number_format_i18n( $this->bedrag, 2 ) . ' vanwege ' . $this->reden;
			file_put_contents(  // phpcs:ignore
				wp_upload_dir()['basedir'] . '/stooksaldo.log',
				date( 'c' ) . " : $tekst\n",
				FILE_APPEND
			);
			return true;
		}
		return false;
	}

	/**
	 * Geef de status van het artikel als een tekst terug.
	 *
	 * @return string De status tekst.
	 */
	public function geef_statustekst() : string {
		return 0 < $this->bedrag ? 'saldo' : '';
	}

	/**
	 * Verwijder het saldo
	 */
	public function erase() {
		delete_user_meta( $this->klant_id, self::META_KEY );
	}

	/**
	 * Verwerk een betaling. Wordt aangeroepen vanuit de betaal callback
	 *
	 * @since      4.2.0
	 *
	 * @param int    $order_id      De order_id, als die al bestaat.
	 * @param float  $bedrag        Het bedrag dat betaald is.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk_betaling( $order_id, $bedrag, $betaald, $type, $transactie_id = '' ) {
		if ( $betaald ) {
			$this->bedrag = round( $this->bedrag + $bedrag, 2 );
			$this->reden  = $bedrag > 0 ? 'storting' : 'stornering';
			$this->save();

			if ( $order_id ) {
				/**
				 * Er bestaat al een order dus dit is een betaling o.b.v. een email link of per bank.
				 */
				$this->ontvang_order( $order_id, $bedrag, $transactie_id );
				if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
					$this->verzend_email( '_ideal_betaald' );
				}
				return;
			}
			/**
			 * Een betaling vanuit het formulier
			 */
			$this->verzend_email( '_ideal', $this->bestel_order( $bedrag, strtotime( '+7 days  0:00' ), '', $transactie_id ) );
		}
	}

	/**
	 * De factuur regels.
	 *
	 * @return Orderregel
	 */
	protected function geef_factuurregels() {
		return new Orderregel( 'stooksaldo', 1, $this->prijs );
	}

}
