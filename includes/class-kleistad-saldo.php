<?php
/**
 * The file that defines the saldo class
 *
 * A class definition including the ovens, reserveringen and regelingen
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Klasse voor het beheren van de stook saldo.
 */
class Kleistad_Saldo {

	const META_KEY = 'stooksaldo';

	/**
	 * De attributen van het saldo
	 *
	 * @var array De attributen van het saldo.
	 */
	private $_data;

	/**
	 * De gebruiker identificatie
	 *
	 * @var int Het gebruiker_id.
	 */
	private $_gebruiker_id;

	/**
	 * Private functie welke de update van stooksaldo.log doet.
	 *
	 * @param string $tekst Toe te voegen tekst aan log.
	 */
	private static function write_log( $tekst ) {
		$upload_dir = wp_upload_dir();
		$f          = fopen( $upload_dir['basedir'] . '/stooksaldo.log', 'a' );
		fwrite( $f, date( 'c' ) . " : $tekst\n" );
		fclose( $f );
	}

	/**
	 * Private functie om de huidige saldo stand op te vragen
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
	 * @param tekst $reden De te loggen tekst.
	 */
	public static function log( $reden ) {
		self::write_log( $reden );
	}

	/**
	 * De constructor
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
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (stooksaldo).
	 */
	public static function export( $gebruiker_id ) {
		$saldo   = new static( $gebruiker_id );
		$items[] = [
			'group_id'    => self::META_KEY,
			'group_label' => 'stooksaldo informatie',
			'item_id'     => 'stooksaldo-1',
			'data'        => [
				[
					'name'  => self::META_KEY,
					'value' => $saldo->huidig_saldo(),
				],
			],
		];
		return $items;
	}

	/**
	 * Erase functie privacy gevoelige data.
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
	 * @param string $attribuut Het attribuut waarvan de waarde wordt aangepast.
	 * @param mixed  $waarde De nieuwe waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		$this->_data[ $attribuut ] = $waarde;
	}

	/**
	 * Getter magic functie
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
	 * @param string $reden De reden waarom het saldo wordt aangepast.
	 * @return bool True als saldo is aangepast.
	 */
	public function save( $reden ) {
		$huidig_saldo = $this->huidig_saldo();

		if ( $huidig_saldo !== $this->bedrag ) {
			update_user_meta( $this->_gebruiker_id, self::META_KEY, $this->bedrag );
			$gebruiker = get_userdata( $this->_gebruiker_id );
			self::write_log( "$gebruiker->display_name nu: $huidig_saldo naar: " . $this->bedrag . " vanwege $reden" );
			return true;
		}
		return false;
	}

	/**
	 * Betaal de inschrijving met iDeal.
	 *
	 * @param string $bericht Het bericht bij succesvolle betaling.
	 * @param float  $bedrag  Het te betalen bedrag.
	 */
	public function betalen( $bericht, $bedrag ) {
		$betaling = new Kleistad_Betalen();
		$code     = "S$this->_gebruiker_id-" . strftime( '%y%m%d' );
		$betaling->order(
			$this->_gebruiker_id,
			$code,
			$bedrag,
			'Kleistad stooksaldo ' . $code,
			$bericht
		);
	}

	/**
	 * Verzenden van de saldo verhoging email.
	 *
	 * @param string $type   direct betaald of melding van storting.
	 * @param float  $bedrag het saldo dat toegevoegd is.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $bedrag ) {
		$gebruiker = get_userdata( $this->_gebruiker_id );
		$to        = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
		return Kleistad_public::compose_email(
			$to, 'Bijstorting stooksaldo', 'kleistad_email_saldo_wijziging' . $type, [
				'voornaam'   => $gebruiker->first_name,
				'achternaam' => $gebruiker->last_name,
				'bedrag'     => $bedrag,
				'saldo'      => $this->bedrag,
			]
		);
	}

	/**
	 * Verwerk een betaling. Wordt aangeroepen vanuit de betaal callback
	 *
	 * @param float $bedrag Het bedrag dat betaald is.
	 */
	public function callback( $bedrag ) {
		$this->bedrag = $this->bedrag + $bedrag;
		$this->save( 'betaling per iDeal' );
		$this->email( '_ideal', $bedrag );
	}
}