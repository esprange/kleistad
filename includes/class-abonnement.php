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

use WP_User;
use DateTime;
use DateTimeZone;

/**
 * Class abonnement, alle acties voor het aanmaken en beheren van abonnementen
 *
 * @property int    start_datum
 * @property int    start_eind_datum
 * @property string dag
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
	 * De beginwaarden van een abonnement.
	 *
	 * @access private
	 * @var array $default_data De standaard waarden bij het aanmaken van een abonnement.
	 */
	private $default_data = [
		'code'               => '',
		'datum'              => 0,
		'start_datum'        => 0,
		'start_eind_datum'   => 0,
		'dag'                => '',
		'opmerking'          => '',
		'soort'              => 'onbeperkt',
		'pauze_datum'        => 0,
		'eind_datum'         => 0,
		'herstart_datum'     => 0,
		'reguliere_datum'    => 0,
		'overbrugging_email' => 0,
		'extras'             => [],
		'factuur_maand'      => '',
		'historie'           => [],
	];

	/**
	 * De tekst voor een eventueel bericht in de email
	 *
	 * @access private
	 * @var string $bericht De tekst.
	 */
	private string $bericht = '';

	/**
	 * Constructor, maak het abonnement object .
	 *
	 * @param int $klant_id wp user id van de abonnee.
	 */
	public function __construct( int $klant_id ) {
		$this->klant_id              = $klant_id;
		$this->betalen               = new Betalen();
		$this->default_data['code']  = "A$klant_id";
		$this->default_data['datum'] = time();
		$abonnement                  = get_user_meta( $this->klant_id, self::META_KEY, true );
		$this->data                  = is_array( $abonnement ) ? wp_parse_args( $abonnement, $this->default_data ) : $this->default_data;
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) {
		return array_key_exists( $attribuut, $this->data ) ? $this->data[ $attribuut ] : null;
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( string $attribuut, $waarde ) : void {
		$this->data[ $attribuut ] = $waarde;
	}

	/**
	 * Verwijder het abonnement
	 */
	public function erase() {
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
		switch ( $this->artikel_type ) {
			case 'regulier':
			case 'pauze':
				return "$this->code-$this->artikel_type-" . date( 'Ym' );
			case 'start':
				return "$this->code-$this->artikel_type-" . date( 'Ymd' );
			default:
				return "$this->code-$this->artikel_type";
		}
	}

	/**
	 * Bepaalt of er automatisch betaalt wordt.
	 *
	 * @return bool
	 */
	public function betaalt_automatisch() : bool {
		return $this->betalen->heeft_mandaat( $this->klant_id );
	}

	/**
	 * Wijzig de betaalwijze van het abonnement naar sepa incasso.
	 *
	 * @return string|bool De redirect uri of false als de betaling niet lukt.
	 */
	public function start_incasso() {
		$this->log( 'gestart met automatisch betalen' );
		$this->save();
		$this->artikel_type = 'mandaat';
		return $this->doe_idealbetaling( 'Bedankt voor de betaling! De wijziging is verwerkt en er wordt een email verzonden met bevestiging', $this->geef_referentie() );
	}

	/**
	 * Wijzig de betaalwijze van het abonnement naar bank.
	 */
	public function stop_incasso() : bool {
		$this->betalen->verwijder_mandaat( $this->klant_id );
		$this->log( 'gestopt met automatisch betalen' );
		$this->save();
		if ( ! is_admin() ) {
			$this->bericht = 'Je gaat het abonnement voortaan per bank betalen';
			$this->verzend_email( '_gewijzigd' );
		}
		return true;
	}

	/**
	 * Maak de ideal betalingen.
	 *
	 * @param string $bericht  Te tonen melding als betaling gelukt.
	 * @param  string $referentie De referentie van het artikel.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect url of het is fout gegaan.
	 */
	public function doe_idealbetaling( string $bericht, string $referentie, float $openstaand = null ) {
		switch ( $this->artikel_type ) {
			case 'start':
				$vanaf      = strftime( '%d-%m-%Y', $this->start_datum );
				$tot        = strftime( '%d-%m-%Y', $this->start_eind_datum );
				$vermelding = " vanaf $vanaf tot $tot";
				$mandaat    = false;
				break;
			case 'overbrugging':
				$vanaf      = strftime( '%d-%m-%Y', strtotime( '+1 day', $this->start_eind_datum ) );
				$tot        = strftime( '%d-%m-%Y', strtotime( '-1 day', $this->reguliere_datum ) );
				$vermelding = " vanaf $vanaf tot $tot";
				$mandaat    = true;
				break;
			case 'mandaat':
				$vermelding = ' machtiging tot sepa-incasso';
				$mandaat    = true;
				break;
			default: // Regulier of pauze, echter dan is artikel type YYMM.
				$vermelding = '';
				$mandaat    = false;
		}
		return $this->betalen->order(
			$this->klant_id,
			$referentie,
			$openstaand ?? $this->geef_bedrag( "#{$this->artikel_type}" ),
			"Kleistad abonnement {$this->code}$vermelding",
			$bericht,
			$mandaat
		);
	}

	/**
	 * Maak de sepa incasso betalingen.
	 */
	private function doe_sepa_incasso() {
		$bedrag = $this->geef_bedrag( "#{$this->artikel_type}" );
		if ( 0.0 < $bedrag ) {
			return $this->betalen->eenmalig(
				$this->klant_id,
				$this->geef_referentie(),
				$bedrag,
				"Kleistad abonnement {$this->code} " . strftime( '%B %Y', strtotime( 'today' ) ),
			);
		}
		return '';
	}

	/**
	 * Verzenden van de email.
	 *
	 * @param  string $type      Welke email er verstuurd moet worden.
	 * @param  string $factuur   Bij de sluiten factuur.
	 * @return boolean succes of falen van verzending email.
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
					'abonnement_dag'          => $this->dag,
					'abonnement_opmerking'    => empty( $this->opmerking ) ? '' : "De volgende opmerking heb je doorgegeven: $this->opmerking ",
					'abonnement_wijziging'    => $this->bericht,
					'abonnement_extras'       => count( $this->extras ) ? 'Je hebt de volgende extras gekozen: ' . $this->geef_extras_tekst() : '',
					'abonnement_startgeld'    => number_format_i18n( $this->geef_bedrag( '#start' ), 2 ),
					'abonnement_maandgeld'    => number_format_i18n( $this->geef_bedrag( '#regulier' ), 2 ),
					'abonnement_overbrugging' => number_format_i18n( $this->geef_bedrag( '#overbrugging' ), 2 ),
					'abonnement_link'         => $this->betaal_link,
				],
			]
		);
	}

	/**
	 * Pauzeer het abonnement per pauze datum.
	 *
	 * @param int $pauze_datum    Pauzedatum.
	 * @param int $herstart_datum Herstartdatum.
	 */
	public function pauzeren( int $pauze_datum, int $herstart_datum ) : bool {
		$thans_gepauzeerd     = $this->is_gepauzeerd();
		$this->pauze_datum    = $pauze_datum;
		$this->herstart_datum = $herstart_datum;
		$pauze_datum_str      = strftime( '%d-%m-%Y', $this->pauze_datum );
		$herstart_datum_str   = strftime( '%d-%m-%Y', $this->herstart_datum );
		$this->log( "gepauzeerd per $pauze_datum_str en hervat per $herstart_datum_str" );
		$this->save();
		$this->bericht     = ( $thans_gepauzeerd ) ?
			"Je hebt aangegeven dat je abonnement, dat nu gepauzeerd is, hervat wordt per $herstart_datum_str"
			:
			$this->bericht = "Je pauzeert het abonnement per $pauze_datum_str en hervat het per $herstart_datum_str";
		if ( ! is_admin() ) {
			$this->verzend_email( '_gewijzigd' );
		}
		return true;
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
	 * @param  boolean $uitgebreid Uitgebreide tekst of korte tekst.
	 * @return string De status tekst.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function geef_statustekst( bool $uitgebreid ) : string {
		$vandaag = strtotime( 'today' );
		if ( $this->is_geannuleerd() ) {
			return $uitgebreid ? 'gestopt sinds ' . strftime( '%x', $this->eind_datum ) : 'gestopt';
		} elseif ( $this->is_gepauzeerd() ) {
			return $uitgebreid ? 'gepauzeerd sinds ' . strftime( '%x', $this->pauze_datum ) . ' tot ' . strftime( '%x', $this->herstart_datum ) : 'gepauzeerd';
		} elseif ( $vandaag > $this->start_datum ) {
			if ( $vandaag < $this->pauze_datum ) {
				return $uitgebreid ? 'pauze gepland per ' . strftime( '%x', $this->pauze_datum ) . ' tot ' . strftime( '%x', $this->herstart_datum ) : 'pauze gepland';
			} elseif ( $vandaag <= $this->eind_datum ) {
				return $uitgebreid ? 'stop gepland per ' . strftime( '%x', $this->eind_datum ) : 'stop gepland';
			} elseif ( $vandaag < $this->start_eind_datum ) {
				return $uitgebreid ? 'gestart sinds ' . strftime( '%x', $this->start_datum ) : 'gestart';
			} elseif ( $vandaag < $this->reguliere_datum ) {
				return 'overbrugging';
			}
			return $uitgebreid ? 'actief sinds ' . strftime( '%x', $this->start_datum ) : 'actief';
		}
		return $uitgebreid ? 'aangemeld per ' . strftime( '%x', $this->datum ) . ', start per ' . strftime( '%x', $this->start_datum ) : 'aangemeld';
	}

	/**
	 * Start het abonnement per datum.
	 *
	 * @param int    $start_datum Startdatum.
	 * @param string $soort       Beperkt of onbeperkt.
	 * @param string $dag         De dagnaam bij beperkt.
	 * @param string $opmerking   De opmerking.
	 */
	public function starten( $start_datum, $soort, $dag, $opmerking ) {
		$this->data             = $this->default_data;
		$this->soort            = $soort;
		$this->opmerking        = $opmerking;
		$this->start_datum      = $start_datum;
		$this->start_eind_datum = strtotime( '+3 month', $start_datum );
		$this->reguliere_datum  = strtotime( 'first day of +4 month ', $start_datum );
		$this->dag              = $dag;
		$this->artikel_type     = 'start';
		$this->autoriseer( true );
		$this->save();
	}

	/**
	 * Stop het abonnement per datum.
	 *
	 * @param int $eind_datum Einddatum.
	 */
	public function stoppen( int $eind_datum ) : bool {
		$this->eind_datum = $eind_datum;
		$eind_datum_str   = strftime( '%d-%m-%Y', $this->eind_datum );
		$this->betalen->verwijder_mandaat( $this->klant_id );
		$this->log( "gestopt per $eind_datum_str" );
		$this->bericht = "Je hebt het abonnement per $eind_datum_str beëindigd.";
		$this->save();
		if ( ! is_admin() ) {
			$this->verzend_email( '_gewijzigd' );
		}
		return true;
	}

	/**
	 * Doe acties na betaling van abonnementen. Wordt aangeroepen vanuit de betaal callback.
	 *
	 * @param int    $order_id   Het eventueel al bestaande order id.
	 * @param float  $bedrag     Het betaalde bedrag.
	 * @param bool   $betaald    Of er werkelijk betaald is.
	 * @param string $type       Het type betaling.
	 * @param string $transactie_id De betalings id.
	 */
	public function verwerk_betaling( int $order_id, float $bedrag, bool $betaald, string $type, string $transactie_id = '' ) {
		if ( $betaald ) {
			if ( $order_id ) {
				/**
				 * Er bestaat blijkbaar al een order voor deze referentie. Het komt dan vanaf een email betaal link of incasso of betaling per bank.
				 */
				if ( 0 < $bedrag ) {
					if ( 'ideal' === $type ) {
						$this->ontvang_order( $order_id, $bedrag, $transactie_id );
						$this->verzend_email( '_ideal_betaald' );
						return;
					}
					if ( 'directdebit' === $type ) { // Als het een incasso is dan wordt er ook een factuur aangemaakt.
						$this->verzend_email( '_regulier_incasso', $this->ontvang_order( $order_id, $bedrag, $transactie_id, true ) );
						return;
					}
					// Anders is het een bank betaling en daarvoor wordt geen bedank email verzonden.
					$this->ontvang_order( $order_id, $bedrag, $transactie_id );
					return;
				}
				// Anders is het een terugstorting.
				$this->ontvang_order( $order_id, $bedrag, $transactie_id );
				return;
			}
			if ( 'mandaat' === $this->artikel_type ) {
				/**
				 * Bij een mandaat ( 1 eurocent ) hoeven we geen factuur te sturen en is er dus geen order aangemaakt.
				 */
				$this->bericht = 'Je hebt Kleistad toestemming gegeven voor een maandelijkse incasso van het abonnement';
				$this->verzend_email( '_gewijzigd' );
				return;
			}
			if ( 'start' === $this->artikel_type ) {
				/**
				 * Bij een start en nog niet bestaande order moet dit wel afkomstig zijn van het invullen van
				 * een inschrijving formulier.
				 */
				$this->verzend_email( '_start_ideal', $this->bestel_order( $bedrag, $this->start_datum, '', $transactie_id ) );
				return;
			}
		} elseif ( 'directdebit' === $type ) {
			/**
			 * Als het een incasso betreft die gefaald is dan is het bedrag 0 en moet de factuur alsnog aangemaakt worden.
			 */
			$this->verzend_email( '_regulier_mislukt', $this->ontvang_order( $order_id, 0, $transactie_id, true ) );
		}
	}

	/**
	 * Wijzig het abonnement per datum.
	 *
	 * @param int    $wijzig_datum Wijzigdatum.
	 * @param string $type         Soort wijziging: soort abonnement of de extras.
	 * @param mixed  $soort        Beperkt/onbeperkt wijziging of de extras.
	 * @param string $dag          Dag voor beperkt abonnement.
	 */
	public function wijzigen( int $wijzig_datum, string $type, $soort, string $dag = '' ) : bool {
		$gewijzigd        = false;
		$wijzig_datum_str = strftime( '%d-%m-%Y', $wijzig_datum );
		switch ( $type ) {
			case 'soort':
				$gewijzigd      = $this->soort != $soort || $this->dag != $dag; // phpcs:ignore
				$this->soort = $soort;
				$this->dag   = $dag;
				$this->log( "gewijzigd per $wijzig_datum_str naar $soort $dag" );
				$this->bericht = "Je hebt het abonnement per $wijzig_datum_str gewijzigd naar {$this->soort} " .
					( 'beperkt' === $this->soort ? ' (' . $this->dag . ')' : '' );
				break;
			case 'extras':
				$gewijzigd    = $this->extras != $soort; // phpcs:ignore
				$this->extras = $soort;
				$soort_str    = ! is_null( $soort ) ? ( 'gebruik maken van ' . implode( ', ', $soort ) ) : 'geen extras meer gebruiken';
				$this->log( "extras gewijzigd per $wijzig_datum_str naar $soort_str" );
				$this->bericht = "Je gaat voortaan per $wijzig_datum_str $soort_str";
				break;
			default:
				$this->bericht = '';
		}
		if ( $gewijzigd ) {
			$this->save();
			$this->verzend_email( '_gewijzigd' );
		}
		return true;
	}

	/**
	 * Geef de factuurregels door.
	 *
	 * @return array De regels.
	 */
	protected function geef_factuurregels() : array {
		switch ( $this->artikel_type ) {
			case 'start':
				$vanaf  = strftime( '%d-%m-%Y', $this->start_datum );
				$tot    = strftime( '%d-%m-%Y', $this->start_eind_datum );
				$basis  = "{$this->soort} abonnement {$this->code} vanaf $vanaf tot $tot";
				$aantal = 3;
				break;
			case 'overbrugging':
				$vanaf  = strftime( '%d-%m-%Y', strtotime( '+1 day', $this->start_eind_datum ) );
				$tot    = strftime( '%d-%m-%Y', strtotime( '-1 day', $this->reguliere_datum ) );
				$basis  = "{$this->soort} abonnement {$this->code} vanaf $vanaf tot $tot";
				$aantal = $this->geef_overbrugging_fractie();
				break;
			case 'regulier':
				$periode = strftime( '%B %Y', strtotime( 'today' ) );
				$basis   = "{$this->soort} abonnement {$this->code} periode $periode";
				$aantal  = 1;
				break;
			case 'pauze':
				$periode = strftime( '%B %Y', strtotime( 'today' ) );
				$basis   = "{$this->soort} abonnement {$this->code} periode $periode (deels gepauzeerd)";
				$aantal  = $this->geef_pauze_fractie();
				break;
			default:
				$basis  = '';
				$aantal = 0;
		}
		$orderregels = [];
		if ( 0 < $aantal ) {
			$orderregels[] = new Orderregel( $basis, $aantal, $this->geef_bedrag() );
			foreach ( $this->extras as $extra ) {
				$orderregels[] = new Orderregel( "gebruik $extra", $aantal, $this->geef_bedrag_extra( $extra ) );
			}
		}
		return $orderregels;
	}

	/**
	 * Autoriseer de abonnee zodat deze de oven reservering mag doen en toegang tot leden pagina's krijgt.
	 *
	 * @param boolean $valid Als true, geef de autorisatie, als false haal de autorisatie weg.
	 */
	public function autoriseer( bool $valid ) {
		$abonnee = new WP_User( $this->klant_id );
		if ( is_super_admin( $this->klant_id ) ) {
			// Voorkom dat de admin enige rol kwijtraakt.
			return;
		}
		$abonnee->add_cap( LID, $valid );
	}

	/**
	 * Bereken de prijs van een extra.
	 *
	 * @param string $extra het extra element.
	 * @return float Het maandbedrag van de extra.
	 */
	private function geef_bedrag_extra( string $extra ) : float {
		$options = opties();
		foreach ( $options['extra'] as $extra_optie ) {
			if ( $extra === $extra_optie['naam'] ) {
				return (float) $extra_optie['prijs'];
			}
		}
		return 0.0;
	}

	/**
	 * Bereken de maandelijkse kosten, de overbrugging, of het startbedrag.
	 *
	 * @param  string $type Welk bedrag gevraagd wordt, standaard het maandbedrag.
	 * @return float Het maandbedrag.
	 */
	private function geef_bedrag( string $type = '' ) : float {
		$options       = opties();
		$basis_bedrag  = (float) $options[ $this->soort . '_abonnement' ];
		$extras_bedrag = 0.0;
		foreach ( $this->extras as $extra ) {
			$extras_bedrag += $this->geef_bedrag_extra( $extra );
		}
		switch ( $type ) {
			case '#mandaat':
				return 0.01;
			case '#start':
				return 3 * $basis_bedrag;
			case '#overbrugging':
				return $this->geef_overbrugging_fractie() * $basis_bedrag;
			case '#regulier':
				return $basis_bedrag + $extras_bedrag;
			case '#pauze':
				return $this->geef_pauze_fractie() * ( $basis_bedrag + $extras_bedrag );
			default:
				return $basis_bedrag;
		};
	}

	/**
	 * Geef de fractie terug wat er betaald moet worden in de overbruggingsmaand.
	 *
	 * @return float De fractie.
	 */
	private function geef_overbrugging_fractie() : float {
		$overbrugging_datum = strtotime( '+1 day', $this->start_eind_datum );
		$aantal_dagen       = intval( ( $this->reguliere_datum - $overbrugging_datum ) / ( DAY_IN_SECONDS ) );
		return ( 0 < $aantal_dagen ) ? round( $aantal_dagen / intval( date( 't', $this->start_eind_datum ) ), 2 ) : 0.00;
	}

	/**
	 * Geef de fractie terug wat er betaald moet worden in de huidige pauzemaand.
	 *
	 * @return float De fractie.
	 */
	private function geef_pauze_fractie() : float {
		$aantal_dagen = intval( date( 't' ) );
		$maand_start  = strtotime( 'first day of this month 00:00' );
		$maand_eind   = strtotime( 'last day of this month 00:00' );
		$begin_dagen  = $this->pauze_datum < $maand_start ? 0 : intval( date( 'd', $this->pauze_datum ) ) - 1;
		$eind_dagen   = $this->herstart_datum > $maand_eind ? 0 : $aantal_dagen - intval( date( 'd', $this->herstart_datum ) ) + 1;
		return round( ( $begin_dagen + $eind_dagen ) / $aantal_dagen, 2 );
	}

	/**
	 * Maak een tekst met de extras inclusief vermelding van de prijs per maand.
	 */
	private function geef_extras_tekst() : string {
		$lijst = [];
		foreach ( $this->extras as $extra ) {
			$lijst[] = $extra . ' ( € ' . number_format_i18n( $this->geef_bedrag_extra( $extra ), 2 ) . ' p.m.)';
		}
		return implode( ', ', $lijst );
	}

	/**
	 * Factureer de maand
	 *
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 */
	public function factureer() {
		$vandaag        = strtotime( 'today' );
		$factuur_maand  = (int) date( 'Ym', $vandaag );
		$volgende_maand = strtotime( 'first day of next month 00:00' );
		$deze_maand     = strtotime( 'first day of this month 00:00' );
		if ( $this->factuur_maand >= $factuur_maand ||
		( $this->herstart_datum >= $volgende_maand && $this->pauze_datum <= $deze_maand )
		) {
			return;
		}
		$betalen = new Betalen();
		// Als het abonnement in deze maand wordt gepauzeerd of herstart dan is er sprake van een gedeeltelijke .
		$this->artikel_type = ( ( $this->herstart_datum > $deze_maand && $this->herstart_datum < $volgende_maand ) ||
			( $this->pauze_datum >= $deze_maand && $this->pauze_datum < $volgende_maand ) ) ? 'pauze' : 'regulier';
		if ( $betalen->heeft_mandaat( $this->klant_id ) ) {
			$this->bestel_order( 0.0, strtotime( '+14 days 0:00' ), '', $this->doe_sepa_incasso(), false );
		} else {
			$this->verzend_email( '_regulier_bank', $this->bestel_order( 0.0, strtotime( '+14 days 0:00' ) ) );
		}
		$this->factuur_maand = $factuur_maand;
		$this->save();
	}

	/**
	 * Dagelijkse job
	 */
	public static function doe_dagelijks() {
		$vandaag      = strtotime( 'today' );
		$abonnementen = new Abonnementen();
		foreach ( $abonnementen as $abonnement ) {
			if ( $abonnement->is_geannuleerd() || $vandaag < $abonnement->start_datum ) {
				// Gestopte abonnementen en abonnementen die nog moeten starten hebben geen actie nodig.
				continue;
			} elseif ( $abonnement->eind_datum && $vandaag >= $abonnement->eind_datum ) {
				// Abonnementen waarvan de einddatum verstreken is worden gestopt.
				$abonnement->autoriseer( false );
				$abonnement->save();
				continue;
			}
			$abonnement->autoriseer( true );
			// Abonnementen waarvan de starttermijn over 1 week verstrijkt krijgen de overbrugging email en factuur, mits er nog geen einddatum ingevuld is.
			if ( $vandaag < $abonnement->reguliere_datum ) {
				if ( $vandaag >= strtotime( '-7 days', $abonnement->start_eind_datum ) && ! $abonnement->eind_datum && ! $abonnement->overbrugging_email ) {
					$abonnement->artikel_type = 'overbrugging';
					$abonnement->verzend_email( '_vervolg', $abonnement->bestel_order( 0.0, strtotime( '+7 days 0:00' ) ) );
					$abonnement->overbrugging_email = true;
					$abonnement->save();
				}
				continue; // Meer actie is niet nodig. Abonnee zit nog in startperiode of overbrugging.
			}
			// Hierna wordt er niets meer aan het abonnement aangepast, nu nog factureren indien nodig.
			$abonnement->factureer();
		}
	}

	/**
	 * Helper functie, om een handeling toe te voegen
	 *
	 * @param string $tekst De handeling.
	 */
	private function log( string $tekst ) : void {
		array_push( $this->historie, strftime( '%c' ) . " $tekst" );
	}
}
