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

/**
 * Klasse voor het beheren van de stook saldo.
 *
 * @property float saldo
 */
class Kleistad_Saldo {

	const META_KEY = 'stooksaldo';

	/**
	 * De attributen van het saldo
	 *
	 * @since      4.0.87
	 *
	 * @var array De attributen van het saldo.
	 */
	private $_data;

	/**
	 * De gebruiker identificatie
	 *
	 * @since      4.0.87
	 *
	 * @var int Het gebruiker_id.
	 */
	private $_gebruiker_id;

	/**
	 * Private functie welke de update van stooksaldo.log doet.
	 *
	 * @since      4.0.87
	 *
	 * @param string $tekst Toe te voegen tekst aan log.
	 */
	private static function write_log( $tekst ) {
		$upload_dir = wp_upload_dir();
		file_put_contents( $upload_dir['basedir'] . '/stooksaldo.log', date( 'c' ) . " : $tekst\n", FILE_APPEND); // phpcs:ignore
	}

	/**
	 * Private functie om de huidige saldo stand op te vragen
	 *
	 * @since      4.0.87
	 *
	 * @return float De huidige saldo stand.
	 */
	private function huidig_saldo() {
		$huidig_saldo = get_user_meta( $this->_gebruiker_id, self::META_KEY, true );
		return ( '' === $huidig_saldo ) ? 0.0 : (float) $huidig_saldo;
	}

	/**
	 * Functie om algemene teksten toe te voegen aan de log
	 *
	 * @since      4.0.87
	 *
	 * @param string $reden De te loggen tekst.
	 */
	public static function log( $reden ) {
		self::write_log( $reden );
	}

	/**
	 * De constructor
	 *
	 * @since      4.0.87
	 *
	 * @param int $gebruiker_id De gebruiker waarvoor het saldo wordt gemaakt.
	 */
	public function __construct( $gebruiker_id ) {
		$this->_gebruiker_id   = $gebruiker_id;
		$this->_data['bedrag'] = $this->huidig_saldo();
	}

	/**
	 * Export functie privacy gevoelige data.
	 *
	 * @since      4.3.0
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (stooksaldo).
	 */
	public static function export( $gebruiker_id ) {
		$saldo   = new static( $gebruiker_id );
		$items   = [];
		$items[] = [
			'group_id'    => self::META_KEY,
			'group_label' => 'Stooksaldo informatie',
			'item_id'     => 'stooksaldo-1',
			'data'        => [
				[
					'name'  => 'Saldo',
					'value' => number_format_i18n( $saldo->huidig_saldo(), 2 ),
				],
			],
		];
		return $items;
	}

	/**
	 * Erase functie privacy gevoelige data.
	 *
	 * @since      4.3.0
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return int Aantal verwijderd.
	 */
	public static function erase( $gebruiker_id ) {
		return delete_user_meta( $gebruiker_id, self::META_KEY ) ? 1 : 0;
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
		$this->_data[ $attribuut ] = $waarde;
	}

	/**
	 * Getter magic functie
	 *
	 * @since      4.0.87
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt opgevraagd.
	 * @return mixed De waarde.
	 */
	public function __get( $attribuut ) {
		return $this->_data[ $attribuut ];
	}

	/**
	 * Bewaar het aangepaste saldo
	 *
	 * @since      4.0.87
	 *
	 * @param string $reden De reden waarom het saldo wordt aangepast.
	 * @return bool True als saldo is aangepast.
	 */
	public function save( $reden ) {
		$huidig_saldo = $this->huidig_saldo();

		if ( 0.0099 < abs( $huidig_saldo - $this->bedrag ) ) {
			update_user_meta( $this->_gebruiker_id, self::META_KEY, $this->bedrag );
			$gebruiker = get_userdata( $this->_gebruiker_id );
			self::write_log( $gebruiker->display_name . ' nu: € ' . number_format_i18n( $huidig_saldo, 2 ) . ' naar: € ' . number_format_i18n( $this->bedrag, 2 ) . ' vanwege ' . $reden );
			return true;
		}
		return false;
	}

	/**
	 * Betaal de bijstorting saldo met iDeal.
	 *
	 * @since      4.2.0
	 *
	 * @param string $bericht Het bericht bij succesvolle betaling.
	 * @param float  $bedrag  Het te betalen bedrag.
	 */
	public function betalen( $bericht, $bedrag ) {
		$betaling = new Kleistad_Betalen();
		$code     = "S$this->_gebruiker_id-" . strftime( '%y%m%d' );
		$betaling->order(
			$this->_gebruiker_id,
			__CLASS__ . '-' . $code,
			$bedrag,
			'Kleistad stooksaldo ' . $code,
			$bericht
		);
	}

	/**
	 * Verzenden van de saldo verhoging email.
	 *
	 * @since      4.0.87
	 *
	 * @param string $type   direct betaald of melding van storting.
	 * @param float  $bedrag het saldo dat toegevoegd is.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $bedrag ) {
		$gebruiker = get_userdata( $this->_gebruiker_id );
		$to        = "$gebruiker->display_name <$gebruiker->user_email>";
		return Kleistad_public::compose_email(
			$to,
			'Bijstorting stooksaldo',
			'kleistad_email_saldo_wijziging' . $type,
			[
				'voornaam'   => $gebruiker->first_name,
				'achternaam' => $gebruiker->last_name,
				'bedrag'     => number_format_i18n( $bedrag, 2 ),
				'saldo'      => number_format_i18n( $this->bedrag, 2 ),
			]
		);
	}

	/**
	 * Verwerk een betaling. Wordt aangeroepen vanuit de betaal callback
	 *
	 * @since      4.2.0
	 *
	 * @param array $parameters De parameters 0: gebruiker-id, 1: of het een herstart betreft.
	 * @param float $bedrag     Het bedrag dat betaald is.
	 * @param bool  $betaald    Of er werkelijk betaald is.
	 */
	public static function callback( $parameters, $bedrag, $betaald = true ) {
		if ( $betaald ) {
			$saldo         = new static( intval( $parameters[0] ) );
			$saldo->bedrag = $saldo->bedrag + $bedrag;
			$saldo->save( 'betaling per iDeal' );
			$saldo->email( '_ideal', $bedrag );
		}
	}
}
