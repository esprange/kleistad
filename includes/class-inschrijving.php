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

namespace Kleistad;

/**
 * Kleistad Inschrijving class.
 *
 * @since 4.0.87
 *
 * @property string code
 * @property int    datum
 * @property array  technieken
 * @property bool   i_betaald
 * @property bool   c_betaald
 * @property bool   ingedeeld
 * @property bool   geannuleerd
 * @property string opmerking
 * @property int    aantal
 * @property bool   restant_email
 * @property bool   gedeeld
 */
class Inschrijving extends Entity {

	const META_KEY = 'kleistad_cursus';

	/**
	 * De cursist id
	 *
	 * @since 4.0.87
	 *
	 * @access private
	 * @var int $cursist_id de wp user id van de cursist.
	 */
	private $cursist_id;

	/**
	 * De cursus
	 *
	 * @since 4.0.87
	 *
	 * @access private
	 * @var object $cursus cursus object.
	 */
	private $cursus;

	/**
	 * De beginwaarden van een inschrijving
	 *
	 * @since 4.3.0
	 *
	 * @access private
	 * @var array $default_data de standaard waarden bij het aanmaken van een inschrijving.
	 */
	private $default_data = [
		'code'          => '',
		'datum'         => '',
		'technieken'    => [],
		'i_betaald'     => 0,
		'c_betaald'     => 0,
		'ingedeeld'     => 0,
		'geannuleerd'   => 0,
		'opmerking'     => '',
		'aantal'        => 1,
		'restant_email' => 0,
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
		$this->emailer               = new \Kleistad\Email();
		$this->cursus                = new \Kleistad\Cursus( $cursus_id );
		$this->cursist_id            = $cursist_id;
		$this->default_data['code']  = "C$cursus_id-$cursist_id-" . strftime( '%y%m%d', $this->cursus->start_datum );
		$this->default_data['datum'] = date( 'Y-m-d' );

		$inschrijvingen = get_user_meta( $this->cursist_id, self::META_KEY, true );
		if ( is_array( $inschrijvingen ) && ( isset( $inschrijvingen[ $cursus_id ] ) ) ) {
			$this->data = wp_parse_args( $inschrijvingen[ $cursus_id ], $this->default_data );
		} else {
			$this->data = $this->default_data;
		}
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
				return ( is_array( $this->data[ $attribuut ] ) ) ? $this->data[ $attribuut ] : [];
			case 'datum':
				return strtotime( $this->data[ $attribuut ] );
			case 'i_betaald':
			case 'c_betaald':
			case 'geannuleerd':
			case 'restant_email':
				return boolval( $this->data[ $attribuut ] );
			case 'gedeeld':
				return 0 < $this->cursus->inschrijfkosten;
			default:
				if ( is_string( $this->data[ $attribuut ] ) ) {
					return htmlspecialchars_decode( $this->data[ $attribuut ] );
				}
				return $this->data[ $attribuut ];
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
				$this->data[ $attribuut ] = is_array( $waarde ) ? $waarde : [];
				break;
			case 'datum':
				$this->data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'i_betaald':
			case 'c_betaald':
			case 'geannuleerd':
			case 'restant_email':
				$this->data[ $attribuut ] = $waarde ? 1 : 0;
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Sla de inschrijving op als user metadata in de database.
	 *
	 * @since 4.0.87
	 */
	public function save() {
		$bestaande_inschrijvingen = get_user_meta( $this->cursist_id, self::META_KEY, true );
		if ( is_array( $bestaande_inschrijvingen ) ) {
			$inschrijvingen = $bestaande_inschrijvingen;
		} else {
			$inschrijvingen = [];
		}
		$inschrijvingen[ $this->cursus->id ] = $this->data;
		update_user_meta( $this->cursist_id, self::META_KEY, $inschrijvingen );
	}

	/**
	 * Corrigeer de inschrijving naar nieuwe cursus.
	 *
	 * @since 4.5.0
	 *
	 * @param int $cursus_id nieuw cursus_id.
	 */
	public function correct( $cursus_id ) {
		$bestaande_inschrijvingen = get_user_meta( $this->cursist_id, self::META_KEY, true );
		if ( is_array( $bestaande_inschrijvingen ) ) {
			$inschrijvingen = $bestaande_inschrijvingen;
			if ( ! array_key_exists( $cursus_id, $inschrijvingen ) ) {
				$cursus                       = new \Kleistad\Cursus( $cursus_id );
				$this->data['code']           = "C$cursus_id-$this->cursist_id-" . strftime( '%y%m%d', $cursus->start_datum );
				$inschrijvingen[ $cursus_id ] = $this->data;
				unset( $inschrijvingen[ $this->cursus->id ] );
				update_user_meta( $this->cursist_id, self::META_KEY, $inschrijvingen );
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
		$this->data = wp_parse_args( $data, $this->default_data );
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
		$cursist   = get_userdata( $this->cursist_id );
		$onderwerp = ucfirst( $type ) . ' cursus';

		switch ( $type ) {
			case 'inschrijving':
				$slug = $this->cursus->inschrijfslug;
				break;
			case 'indeling':
				$slug = $this->cursus->indelingslug;
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
		return $this->emailer->send(
			[
				'to'         => "$cursist->display_name <$cursist->user_email>",
				'subject'    => $onderwerp,
				'slug'       => $slug,
				'parameters' =>
				[
					'voornaam'               => $cursist->first_name,
					'achternaam'             => $cursist->last_name,
					'cursus_naam'            => $this->cursus->naam,
					'cursus_docent'          => $this->cursus->docent,
					'cursus_start_datum'     => strftime( '%A %d-%m-%y', $this->cursus->start_datum ),
					'cursus_eind_datum'      => strftime( '%A %d-%m-%y', $this->cursus->eind_datum ),
					'cursus_start_tijd'      => strftime( '%H:%M', $this->cursus->start_tijd ),
					'cursus_eind_tijd'       => strftime( '%H:%M', $this->cursus->eind_tijd ),
					'cursus_technieken'      => implode( ', ', $this->technieken ),
					'cursus_code'            => $this->code,
					'cursus_kosten'          => number_format_i18n( $this->aantal * $this->cursus->cursuskosten, 2 ),
					'cursus_inschrijfkosten' => number_format_i18n( $this->aantal * $this->cursus->inschrijfkosten, 2 ),
					'cursus_aantal'          => $this->aantal,
					'cursus_opmerking'       => ( '' !== $this->opmerking ) ? 'De volgende opmerking heb je doorgegeven: ' . $this->opmerking : '',
					'cursus_link'            => '<a href="' . home_url( '/kleistad_cursus_betaling' ) .
													'?gid=' . $this->cursist_id .
													'&crss=' . $this->cursus->id .
													'&hsh=' . $this->controle() . '" >Kleistad pagina</a>',
				],
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
	 * @return string De redirect url ingeval van een ideal betaling.
	 */
	public function betalen( $bericht, $inschrijving ) {

		$betaling   = new \Kleistad\Betalen();
		$deelnemers = ( 1 === $this->aantal ) ? '1 cursist' : $this->aantal . ' cursisten';
		if ( $inschrijving && $this->gedeeld ) {
			return $betaling->order(
				$this->cursist_id,
				__CLASS__ . '-' . $this->code . '-inschrijving',
				$this->aantal * $this->cursus->inschrijfkosten,
				'Kleistad cursus ' . $this->code . ' inschrijfkosten voor ' . $deelnemers,
				$bericht
			);
		} else {
			return $betaling->order(
				$this->cursist_id,
				__CLASS__ . '-' . $this->code . '-cursus',
				$this->aantal * $this->cursus->cursuskosten,
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
		return hash( 'sha256', "KlEiStAd{$this->cursist_id}C{$this->cursus->id}cOnTrOlE" );
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        4.2.0
	 *
	 * @param array $parameters De parameters 0: cursus-id, 1: gebruiker-id, 2: startdatum, 3: type betaling.
	 * @param float $bedrag     Het betaalde bedrag, wordt hier niet gebruikt.
	 * @param bool  $betaald    Of er werkelijk betaald is.
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
			$cursisten = get_users(
				[
					'meta_key' => self::META_KEY,
					'fields'   => [ 'ID' ],
				]
			);

			foreach ( $cursisten as $cursist ) {
				$inschrijvingen = get_user_meta( $cursist->ID, self::META_KEY, true );
				if ( is_array( $inschrijvingen ) ) {
					krsort( $inschrijvingen );
					foreach ( $inschrijvingen as $cursus_id => $inschrijving ) {
						$arr[ $cursist->ID ][ $cursus_id ] = new \Kleistad\Inschrijving( $cursist->ID, $cursus_id );
						$arr[ $cursist->ID ][ $cursus_id ]->load( $inschrijving );
					}
				}
			}
		}
		return $arr;
	}
}