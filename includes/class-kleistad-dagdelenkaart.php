<?php
/**
 * Definieer de dagdelenkaart class
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Kleistad dagdelenkaart.
 *
 * @since      4.3.0
 */
class Kleistad_Dagdelenkaart extends Kleistad_Entity {

	const META_KEY = 'kleistad_dagdelenkaart';

	/**
	 * De gebruiker_id
	 *
	 * @since 4.3.0
	 *
	 * @access private
	 * @var int $_gebruiker_id het wp user id van de gebruiker.
	 */
	private $_gebruiker_id;

	/**
	 * De beginwaarden van een dagdelenkaart.
	 *
	 * @since 4.3.0
	 *
	 * @access private
	 * @var array $_default_data de standaard waarden bij het aanmaken van een dagdelenkaart.
	 */
	private $_default_data = [
		'code'        => '',
		'datum'       => '',
		'start_datum' => '',
		'opmerking'   => '',
	];

	/**
	 * Constructor
	 *
	 * @since 4.3.0
	 *
	 * @param int $gebruiker_id wp id van de gebruiker.
	 */
	public function __construct( $gebruiker_id ) {
		$this->_gebruiker_id         = $gebruiker_id;
		$this->_default_data['code'] = "K$gebruiker_id-" . strftime( '%y%m%d', time() );

		$this->_default_data['datum'] = date( 'Y-m-d' );

		$dagdelenkaart = get_user_meta( $this->_gebruiker_id, self::META_KEY, true );
		if ( is_array( $dagdelenkaart ) ) {
			$this->_data = wp_parse_args( $dagdelenkaart, $this->_default_data );
		} else {
			$this->_data = $this->_default_data;
		}
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @since 4.3.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( $attribuut ) {
		switch ( $attribuut ) {
			case 'datum':
			case 'start_datum':
				return strtotime( $this->_data[ $attribuut ] );
			default:
				return $this->_data[ $attribuut ];
		}
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @since 4.3.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'datum':
			case 'start_datum':
				$this->_data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			default:
				$this->_data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Bewaar de dagdelenkaart als metadata in de database.
	 *
	 * @since 4.3.0
	 */
	public function save() {
		update_user_meta( $this->_gebruiker_id, self::META_KEY, $this->_data );
	}

	/**
	 * Omdat een dagdelenkaart een meta data object betreft kan het niet omgezet worden naar de laatste versie.
	 *
	 * @since      4.3.0
	 *
	 * @param array $data het te laden object.
	 */
	public function load( $data ) {
		$this->_data = wp_parse_args( $data, $this->_default_data );
	}

	/**
	 * Verzenden van de welkomst email.
	 *
	 * @since      4.3.0
	 *
	 * @param string $type Welke email er verstuurd moet worden.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type ) {
		$options   = Kleistad::get_options();
		$gebruiker = get_userdata( $this->_gebruiker_id );
		$to        = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
		return Kleistad_public::compose_email(
			$to, 'Welkom bij Kleistad', 'kleistad_email_dagdelenkaart' . $type, [
				'voornaam'                => $gebruiker->first_name,
				'achternaam'              => $gebruiker->last_name,
				'loginnaam'               => $gebruiker->user_login,
				'start_datum'             => strftime( '%d-%m-%y', $this->start_datum ),
				'dagdelenkaart_code'      => $this->code,
				'dagdelenkaart_opmerking' => $this->opmerking,
				'dagdelenkaart_prijs'     => number_format_i18n( 3 * $options['dagdelenkaart'], 2 ),
			]
		);
	}

	/**
	 * Start de betaling van een nieuw dagdelenkaart.
	 *
	 * @since      4.3.0
	 *
	 * @param int    $start_datum Datum waarop dagdelenkaart gestart wordt.
	 * @param string $betaalwijze Ideal of bank.
	 * @param bool   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function betalen( $start_datum, $betaalwijze, $admin = false ) {
		$this->start_datum = $start_datum;
		$options           = Kleistad::get_options();

		if ( 'ideal' === $betaalwijze ) {
			$this->save();

			$betalen = new Kleistad_Betalen();
			$betalen->order(
				$this->_gebruiker_id,
				$this->code,
				$options['dagdelenkaart'],
				'Kleistad dagdelenkaart ' . $this->code,
				'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging',
				false
			);
		} else {
			$this->email( '_bank' );
		}
		return true;
	}

	/**
	 * Activeer een dagdelenkaart. Wordt aangeroepen vanuit de betaal callback.
	 *
	 * @since      4.3.0
	 */
	public function callback() {
		$this->email( '_ideal' );
		$this->save();
	}

	/**
	 * Return alle dagdelenkaarten.
	 *
	 * @since      4.3.0
	 *
	 * @return array dagdelenkaarten.
	 */
	public static function all() {
		$arr        = [];
		$gebruikers = get_users( [ 'meta_key' => self::META_KEY ] );
		foreach ( $gebruikers as $gebruiker ) {
			$dagdelenkaart         = get_user_meta( $gebruiker->ID, self::META_KEY, true );
			$arr[ $gebruiker->ID ] = new Kleistad_Dagdelenkaart( $gebruiker->ID );
			$arr[ $gebruiker->ID ]->load( $dagdelenkaart );
		}
		return $arr;
	}
}
