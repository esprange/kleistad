<?php
/**
 * Definieer de abonnement class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Class abonnement, alle acties voor het aanmaken en beheren van abonnementen
 *
 * @property int    start_datum
 * @property int    start_eind_datum
 * @property string opmerking
 * @property string soort
 * @property int    pauze_datum
 * @property int    eind_datum
 * @property int    herstart_datum
 * @property int    reguliere_datum
 * @property bool   overbrugging_email
 * @property array  extras
 * @property int    factuur_maand
 * @property array historie
 */
class Abonnement extends Artikel {

	public const DEFINITIE       = [
		'prefix' => 'A',
		'naam'   => 'abonnement',
		'pcount' => 1,
	];
	public const META_KEY        = 'kleistad_abonnement_v2';
	public const MAX_PAUZE_WEKEN = 9;
	public const MIN_PAUZE_WEKEN = 2;
	public const START_MAANDEN   = 3;
	private const EMAIL_SUBJECT  = [
		'_gewijzigd'        => 'Wijziging abonnement',
		'_ideal_betaald'    => 'Betaling abonnement',
		'_regulier_bank'    => 'Betaling abonnement per bankstorting',
		'_regulier_incasso' => 'Betaling abonnement per incasso',
		'_regulier_mislukt' => 'Betaling abonnement per incasso mislukt',
		'_start_bank'       => 'Welkom bij Kleistad',
		'_start_ideal'      => 'Welkom bij Kleistad',
		'_vervolg'          => 'Verlenging abonnement',
	];

	/**
	 * Het Actie object
	 *
	 * @var AbonnementActie $actie De acties.
	 */
	public AbonnementActie $actie;

	/**
	 * De tekst voor een eventueel bericht in de email
	 *
	 * @var string $bericht De tekst.
	 */
	public string $bericht = '';

	/**
	 * Constructor, maak het abonnement object .
	 *
	 * @param int $klant_id wp user id van de abonnee.
	 */
	public function __construct( int $klant_id ) {
		$default_data   = [
			'code'               => "A$klant_id",
			'datum'              => time(),
			'start_datum'        => 0,
			'start_eind_datum'   => 0,
			'opmerking'          => '',
			'soort'              => 'onbeperkt',
			'pauze_datum'        => 0,
			'eind_datum'         => 0,
			'herstart_datum'     => 0,
			'reguliere_datum'    => 0,
			'overbrugging_email' => 0,
			'extras'             => [],
			'factuur_maand'      => 0,
			'historie'           => [],
		];
		$this->klant_id = $klant_id;
		$abonnement     = get_user_meta( $this->klant_id, self::META_KEY, true );
		$this->data     = is_array( $abonnement ) ? wp_parse_args( $abonnement, $default_data ) : $default_data;
		$this->actie    = new AbonnementActie( $this );
		$this->betaling = new AbonnementBetaling( $this );
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) : mixed {
		return array_key_exists( $attribuut, $this->data ) ? $this->data[ $attribuut ] : null;
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( string $attribuut, mixed $waarde ) : void {
		$this->data[ $attribuut ] = $waarde;
	}

	/**
	 * Verwijder het abonnement
	 */
	public function erase() {
		$this->actie->autoriseer( false );
		delete_user_meta( $this->klant_id, self::META_KEY );
	}

	/**
	 * Bepaal of er gepauzeerd is.
	 *
	 * @return bool
	 */
	public function is_gepauzeerd() : bool {
		$vandaag = strtotime( 'today' );
		return $vandaag < $this->herstart_datum && $vandaag >= $this->pauze_datum;
	}

	/**
	 * Bepaal of er geannuleerd is.
	 *
	 * @return bool
	 */
	public function is_geannuleerd() : bool {
		$vandaag = strtotime( 'today' );
		return $this->eind_datum && $vandaag >= $this->eind_datum;
	}

	/**
	 * Geef de referentie terug.
	 *
	 * @return string
	 */
	public function geef_referentie() : string {
		if ( str_contains( 'regulier,pauze,start', $this->artikel_type ) ) {
			return "$this->code-$this->artikel_type-" . date( 'Ym' );
		}
		return "$this->code-$this->artikel_type";
	}

	/**
	 * Geef de fractie terug wat er betaald moet worden in de overbruggingsmaand.
	 *
	 * @return float De fractie.
	 */
	public function geef_overbrugging_fractie() : float {
		$overbrugging_datum = strtotime( '+1 day', $this->start_eind_datum );
		$aantal_dagen       = intval( ( $this->reguliere_datum - $overbrugging_datum ) / ( DAY_IN_SECONDS ) );
		return ( 0 < $aantal_dagen ) ? round( $aantal_dagen / intval( date( 't', $this->start_eind_datum ) ), 2 ) : 0.00;
	}

	/**
	 * Geef de fractie terug wat er betaald moet worden in de huidige pauzemaand.
	 *
	 * @return float De fractie.
	 */
	public function geef_pauze_fractie() : float {
		$aantal_dagen = intval( date( 't' ) );
		$maand_start  = strtotime( 'first day of this month 00:00' );
		$maand_eind   = strtotime( 'last day of this month 00:00' );
		$begin_dagen  = $this->pauze_datum < $maand_start ? 0 : intval( date( 'd', $this->pauze_datum ) ) - 1;
		$eind_dagen   = $this->herstart_datum > $maand_eind ? 0 : $aantal_dagen - intval( date( 'd', $this->herstart_datum ) ) + 1;
		return round( ( $begin_dagen + $eind_dagen ) / $aantal_dagen, 2 );
	}

	/**
	 * Bewaar de data als user meta in de database.
	 */
	public function save() {
		update_user_meta( $this->klant_id, self::META_KEY, $this->data );
	}

	/**
	 * Geef de status van het abonnement als een tekst terug.
	 *
	 * @param  bool $uitgebreid Uitgebreide tekst of korte tekst.
	 * @return string De status tekst.
	 */
	public function geef_statustekst( bool $uitgebreid ) : string {
		return $uitgebreid ? $this->geef_status_uitgebreid() : $this->geef_status_kort();
	}

	/**
	 * Geef de factuurregels door.
	 *
	 * @return Orderregels De regels.
	 */
	public function geef_factuurregels() : Orderregels {
		$betaalinfo  = [
			'start'        => [
				'info'   => sprintf(
					'%s abonnement %s vanaf %s tot %s',
					$this->soort,
					$this->code,
					strftime( '%d-%m-%Y', $this->start_datum ),
					strftime( '%d-%m-%Y', $this->start_eind_datum )
				),
				'aantal' => 3,
			],
			'overbrugging' => [
				'info'   => sprintf(
					'%s abonnement %s vanaf %s tot %s',
					$this->soort,
					$this->code,
					strftime( '%d-%m-%Y', strtotime( '+1 day', $this->start_eind_datum ) ),
					strftime( '%d-%m-%Y', strtotime( '-1 day', $this->reguliere_datum ) )
				),
				'aantal' => $this->geef_overbrugging_fractie(),
			],
			'regulier'     => [
				'info'   => sprintf(
					'%s abonnement %s periode %s',
					$this->soort,
					$this->code,
					strftime( '%B %Y', strtotime( 'today' ) )
				),
				'aantal' => 1,
			],
			'pauze'        => [
				'info'   => sprintf(
					'%s abonnement %s periode %s (deels gepauzeerd)',
					$this->soort,
					$this->code,
					strftime( '%B %Y', strtotime( 'today' ) )
				),
				'aantal' => $this->geef_pauze_fractie(),
			],
		];
		$orderregels = new Orderregels();
		if ( isset( $betaalinfo[ $this->artikel_type ] ) ) {
			$aantal = $betaalinfo[ $this->artikel_type ]['aantal'];
			if ( $aantal ) {
				$orderregels->toevoegen( new Orderregel( $betaalinfo[ $this->artikel_type ]['info'], $aantal, $this->betaling->geef_bedrag() ) );
				foreach ( $this->extras as $extra ) {
					$orderregels->toevoegen( new Orderregel( "gebruik $extra", $aantal, $this->betaling->geef_bedrag_extra( $extra ) ) );
				}
			}
		}
		return $orderregels;
	}

	/**
	 * Verzenden van de email.
	 *
	 * @param  string $type      Welke email er verstuurd moet worden.
	 * @param  string $factuur   Bij de sluiten factuur.
	 * @return bool succes of falen van verzending email.
	 */
	public function verzend_email( string $type, string $factuur = '' ) : bool {
		$abonnee = get_userdata( $this->klant_id );
		$emailer = new Email();
		return $emailer->send(
			[
				'to'          => "$abonnee->display_name <$abonnee->user_email>",
				'subject'     => self::EMAIL_SUBJECT[ $type ],
				'slug'        => 'abonnement' . $type,
				'attachments' => $factuur ?: [],
				'parameters'  =>
				[
					'voornaam'                => $abonnee->first_name,
					'achternaam'              => $abonnee->last_name,
					'loginnaam'               => $abonnee->user_login,
					'start_datum'             => strftime( '%d-%m-%Y', $this->start_datum ),
					'pauze_datum'             => $this->pauze_datum ? strftime( '%d-%m-%Y', $this->pauze_datum ) : '',
					'eind_datum'              => $this->eind_datum ? strftime( '%d-%m-%Y', $this->eind_datum ) : '',
					'herstart_datum'          => $this->herstart_datum ? strftime( '%d-%m-%Y', $this->herstart_datum ) : '',
					'abonnement'              => $this->soort,
					'abonnement_code'         => $this->code,
					'abonnement_opmerking'    => empty( $this->opmerking ) ? '' : "De volgende opmerking heb je doorgegeven: $this->opmerking ",
					'abonnement_wijziging'    => $this->bericht,
					'abonnement_extras'       => count( $this->extras ) ? 'Je hebt de volgende extras gekozen: ' . $this->geef_extras_tekst() : '',
					'abonnement_startgeld'    => number_format_i18n( $this->betaling->geef_bedrag( '#start' ), 2 ),
					'abonnement_maandgeld'    => number_format_i18n( $this->betaling->geef_bedrag( '#regulier' ), 2 ),
					'abonnement_overbrugging' => number_format_i18n( $this->betaling->geef_bedrag( '#overbrugging' ), 2 ),
					'abonnement_link'         => $this->maak_betaal_link(),
				],
			]
		);
	}

	/**
	 * Maak een tekst met de extras inclusief vermelding van de prijs per maand.
	 */
	private function geef_extras_tekst() : string {
		$lijst = [];
		foreach ( $this->extras as $extra ) {
			$lijst[] = $extra . ' ( € ' . number_format_i18n( $this->betaling->geef_bedrag_extra( $extra ), 2 ) . ' p.m.)';
		}
		return implode( ', ', $lijst );
	}

	/**
	 * Geef de lange status terug
	 *
	 * @return string
	 */
	private function geef_status_uitgebreid() : string {
		$vandaag = strtotime( 'today' );
		if ( $this->is_geannuleerd() ) {
			return 'gestopt sinds ' . strftime( '%x', $this->eind_datum );
		} elseif ( $this->is_gepauzeerd() ) {
			return 'gepauzeerd sinds ' . strftime( '%x', $this->pauze_datum ) . ' tot ' . strftime( '%x', $this->herstart_datum );
		} elseif ( $vandaag > $this->start_datum ) {
			if ( $vandaag < $this->pauze_datum ) {
				return 'pauze gepland per ' . strftime( '%x', $this->pauze_datum ) . ' tot ' . strftime( '%x', $this->herstart_datum );
			} elseif ( $vandaag <= $this->eind_datum ) {
				return 'stop gepland per ' . strftime( '%x', $this->eind_datum );
			} elseif ( $vandaag < $this->start_eind_datum ) {
				return 'gestart sinds ' . strftime( '%x', $this->start_datum );
			} elseif ( $vandaag < $this->reguliere_datum ) {
				return 'overbrugging';
			}
			return 'actief sinds ' . strftime( '%x', $this->start_datum );
		}
		return 'aangemeld per ' . strftime( '%x', $this->datum ) . ', start per ' . strftime( '%x', $this->start_datum );
	}

	/**
	 * Geef de korte status terug
	 *
	 * @return string
	 */
	private function geef_status_kort() : string {
		$vandaag = strtotime( 'today' );
		if ( $this->is_geannuleerd() ) {
			return 'gestopt';
		} elseif ( $this->is_gepauzeerd() ) {
			return 'gepauzeerd';
		} elseif ( $vandaag > $this->start_datum ) {
			if ( $vandaag < $this->pauze_datum ) {
				return 'pauze gepland';
			} elseif ( $vandaag <= $this->eind_datum ) {
				return 'stop gepland';
			} elseif ( $vandaag < $this->start_eind_datum ) {
				return 'gestart';
			} elseif ( $vandaag < $this->reguliere_datum ) {
				return 'overbrugging';
			}
			return 'actief';
		}
		return 'aangemeld';
	}
}
