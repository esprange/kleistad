<?php
/**
 * The file that defines the inschrijving class
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Kleistad Inschrijving class.
 *
 * A class definition that define the attributes of a inschrijving class.
 *
 * @since 4.0.87
 *
 * @see n.a.
 * @link URL
 */
class Kleistad_Inschrijving extends Kleistad_Entity {

	const META_KEY = 'kleistad_cursus';

	/**
	 * Store the cursist id
	 *
	 * @since 4.0.87
	 * @access private
	 * @var int $_cursist_id the wp user id the of cursist.
	 */
	private $_cursist_id;

	/**
	 * Store the cursus
	 *
	 * @since 4.0.87
	 * @access private
	 * @var object $_cursus of the cursus in the database.
	 */
	private $_cursus;

	/**
	 * De beginwaarden van een inschrijving
	 *
	 * @since 4.3.0
	 * @access private
	 * @var array $_default_data de standaard waarden bij het aanmaken van een inschrijving.
	 */
	private $_default_data = [
		'code'        => '',
		'datum'       => '',
		'technieken'  => [],
		'i_betaald'   => 0,
		'c_betaald'   => 0,
		'ingedeeld'   => 0,
		'geannuleerd' => 0,
		'opmerking'   => '',
		'aantal'      => 1,
	];

	/**
	 * Constructor
	 *
	 * Create the inschrijving object for cursus to be provided to cursist.
	 *
	 * @since 4.0.87
	 *
	 * @param int $cursist_id id of the cursist.
	 * @param int $cursus_id id of the cursus.
	 */
	public function __construct( $cursist_id, $cursus_id ) {
		$this->_cursus                = new Kleistad_Cursus( $cursus_id );
		$this->_cursist_id            = $cursist_id;
		$this->_default_data['code']  = "C$cursus_id-$cursist_id-" . strftime( '%y%m%d', $this->_cursus->start_datum );
		$this->_default_data['datum'] = date( 'Y-m-d' );

		$inschrijvingen = get_user_meta( $this->_cursist_id, self::META_KEY, true );
		if ( is_array( $inschrijvingen ) && ( isset( $inschrijvingen[ $cursus_id ] ) ) ) {
			$this->_data = wp_parse_args( $inschrijvingen[ $cursus_id ], $this->_default_data );
		} else {
			$this->_data = $this->_default_data;
		}
	}

	/**
	 * Export functie privacy gevoelige data.
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (cursus info).
	 */
	public static function export( $gebruiker_id ) {
		$inschrijvingen = get_user_meta( $gebruiker_id, self::META_KEY, true );
		$items          = [];
		if ( is_array( $inschrijvingen ) ) {
			foreach ( $inschrijvingen as $cursus_id => $inschrijving ) {
				$items[] = [
					'group_id'    => 'cursusinfo',
					'group_label' => 'cursussen informatie',
					'item_id'     => 'cursus-' . $cursus_id,
					'data'        => [
						[
							'name'  => 'aanmeld datum',
							'value' => strftime( '%d-%m-%y', strtotime( $inschrijving['datum'] ) ),
						],
						[
							'name'  => 'opmerking',
							'value' => $inschrijving['opmerking'],
						],
						[
							'name'  => 'ingedeeld',
							'value' => ( $inschrijving['ingedeeld'] ) ? 'ja' : 'nee',
						],
						[
							'name'  => 'geannuleerd',
							'value' => ( $inschrijving['geannuleerd'] ) ? 'ja' : 'nee',
						],
					],
				];
			}
		}
		return $items;
	}

	/**
	 * Erase functie privacy gevoelige data.
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return int aantal verwijderde gegevens.
	 */
	public static function erase( $gebruiker_id ) {
		return 0;
	}

	/**
	 * Getter, using the magic function
	 *
	 * Get attribuut from the object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut name.
	 * @return mixed Attribute value.
	 */
	public function __get( $attribuut ) {
		switch ( $attribuut ) {
			case 'technieken':
				return ( is_array( $this->_data[ $attribuut ] ) ) ? $this->_data[ $attribuut ] : [];
			case 'datum':
				return strtotime( $this->_data[ $attribuut ] );
			case 'i_betaald':
			case 'c_betaald':
			case 'geannuleerd':
				return 1 === intval( $this->_data[ $attribuut ] );
			default:
				return $this->_data[ $attribuut ];
		}
	}

	/**
	 * Setter, using the magic function
	 *
	 * Set attribuut from the object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut name.
	 * @param mixed  $waarde Attribuut value.
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'technieken':
				$this->_data[ $attribuut ] = is_array( $waarde ) ? $waarde : [];
				break;
			case 'datum':
				$this->_data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'i_betaald':
			case 'c_betaald':
			case 'geannuleerd':
				$this->_data[ $attribuut ] = $waarde ? 1 : 0;
				break;
			default:
				$this->_data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Save the data
	 *
	 * Saves the data to the database.
	 *
	 * @since 4.0.87
	 */
	public function save() {
		$bestaande_inschrijvingen = get_user_meta( $this->_cursist_id, self::META_KEY, true );
		if ( is_array( $bestaande_inschrijvingen ) ) {
			$inschrijvingen = $bestaande_inschrijvingen;
		} else {
			$inschrijvingen = [];
		}
		$inschrijvingen[ $this->_cursus->id ] = $this->_data;
		update_user_meta( $this->_cursist_id, self::META_KEY, $inschrijvingen );
	}

	/**
	 * Omdat een inschrijving een meta data object betreft kan het niet omgezet worden naar de laatste versie.
	 *
	 * @param object $data het te laden object.
	 */
	public function load( $data ) {
		$this->_data = wp_parse_args( $data, $this->_default_data );
	}

	/**
	 * Verzenden van de inschrijving of indeling email.
	 *
	 * @param string $type inschrijving of indeling.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type ) {
		$cursist   = get_userdata( $this->_cursist_id );
		$to        = "$cursist->first_name $cursist->last_name <$cursist->user_email>";
		$onderwerp = ucfirst( $type ) . ' cursus';

		switch ( $type ) {
			case 'inschrijving':
				$slug = $this->_cursus->inschrijfslug;
				break;
			case 'indeling':
				$slug = $this->_cursus->indelingslug;
				break;
			case 'lopende':
				$slug = 'kleistad_email_cursus_lopend';
				break;
			case 'betaling':
				$slug = 'kleistad_email_cursus_betaling';
				break;
			case 'betaling_ideal':
				$onderwerp = 'Betaling cursus';
				$slug      = 'kleistad_email_cursus_betaling_ideal';
				break;
			default:
				$slug = '';
		}
		return Kleistad_public::compose_email(
			$to, $onderwerp, $slug, [
				'voornaam'               => $cursist->first_name,
				'achternaam'             => $cursist->last_name,
				'cursus_naam'            => $this->_cursus->naam,
				'cursus_docent'          => $this->_cursus->docent,
				'cursus_start_datum'     => strftime( '%A %d-%m-%y', $this->_cursus->start_datum ),
				'cursus_eind_datum'      => strftime( '%A %d-%m-%y', $this->_cursus->eind_datum ),
				'cursus_start_tijd'      => strftime( '%H:%M', $this->_cursus->start_tijd ),
				'cursus_eind_tijd'       => strftime( '%H:%M', $this->_cursus->eind_tijd ),
				'cursus_technieken'      => implode( ', ', $this->technieken ),
				'cursus_code'            => $this->code,
				'cursus_kosten'          => number_format_i18n( $this->aantal * $this->_cursus->cursuskosten, 2 ),
				'cursus_inschrijfkosten' => number_format_i18n( $this->aantal * $this->_cursus->inschrijfkosten, 2 ),
				'cursus_aantal'          => $this->aantal,
				'cursus_opmerking'       => $this->opmerking,
				'cursus_link'            => '<a href="' . home_url( '/kleistad_cursus_betaling' ) .
												'?gid=' . $this->_cursist_id .
												'&crss=' . $this->_cursus->id .
												'&hsh=' . $this->controle() . '" >Kleistad pagina</a>',
			]
		);
	}

	/**
	 * Betaal de inschrijving met iDeal.
	 *
	 * @param string $bericht      Het bericht bij succesvolle betaling.
	 * @param bool   $inschrijving Of het een inschrijving of cursuskosten betreft.
	 */
	public function betalen( $bericht, $inschrijving ) {

		$betaling   = new Kleistad_Betalen();
		$deelnemers = ( 1 === $this->aantal ) ? '1 cursist' : $this->aantal . ' cursisten';
		if ( $inschrijving && 0 < $this->_cursus->inschrijfkosten ) {
			$betaling->order(
				$this->_cursist_id,
				$this->code . '-inschrijving',
				$this->aantal * $this->_cursus->inschrijfkosten,
				'Kleistad cursus ' . $this->code . ' inschrijfkosten voor ' . $deelnemers,
				$bericht
			);
		} else {
			$betaling->order(
				$this->_cursist_id,
				$this->code . '-cursus',
				$this->aantal * $this->_cursus->cursuskosten,
				'Kleistad cursus ' . $this->code . ' cursuskosten voor ' . $deelnemers,
				$bericht
			);
		}
	}

	/**
	 * Maak een controle string aan.
	 *
	 * @return string Hash string.
	 */
	public function controle() {
		return hash( 'sha256', "KlEiStAd{$this->_cursist_id}C{$this->_cursus->id}cOnTrOlE" );
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @param string $type Betaling, cursus of inschrijfkosten.
	 */
	public function callback( $type ) {
		if ( 'inschrijving' === $type ) {
			$this->i_betaald = true;
			$this->ingedeeld = true;
			$this->email( 'indeling' );
		} elseif ( 'cursus' === $type ) {
			$this->i_betaald = true;
			$this->c_betaald = true;
			$this->ingedeeld = true;
			$this->email( 'betaling_ideal' );
		} else {
			return; // Dit zou niet mogen.
		}
		$this->save();
	}

	/**
	 * Return inschrijvingen.
	 *
	 * @return array inschrijvingen.
	 */
	public static function all() {
		static $arr = null;
		if ( is_null( $arr ) ) {
			$cursisten = get_users( [ 'meta_key' => self::META_KEY ] );
			foreach ( $cursisten as $cursist ) {
				$inschrijvingen = get_user_meta( $cursist->ID, self::META_KEY, true );
				foreach ( $inschrijvingen as $cursus_id => $inschrijving ) {
					$arr[ $cursist->ID ][ $cursus_id ] = new Kleistad_Inschrijving( $cursist->ID, $cursus_id );
					$arr[ $cursist->ID ][ $cursus_id ]->load( $inschrijving );
				}
			}
		}
		return $arr;
	}
}
