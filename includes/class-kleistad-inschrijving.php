<?php
/**
 * Definieer de inschrijving class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Kleistad Inschrijving class.
 *
 * @since 4.0.87
 */
class Kleistad_Inschrijving extends Kleistad_Entity {

	const META_KEY = 'kleistad_cursus';

	/**
	 * De cursist id
	 *
	 * @since 4.0.87
	 *
	 * @access private
	 * @var int $_cursist_id de wp user id van de cursist.
	 */
	private $_cursist_id;

	/**
	 * De cursus
	 *
	 * @since 4.0.87
	 *
	 * @access private
	 * @var object $_cursus cursus object.
	 */
	private $_cursus;

	/**
	 * De beginwaarden van een inschrijving
	 *
	 * @since 4.3.0
	 *
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
	 * @since 4.0.87
	 *
	 * @param int $cursist_id wp user id van de cursist.
	 * @param int $cursus_id id van de cursus.
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
	 * @since      4.3.0
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
	 * Get attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
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
			case 'gedeeld':
				return 0 < $this->_cursus->inschrijfkosten;
			default:
				return $this->_data[ $attribuut ];
		}
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
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
	 * Sla de inschrijving op als user metadata in de database.
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
	 * Corrigeer de inschrijving naar nieuwe cursus.
	 *
	 * @since 4.5.0
	 *
	 * @param int $cursus_id nieuw cursus_id.
	 */
	public function correct( $cursus_id ) {
		$bestaande_inschrijvingen = get_user_meta( $this->_cursist_id, self::META_KEY, true );
		if ( is_array( $bestaande_inschrijvingen ) ) {
			$inschrijvingen = $bestaande_inschrijvingen;
			if ( ! array_key_exists( $cursus_id, $inschrijvingen ) ) {
				$cursus                       = new Kleistad_Cursus( $cursus_id );
				$this->_data['code']          = "C$cursus_id-$this->_cursist_id-" . strftime( '%y%m%d', $cursus->start_datum );
				$inschrijvingen[ $cursus_id ] = $this->_data;
				unset( $inschrijvingen[ $this->_cursus->id ] );
				update_user_meta( $this->_cursist_id, self::META_KEY, $inschrijvingen );
				return true;
			}
		}
		return false; // zou niet mogen.
	}

	/**
	 * Omdat een inschrijving een meta data object betreft kan het niet omgezet worden naar de laatste versie.
	 *
	 * @param array $data het te laden object.
	 */
	public function load( $data ) {
		$this->_data = wp_parse_args( $data, $this->_default_data );
	}

	/**
	 * Verzenden van de inschrijving of indeling email.
	 *
	 * @since      4.0.87
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
				'cursus_opmerking'       => ( '' !== $this->opmerking ) ? 'De volgende opmerking heb je doorgegeven: ' . $this->opmerking : '',
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
	 * @since        4.2.0
	 *
	 * @param string $bericht      Het bericht bij succesvolle betaling.
	 * @param bool   $inschrijving Of het een inschrijving of cursuskosten betreft.
	 */
	public function betalen( $bericht, $inschrijving ) {

		$betaling   = new Kleistad_Betalen();
		$deelnemers = ( 1 === $this->aantal ) ? '1 cursist' : $this->aantal . ' cursisten';
		if ( $inschrijving && $this->gedeeld ) {
			$betaling->order(
				$this->_cursist_id,
				__CLASS__ . '-' . $this->code . '-inschrijving',
				$this->aantal * $this->_cursus->inschrijfkosten,
				'Kleistad cursus ' . $this->code . ' inschrijfkosten voor ' . $deelnemers,
				$bericht
			);
		} else {
			$betaling->order(
				$this->_cursist_id,
				__CLASS__ . '-' . $this->code . '-cursus',
				$this->aantal * $this->_cursus->cursuskosten,
				'Kleistad cursus ' . $this->code . ' cursuskosten voor ' . $deelnemers,
				$bericht
			);
		}
	}

	/**
	 * Maak een controle string aan.
	 *
	 * @since        4.2.0
	 *
	 * @return string Hash string.
	 */
	public function controle() {
		return hash( 'sha256', "KlEiStAd{$this->_cursist_id}C{$this->_cursus->id}cOnTrOlE" );
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        4.2.0
	 *
	 * @param array $parameters De parameters 0: cursus-id, 1: gebruiker-id, 2: startdatum, 3: type betaling.
	 * @param float $bedrag     Het betaalde bedrag, wordt hier niet gebruikt.
	 * @param bool  $betaald    Of er werkelijk betaald is.
	 * @phan-suppress PhanUnusedPublicMethodParameter
	 */
	public static function callback( $parameters, $bedrag, $betaald = true ) {
		if ( $betaald ) {
			$inschrijving = new static( intval( $parameters[1] ), intval( $parameters[0] ) );
			switch ( $parameters[3] ) {
				case 'inschrijving':
					$inschrijving->i_betaald = true;
					$inschrijving->ingedeeld = true;
					$inschrijving->email( 'indeling' );
					$inschrijving->save();
					break;

				case 'cursus':
					$inschrijving->i_betaald = true;
					$inschrijving->c_betaald = true;
					$inschrijving->ingedeeld = true;
					if ( $inschrijving->gedeeld ) {
						$inschrijving->email( 'betaling_ideal' );
					} else {
						$inschrijving->email( 'indeling' );
					}
					$inschrijving->save();
					break;

				default:
					break;
			}
		}
	}

	/**
	 * Return inschrijvingen.
	 *
	 * @return array inschrijvingen.
	 */
	public static function all() {
		static $arr = null;
		if ( is_null( $arr ) ) {
			$arr       = [];
			$cursisten = get_users( [ 'meta_key' => self::META_KEY ] );
			foreach ( $cursisten as $cursist ) {
				$inschrijvingen = get_user_meta( $cursist->ID, self::META_KEY, true );
				krsort( $inschrijvingen );
				foreach ( $inschrijvingen as $cursus_id => $inschrijving ) {
					$arr[ $cursist->ID ][ $cursus_id ] = new Kleistad_Inschrijving( $cursist->ID, $cursus_id );
					$arr[ $cursist->ID ][ $cursus_id ]->load( $inschrijving );
				}
			}
		}
		return $arr;
	}
}
