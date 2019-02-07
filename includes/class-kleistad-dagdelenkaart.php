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
 *
 * @property string code
 * @property int    datum
 * @property int    start_datum
 * @property string opmerking
 */
class Kleistad_Dagdelenkaart extends Kleistad_Entity {

	const META_KEY = 'kleistad_dagdelenkaart';

	/**
	 * De gebruiker_id
	 *
	 * @since 4.3.0
	 *
	 * @access private
	 * @var int $gebruiker_id het wp user id van de gebruiker.
	 */
	private $gebruiker_id;

	/**
	 * De beginwaarden van een dagdelenkaart.
	 *
	 * @since 4.3.0
	 *
	 * @access private
	 * @var array $default_data de standaard waarden bij het aanmaken van een dagdelenkaart.
	 */
	private $default_data = [
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
		$this->gebruiker_id         = $gebruiker_id;
		$this->default_data['code'] = "K$gebruiker_id-" . strftime( '%y%m%d', strtotime( 'today' ) );

		$this->default_data['datum'] = date( 'Y-m-d' );

		$dagdelenkaart = get_user_meta( $this->gebruiker_id, self::META_KEY, true );
		if ( is_array( $dagdelenkaart ) ) {
			$this->data = wp_parse_args( $dagdelenkaart, $this->default_data );
		} else {
			$this->data = $this->default_data;
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
				return strtotime( $this->data[ $attribuut ] );
			default:
				return $this->data[ $attribuut ];
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
				$this->data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Bewaar de dagdelenkaart als metadata in de database.
	 *
	 * @since 4.3.0
	 */
	public function save() {
		update_user_meta( $this->gebruiker_id, self::META_KEY, $this->data );
	}

	/**
	 * Omdat een dagdelenkaart een meta data object betreft kan het niet omgezet worden naar de laatste versie.
	 *
	 * @since      4.3.0
	 *
	 * @param array $data het te laden object.
	 */
	public function load( $data ) {
		$this->data = wp_parse_args( $data, $this->default_data );
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
		$gebruiker = get_userdata( $this->gebruiker_id );
		$to        = "$gebruiker->display_name <$gebruiker->user_email>";
		return Kleistad_email::compose(
			$to,
			'Welkom bij Kleistad',
			'kleistad_email_dagdelenkaart' . $type,
			[
				'voornaam'                => $gebruiker->first_name,
				'achternaam'              => $gebruiker->last_name,
				'loginnaam'               => $gebruiker->user_login,
				'start_datum'             => strftime( '%d-%m-%y', $this->start_datum ),
				'dagdelenkaart_code'      => $this->code,
				'dagdelenkaart_opmerking' => ( '' !== $this->opmerking ) ? 'De volgende opmerking heb je doorgegeven: ' . $this->opmerking : '',
				'dagdelenkaart_prijs'     => number_format_i18n( $options['dagdelenkaart'], 2 ),
			]
		);
	}

	/**
	 * Start de betaling van een nieuw dagdelenkaart.
	 *
	 * @since      4.3.0
	 *
	 * @param string $bericht  Te tonen melding als betaling gelukt.
	 */
	public function betalen( $bericht ) {
		$options = Kleistad::get_options();

		$betalen = new Kleistad_Betalen();
		$betalen->order(
			$this->gebruiker_id,
			__CLASS__ . '-' . $this->code,
			$options['dagdelenkaart'],
			'Kleistad dagdelenkaart ' . $this->code,
			$bericht,
			false
		);
	}

	/**
	 * Activeer een dagdelenkaart. Wordt aangeroepen vanuit de betaal callback.
	 *
	 * @since      4.3.0
	 *
	 * @param array $parameters De parameters 0: gebruiker-id, 1: de aankoopdatum.
	 * @param float $bedrag     Het bedrag dat betaald is.
	 * @param bool  $betaald    Of er werkelijk betaald is.
	 */
	public static function callback( $parameters, $bedrag, $betaald = true ) {
		if ( $betaald ) {
			$dagdelenkaart = new static( intval( $parameters[0] ) );
			$dagdelenkaart->email( '_ideal' );
		}
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
		$gebruikers = get_users(
			[
				'meta_key' => self::META_KEY,
				'fields'   => [ 'ID' ],
			]
		);

		foreach ( $gebruikers as $gebruiker ) {
			$dagdelenkaart         = get_user_meta( $gebruiker->ID, self::META_KEY, true );
			$arr[ $gebruiker->ID ] = new Kleistad_Dagdelenkaart( $gebruiker->ID );
			$arr[ $gebruiker->ID ]->load( $dagdelenkaart );
		}
		return $arr;
	}
}
