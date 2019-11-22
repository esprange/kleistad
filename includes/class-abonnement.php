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
 * @property string code
 * @property int    datum
 * @property int    start_datum
 * @property string dag
 * @property bool   geannuleerd
 * @property string opmerking
 * @property string soort
 * @property int    pauze_datum
 * @property int    eind_datum
 * @property int    herstart_datum
 * @property int    driemaand_datum
 * @property int    reguliere_datum
 * @property int    incasso_datum
 * @property bool   gepauzeerd
 * @property bool   overbrugging_email
 * @property string subscriptie_id
 * @property array  extras
 */
class Abonnement extends Artikel {

	const META_KEY = 'kleistad_abonnement';

	/**
	 * De beginwaarden van een abonnement.
	 *
	 * @access private
	 * @var array $default_data De standaard waarden bij het aanmaken van een abonnement.
	 */
	private $default_data = [
		'code'               => '',
		'datum'              => '',
		'start_datum'        => '',
		'dag'                => '',
		'geannuleerd'        => 0,
		'opmerking'          => '',
		'soort'              => 'onbeperkt',
		'pauze_datum'        => '',
		'eind_datum'         => '',
		'herstart_datum'     => '',
		'incasso_datum'      => '',
		'gepauzeerd'         => 0,
		'overbrugging_email' => 0,
		'subscriptie_id'     => '',
		'extras'             => [],
	];

	/**
	 * Constructor, maak het abonnement object .
	 *
	 * @param int $klant_id wp user id van de abonnee.
	 */
	public function __construct( $klant_id ) {
		$this->klant_id              = $klant_id;
		$this->default_data['code']  = "A$klant_id";
		$this->default_data['datum'] = date( 'Y-m-d' );
		$abonnement                  = get_user_meta( $this->klant_id, self::META_KEY, true );
		$this->data                  = is_array( $abonnement ) ? wp_parse_args( $abonnement, $this->default_data ) : $this->default_data;
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( $attribuut ) {
		$start = getdate( strtotime( $this->data['start_datum'] ) );
		switch ( $attribuut ) {
			case 'datum':
			case 'start_datum':
			case 'pauze_datum':
			case 'eind_datum':
			case 'herstart_datum':
				return strtotime( $this->data[ $attribuut ] );
			case 'driemaand_datum':
				return mktime( 0, 0, 0, $start['mon'] + 3, $start['mday'], $start['year'] );
			case 'incasso_datum':
				return ( '' === $this->data[ $attribuut ] ) ? mktime( 0, 0, 0, $start['mon'] + 4, 1, $start['year'] ) : strtotime( $this->data[ $attribuut ] );
			case 'reguliere_datum':
				return mktime( 0, 0, 0, $start['mon'] + 4, 1, $start['year'] );
			case 'geannuleerd':
			case 'gepauzeerd':
			case 'overbrugging_email':
				return boolval( $this->data[ $attribuut ] );
			case 'dag':
				return 'beperkt' === $this->soort ? $this->data[ $attribuut ] : '';
			case 'extras':
				return $this->data['extras'] ?? [];
			default:
				return is_string( $this->data[ $attribuut ] ) ? htmlspecialchars_decode( $this->data[ $attribuut ] ) : $this->data[ $attribuut ];
		}
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'datum':
			case 'start_datum':
			case 'pauze_datum':
			case 'eind_datum':
			case 'herstart_datum':
			case 'incasso_datum':
				$this->data[ $attribuut ] = is_null( $waarde ) ? 0 : date( 'Y-m-d', $waarde );
				break;
			case 'geannuleerd':
			case 'gepauzeerd':
			case 'overbrugging_email':
				$this->data[ $attribuut ] = (int) $waarde;
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Geef de artikel naam.
	 *
	 * @return string
	 */
	public function artikel_naam() {
		return 'abonnement';
	}

	/**
	 * Geef de code terug.
	 *
	 * @return string
	 */
	public function code() {
		return $this->code;
	}

	/**
	 * Wijzig de betaalwijze van het abonnement per datum.
	 *
	 * @param int    $wijzig_datum Wijzigdatum.
	 * @param string $betaalwijze  Ideal of bankstorting.
	 * @param bool   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 * @return string|bool De redirect url ingeval van een ideal betaling.
	 */
	public function betaalwijze( $wijzig_datum, $betaalwijze, $admin = false ) {
		$betalen              = new \Kleistad\Betalen();
		$this->subscriptie_id = $betalen->annuleer( $this->klant_id, $this->subscriptie_id ); // Verwijder een eventuele bestaande subscriptie.

		if ( 'ideal' === $betaalwijze ) {
			// Doe een proefbetaling om het mandaat te verkrijgen. De wijzig datum is de 1e van de volgende maand.
			$this->incasso_datum = $wijzig_datum;
			$this->save();
			return $betalen->order(
				$this->klant_id,
				__CLASS__ . "-{$this->code}-betaalwijze_ideal",
				0.01,
				"Kleistad abonnement {$this->code} machtiging tot sepa-incasso",
				'Bedankt voor de betaling! De wijziging is verwerkt en er wordt een email verzonden met bevestiging',
				true
			);
		} else {
			$betalen->verwijder_mandaat( $this->klant_id );
			$this->subscriptie_id = '';
			$this->save();
			if ( ! $admin ) {
				$this->email( '_betaalwijze_bank' );
			}
			return true;
		}
	}

	/**
	 * Maak de ideal betalingen.
	 *
	 * @param string $bericht  Te tonen melding als betaling gelukt.
	 * @return string|bool De redirect url of het is fout gegaan.
	 */
	public function betalen( $bericht ) {
		$betalen = new \Kleistad\Betalen();
		switch ( $this->artikel_type ) {
			case 'start':
				$vanaf      = strftime( '%d-%m-%Y', $this->start_datum );
				$tot        = strftime( '%d-%m-%Y', $this->driemaand_datum );
				$vermelding = " vanaf $vanaf tot $tot";
				$mandaat    = false;
				break;
			case 'overbrugging':
				$vanaf      = strftime( '%d-%m-%Y', strtotime( '+1 day', $this->driemaand_datum ) );
				$tot        = strftime( '%d-%m-%Y', strtotime( '-1 day', $this->reguliere_datum ) );
				$vermelding = " vanaf $vanaf tot $tot";
				$mandaat    = true;
				break;
			default:
				$vermelding = '';
				$mandaat    = false;
		}
		return $betalen->order(
			$this->klant_id,
			__CLASS__ . "-{$this->code}-{$this->artikel_type}",
			$this->bedrag( $this->artikel_type ),
			"Kleistad abonnement {$this->code}$vermelding",
			$bericht,
			$mandaat
		);
	}

	/**
	 * Verzenden van de welkomst email.
	 *
	 * @param  string $type      Welke email er verstuurd moet worden.
	 * @param  string $wijziging Ingeval van een wijziging, de tekst die dit beschrijft.
	 * @param  string $factuur   Bij de sluiten factuur.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $wijziging = '', $factuur = '' ) {
		$abonnee = get_userdata( $this->klant_id );
		$emailer = new \Kleistad\Email();
		return $emailer->send(
			[
				'to'          => "$abonnee->display_name <$abonnee->user_email>",
				'subject'     => false !== strpos( $type, '_start' ) ? 'Welkom bij Kleistad' : 'Abonnement Kleistad',
				'slug'        => 'kleistad_email_abonnement' . $type,
				'attachments' => $factuur,
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
					'abonnement_opmerking'    => ( '' !== $this->opmerking ) ? 'De volgende opmerking heb je doorgegeven: ' . $this->opmerking : '',
					'abonnement_wijziging'    => $wijziging,
					'abonnement_reason'       => $wijziging,
					'abonnement_extras'       => count( $this->extras ) ? 'Je hebt de volgende extras gekozen: ' . $this->extras_lijst() : '',
					'abonnement_startgeld'    => number_format_i18n( $this->bedrag( 'start' ), 2 ),
					'abonnement_maandgeld'    => number_format_i18n( $this->bedrag( 'regulier' ), 2 ),
					'abonnement_overbrugging' => number_format_i18n( $this->bedrag( 'overbrugging' ), 2 ),
					'abonnement_link'         => $this->betaal_link(),
				],
			]
		);
	}

	/**
	 * Herstart het abonnement per datum.
	 *
	 * @param int  $herstart_datum Herstartdatum.
	 * @param bool $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function herstarten( $herstart_datum, $admin = false ) {
		$this->herstart_datum = $herstart_datum;
		$betalen              = new \Kleistad\Betalen();
		if ( $betalen->heeft_mandaat( $this->klant_id ) ) {
			$this->subscriptie_id = $this->herhaalbetalen( $herstart_datum );
		}
		$this->save();
		if ( ! $admin ) {
			$this->email(
				'_gewijzigd',
				'Je hebt het abonnement per ' . strftime( '%d-%m-%Y', $this->herstart_datum ) . ' herstart.'
			);
		}
		return true;
	}

	/**
	 * Controleer of er een incasso actief is
	 *
	 * @return bool Als true, dan is incasso actief.
	 */
	public function incasso_actief() {
		return \Kleistad\Betalen::actief( $this->klant_id, $this->subscriptie_id );
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
	 * Pauzeer het abonnement per pauze datum.
	 *
	 * @param int  $pauze_datum    Pauzedatum.
	 * @param int  $herstart_datum Herstartdatum.
	 * @param bool $admin          Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function pauzeren( $pauze_datum, $herstart_datum, $admin = false ) {
		$this->pauze_datum    = $pauze_datum;
		$this->herstart_datum = $herstart_datum;
		$betalen              = new \Kleistad\Betalen();
		$this->subscriptie_id = $betalen->annuleer( $this->klant_id, $this->subscriptie_id );
		if ( $betalen->heeft_mandaat( $this->klant_id ) ) {
			$this->subscriptie_id = $this->herhaalbetalen( $herstart_datum );
		}
		$this->save();
		if ( ! $admin ) {
			$this->email( '_gewijzigd', 'Je hebt het abonnement per ' . strftime( '%d-%m-%Y', $this->pauze_datum ) . ' gepauzeerd en start weer per ' . strftime( '%d-%m-%Y', $this->herstart_datum ) );
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
	 */
	public function status( $uitgebreid = false ) {
		$vandaag = strtotime( 'today' );
		if ( $this->geannuleerd ) {
			return $uitgebreid ? 'gestopt sinds ' . strftime( '%x', $this->eind_datum ) : 'gestopt';
		} elseif ( $this->gepauzeerd ) {
			return $uitgebreid ? 'gepauzeerd sinds ' . strftime( '%x', $this->pauze_datum ) . ' tot ' . strftime( '%x', $this->herstart_datum ) : 'gepauzeerd';
		} elseif ( $vandaag > $this->start_datum ) {
			if ( $vandaag < $this->pauze_datum ) {
				return $uitgebreid ? 'pauze gepland per ' . strftime( '%x', $this->pauze_datum ) . ' tot ' . strftime( '%x', $this->herstart_datum ) : 'pauze gepland';
			} elseif ( $vandaag <= $this->eind_datum ) {
				return $uitgebreid ? 'stop gepland per ' . strftime( '%x', $this->eind_datum ) : 'stop gepland';
			}
			return $uitgebreid ? 'actief sinds ' . strftime( '%x', $this->start_datum ) : 'actief';
		}
		return $uitgebreid ? 'aangemeld per ' . strftime( '%x', $this->datum ) . ', start per ' . strftime( '%x', $this->start_datum ) : 'aangemeld';
	}

	/**
	 * Stop het abonnement per datum.
	 *
	 * @param int  $eind_datum Einddatum.
	 * @param bool $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function stoppen( $eind_datum, $admin = false ) {
		$this->eind_datum = $eind_datum;
		$betalen          = new \Kleistad\Betalen();
		$betalen->verwijder_mandaat( $this->klant_id );
		$this->subscriptie_id = '';
		$this->save();
		if ( ! $admin ) {
			$this->email(
				'_gewijzigd',
				'Je hebt het abonnement per ' . strftime( '%d-%m-%Y', $this->eind_datum ) . ' beëindigd.'
			);
		}
		return true;
	}

	/**
	 * Wijzig het abonnement per datum.
	 *
	 * @param int    $wijzig_datum Wijzigdatum.
	 * @param string $type         Soort wijziging: soort abonnement of de extras.
	 * @param mixed  $soort        Beperkt/onbeperkt wijziging of de extras.
	 * @param string $dag          Dag voor beperkt abonnement.
	 * @param bool   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function wijzigen( $wijzig_datum, $type, $soort, $dag = '', $admin = false ) {
		$ongewijzigd = true;
		switch ( $type ) {
			case 'soort':
				$ongewijzigd = $this->soort == $soort || $this->dag == $dag; // phpcs:ignore
				$this->soort = $soort;
				$this->dag   = $dag;
				$bericht     = 'Je hebt het abonnement per ' . strftime( '%d-%m-%Y', $wijzig_datum ) . ' gewijzigd naar ' . $this->soort .
					( 'beperkt' === $this->soort ? ' (' . $this->dag . ')' : '' );
				break;
			case 'extras':
				$ongewijzigd  = $this->extras == $soort; // phpcs:ignore
				$this->extras = $soort;
				$bericht      = 'Je gaat voortaan per ' . strftime( '%d-%m-%Y', $wijzig_datum ) .
					( count( $soort ) ? ' gebruik maken van ' . implode( ', ', $soort ) : ' geen gebruik meer van extras' );
				break;
			default:
				$bericht = '';
		}
		if ( ! $ongewijzigd ) {
			$betalen              = new \Kleistad\Betalen();
			$this->subscriptie_id = $betalen->annuleer( $this->klant_id, $this->subscriptie_id );
			/**
			 * Een automatische incasso wordt alleen gewijzigd als de abonnee zelf de wijziging doet.
			 */
			if ( ! $admin && $betalen->heeft_mandaat( $this->klant_id ) ) {
				$this->subscriptie_id = $this->herhaalbetalen(
					( $wijzig_datum >= $this->pauze_datum && $wijzig_datum <= $this->herstart_datum ) ? $this->herstart_datum : $wijzig_datum
				);
			}
		}
		$this->save();
		if ( ! $admin ) {
			$this->email( '_gewijzigd', $bericht );
		}
		return true;
	}

	/**
	 * Geef de factuurregels door.
	 *
	 * @return array De regels.
	 */
	protected function factuurregels() {
		switch ( $this->artikel_type ) {
			case 'start':
				$vanaf = strftime( '%d-%m-%Y', $this->start_datum );
				$tot   = strftime( '%d-%m-%Y', $this->driemaand_datum );
				return [
					array_merge(
						$this->split_bedrag( $options[ $this->soort . '_abonnement' ] ),
						[
							'artikel' => "{$this->soort} abonnement {$this->code} vanaf $vanaf tot $tot",
							'aantal'  => 3,
						]
					),
				];
			case 'overbrugging':
				$vanaf = strftime( '%d-%m-%Y', strtotime( '+1 day', $this->driemaand_datum ) );
				$tot   = strftime( '%d-%m-%Y', strtotime( '-1 day', $this->reguliere_datum ) );
				return [
					array_merge(
						$this->split_bedrag( $this->bedrag( $this->artikel_type ) ),
						[
							'artikel' => "{$this->soort} abonnement {$this->code} vanaf $vanaf tot $tot",
							'aantal'  => 1,
						]
					),
				];
			case 'regulier':
				$periode = strftime( '%B %Y', strtotime( 'today' ) );
				$regels  = [
					array_merge(
						$this->split_bedrag( $options[ $this->soort . '_abonnement' ] ),
						[
							'artikel' => "{$this->soort} abonnement {$this->code} periode $periode",
							'aantal'  => 1,
						]
					),
				];
				foreach ( $this->extras as $extra ) {
					$regels[] =
						array_merge(
							$this->split_bedrag( $this->extra_bedrag( $extra ) ),
							[
								'artikel' => 'gebruik $extra',
								'aantal'  => 1,
							]
						);
				}
				return $regels;
		}
	}

	/**
	 * Autoriseer de abonnee zodat deze de oven reservering mag doen en toegang tot leden pagina's krijgt.
	 *
	 * @param boolean $valid Als true, geef de autorisatie, als false haal de autorisatie weg.
	 */
	private function autoriseer( $valid ) {
		$abonnee = new \WP_User( $this->klant_id );
		if ( is_super_admin( $this->klant_id ) ) {
			// Voorkom dat de admin enige rol kwijtraakt.
			return;
		}
		if ( $valid ) {
			$abonnee->add_cap( 'leden' );
			$abonnee->add_cap( \Kleistad\Roles::RESERVEER );
			// Alternatief is wellicht abonnee add of remove role subscriber.
		} else {
			$abonnee->remove_cap( 'leden' );
			$abonnee->remove_cap( \Kleistad\Roles::RESERVEER );
			$abonnee->remove_role( 'subscriber' );
		}
	}

	/**
	 * Bereken de maandelijkse kosten, de overbrugging, of het startbedrag.
	 *
	 * @param  mixed $soort Welk bedrag gevraagd wordt, standaard het maandbedrag.
	 * @return float Het maandbedrag.
	 */
	private function bedrag( $soort ) {
		$options = \Kleistad\Kleistad::get_options();
		$bedrag  = (float) $options[ $this->soort . '_abonnement' ];
		foreach ( $this->extras as $extra ) {
			$bedrag += $this->extra_bedrag( $extra );
		}
		switch ( $soort ) {
			case 'regulier':
				return $bedrag;
			case 'start':
				return 3 * (float) $options[ $this->soort . '_abonnement' ];
			case 'overbrugging':
				$overbrugging_datum = strtotime( '+1 day', $this->driemaand_datum );
				$aantal_dagen       = intval( ( $this->reguliere_datum - $overbrugging_datum ) / 60 * 60 * 24 );
				if ( 0 < $aantal_dagen ) {
					// De fractie is het aantal dagen tussen vandaag en reguliere betaling, gedeeld door het aantal dagen in de maand.
					$fractie = $aantal_dagen / intval( date( 't', $this->driemaand_datum ) );
					return (float) $options[ $this->soort . '_abonnement' ] * $fractie;
				} else {
					return 0.0;
				}
		}
	}

	/**
	 * Geef de prijs van een extra.
	 *
	 * @param string $extra De extra abonnements functie.
	 * @return array Het bedrag.
	 */
	private function extra_bedrag( $extra ) {
		$options = \Kleistad\Kleistad::get_options();
		foreach ( $options['extra'] as $extra_option ) {
			if ( $extra === $extra_option['naam'] ) {
				return (float) $extra_option['prijs'];
			}
		}
		return 0.0;
	}

	/**
	 * Maak een tekst met de extras inclusief vermelding van de prijs per maand.
	 */
	private function extras_lijst() {
		$lijst = [];
		foreach ( $this->extras as $extra ) {
			$lijst[] = $extra . ' ( € ' . number_format_i18n( $this->extra_bedrag( $extra ), 2 ) . ' p.m.)';
		}
		return implode( ', ', $lijst );
	}

	/**
	 * Start automatisch betalen per incasso datum.
	 *
	 * @param int $datum Datum waarop abonnement incasso gestart moet worden.
	 */
	private function herhaalbetalen( $datum ) {
		$betalen = new \Kleistad\Betalen();
		return $betalen->herhaalorder(
			$this->klant_id,
			$this->bedrag( 'regulier' ),
			"Kleistad abonnement {$this->code}",
			$datum
		);
	}

	/**
	 * Doe acties na betaling van abonnementen. Wordt aangeroepen vanuit de betaal callback.
	 *
	 * @param array  $parameters De parameters 0: gebruiker-id, 1: de melddatum.
	 * @param float  $bedrag     Geeft aan of het een eerste start of een herstart betreft.
	 * @param bool   $betaald    Of er werkelijk betaald is.
	 * @param string $reason     Eventuele tekst van de bank over het falen van een incasso.
	 */
	public static function callback( $parameters, $bedrag, $betaald, $reason = '' ) {
		$abonnement = new static( intval( $parameters[0] ) );
		if ( $betaald ) {
			switch ( $parameters[1] ) {
				case 'betaalwijze_ideal':
					// Een succesvolle betaling van een overbrugging.
					$abonnement->subscriptie_id = $abonnement->herhaalbetalen( max( $abonnement->herstart_datum, $abonnement->incasso_datum ) );
					$abonnement->save();
					$abonnement->email( '_betaalwijze_ideal' );
					break;
				case 'start':
					$abonnement->email( '_start_ideal', '', $abonnement->bestel_order( $bedrag, 'start' ) );
					break;
				case 'incasso':
					$abonnement->email( '_regulier', '', $abonnement->bestel_order( $bedrag, 'regulier' ) );
					break;
				case 'overbrugging':
					$abonnement->email( '_vervolg' );
			}
		} else {
			if ( 'incasso' === $parameters[1] ) {
				$abonnement->email( '_incasso_mislukt', $reason, $abonnement->bestel_order( 0.0, 'regulier' ) );
			}
		}
	}

	/**
	 * Dagelijkse job
	 */
	public static function dagelijks() {
		$vandaag       = strtotime( 'today' );
		$factuur_maand = (int) date( 'Ym', $vandaag );
		$factuur_vorig = get_option( 'kleistad_factuur' );
		$factureren    = ( false === $factuur_vorig || (int) $factuur_vorig < (int) $factuur_maand );

		$abonnementen = self::all();
		foreach ( $abonnementen as $klant_id => $abonnement ) {
			// Abonnementen waarvan de einddatum verstreken is worden gestopt, zonder verdere acties.
			$abonnement->geannuleerd = ( $abonnement->eind_datum && $vandaag >= $abonnement->eind_datum );
			$abonnement->save();
			if ( $abonnement->geannuleerd ) {
				$abonnement->autoriseer( false );
				continue; // Als geannuleerd, geen verdere actie nodig.
			}

			// Abonnementen zijn gepauzeerd als er het vandaag tussen de pauze en herstart datum is.
			$abonnement->gepauzeerd = ( $abonnement->pauze_datum && $vandaag >= $abonnement->pauze_datum && $vandaag < $abonnement->herstart_datum );
			$abonnement->save();
			if ( $vandaag < $abonnement->start_datum || $abonnement->gepauzeerd ) {
				continue; // Geen acties als nog niet gestart of gepauzeerd.
			}

			// Abonnementen waarvan de driemaanden termijn over 1 week verstrijkt krijgen de overbrugging email en factuur, mits er iets te betalen is, zonder verdere acties.
			if ( ! $abonnement->overbrugging_email && $vandaag >= strtotime( '-7 days', $abonnement->driemaand_datum ) ) {
				$abonnement->overbrugging_email = true;
				$abonnement->save();
				if ( 0.0 < $abonnement->bedrag( 'overbrugging' ) ) {
					$abonnement->email( '_vervolg', '', $abonnement->bestel_order( 0.0, 'overbrugging' ) ); // Alleen versturen als er werkelijk iets te betalen is.
				}
			}
			// Abonnementen die via de bank betaald worden krijgen een maand factuur als er gefactureerd moet worden.
			if ( $factureren && $vandaag >= $abonnement->reguliere_datum && ! $abonnement->incasso_actief() ) {
				$abonnement->email( '_regulier', $abonnement->bestel_order( 0.0, 'regulier' ) );
			}
		}
		// Verhoog het maandnummer van de facturatie.
		update_option( 'kleistad_factuur', $factuur_maand );
	}

	/**
	 * Return alle abonnementen
	 *
	 * @param string $search Optionele zoekterm.
	 * @return array abonnementen.
	 */
	public static function all( $search = '' ) {
		static $arr = null;
		if ( is_null( $arr ) ) {
			$arr      = [];
			$abonnees = get_users(
				[
					'meta_key' => self::META_KEY,
					'fields'   => [ 'ID' ],
					'search'   => '*' . $search . '*',
				]
			);
			foreach ( $abonnees as $abonnee ) {
				$abonnement          = get_user_meta( $abonnee->ID, self::META_KEY, true );
				$arr[ $abonnee->ID ] = new \Kleistad\Abonnement( $abonnee->ID );
				$arr[ $abonnee->ID ]->load( $abonnement );
			}
		}
		return $arr;
	}
}
