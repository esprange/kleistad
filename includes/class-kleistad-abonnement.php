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
 * @property string subscriptie_id
 * @property array  extras
 */
class Kleistad_Abonnement extends Kleistad_Entity {

	const META_KEY             = 'kleistad_abonnement';
	const BEDRAG_MAAND         = 1;
	const BEDRAG_OVERBRUGGING  = 2;
	const BEDRAG_START         = 3;
	const BEDRAG_BORG          = 4;
	const BEDRAG_START_EN_BORG = 5;

	/**
	 * Het abonnee id
	 *
	 * @since 4.0.87
	 * @access private
	 * @var int $abonnee_id Het wp user id van de abonnee.
	 */
	private $abonnee_id;

	/**
	 * De beginwaarden van een abonnement.
	 *
	 * @since 4.3.0
	 * @access private
	 * @var array $_default_data de standaard waarden bij het aanmaken van een abonnement.
	 */
	private $default_data = [
		'code'           => '',
		'datum'          => '',
		'start_datum'    => '',
		'dag'            => '',
		'geannuleerd'    => 0,
		'opmerking'      => '',
		'soort'          => 'onbeperkt',
		'pauze_datum'    => '',
		'eind_datum'     => '',
		'herstart_datum' => '',
		'incasso_datum'  => '',
		'gepauzeerd'     => 0,
		'subscriptie_id' => '',
		'extras'         => [],
	];

	/**
	 * Constructor
	 *
	 * Maak het abonnement object .
	 *
	 * @since 4.0.87
	 *
	 * @param int $abonnee_id wp user id van de abonnee.
	 */
	public function __construct( $abonnee_id ) {
		$this->abonnee_id            = $abonnee_id;
		$this->default_data['code']  = "A$abonnee_id";
		$this->default_data['datum'] = date( 'Y-m-d' );
		$abonnement                  = get_user_meta( $this->abonnee_id, self::META_KEY, true );
		$this->data                  = is_array( $abonnement ) ? wp_parse_args( $abonnement, $this->default_data ) : $this->default_data;
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
				return boolval( $this->data[ $attribuut ] );
			case 'dag':
				return 'beperkt' === $this->soort ? $this->data[ $attribuut ] : '';
			case 'extras':
				return isset( $this->data['extras'] ) ? $this->data['extras'] : [];
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
			case 'datum':
			case 'start_datum':
			case 'pauze_datum':
			case 'eind_datum':
			case 'herstart_datum':
			case 'incasso_datum':
				$this->data[ $attribuut ] = ( ! is_null( $waarde ) ? date( 'Y-m-d', $waarde ) : 0 );
				break;
			case 'geannuleerd':
			case 'gepauzeerd':
				$this->data[ $attribuut ] = $waarde ? 1 : 0;
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Bewaar de data
	 *
	 * Bewaar de data als user meta in de database.
	 *
	 * @since 4.0.87
	 */
	public function save() {
		update_user_meta( $this->abonnee_id, self::META_KEY, $this->data );
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
	 * Verzenden van de welkomst email.
	 *
	 * @param  string $type      Welke email er verstuurd moet worden.
	 * @param  string $wijziging Ingeval van een wijziging, de tekst die dit beschrijft.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $wijziging = '' ) {
		$abonnee = get_userdata( $this->abonnee_id );
		$to      = "$abonnee->display_name <$abonnee->user_email>";
		return Kleistad_Email::compose(
			$to,
			( false !== strpos( $type, '_start' ) ) ? 'Welkom bij Kleistad' : 'Abonnement Kleistad',
			'kleistad_email_abonnement' . $type,
			[
				'voornaam'                => $abonnee->first_name,
				'achternaam'              => $abonnee->last_name,
				'loginnaam'               => $abonnee->user_login,
				'start_datum'             => strftime( '%d-%m-%y', $this->start_datum ),
				'pauze_datum'             => ( $this->pauze_datum > 0 ) ? strftime( '%d-%m-%y', $this->pauze_datum ) : '',
				'eind_datum'              => ( $this->eind_datum > 0 ) ? strftime( '%d-%m-%y', $this->eind_datum ) : '',
				'herstart_datum'          => ( $this->herstart_datum > 0 ) ? strftime( '%d-%m-%y', $this->herstart_datum ) : '',
				'abonnement'              => $this->soort,
				'abonnement_code'         => $this->code,
				'abonnement_dag'          => $this->dag,
				'abonnement_opmerking'    => ( '' !== $this->opmerking ) ? 'De volgende opmerking heb je doorgegeven: ' . $this->opmerking : '',
				'abonnement_wijziging'    => $wijziging,
				'abonnement_extras'       => count( $this->extras ) ? 'Je hebt de volgende extras gekozen: ' . $this->extras_lijst() : '',
				'abonnement_borg'         => number_format_i18n( $this->bedrag( self::BEDRAG_BORG ), 2 ),
				'abonnement_startgeld'    => number_format_i18n( $this->bedrag( self::BEDRAG_START ), 2 ),
				'abonnement_maandgeld'    => number_format_i18n( $this->bedrag( self::BEDRAG_MAAND ), 2 ),
				'abonnement_overbrugging' => number_format_i18n( $this->bedrag( self::BEDRAG_OVERBRUGGING ), 2 ),
				'abonnement_link'         => '<a href="' . home_url( '/kleistad_abonnement_betaling' ) .
												'?gid=' . $this->abonnee_id .
												'&abo=1' .
												'&hsh=' . $this->controle() . '" >Kleistad pagina</a>',
			]
		);
	}

	/**
	 * Maak een controle string aan.
	 *
	 * @since 4.3.0
	 *
	 * @return string Hash string.
	 */
	public function controle() {
		return hash( 'sha256', "KlEiStAd{$this->abonnee_id}AcOnTrOlE" );
	}

	/**
	 * Geef de status van het abonnement als een tekst terug.
	 *
	 * @since 4.5.7
	 *
	 * @return string De status tekst.
	 */
	public function status() {
		if ( $this->geannuleerd ) {
			return 'gestopt';
		} elseif ( $this->gepauzeerd ) {
			return 'gepauzeerd';
		} elseif ( Kleistad_Roles::reserveer( $this->abonnee_id ) ) {
			$vandaag = strtotime( 'today' );
			if ( $vandaag < $this->pauze_datum ) {
				return 'pauze gepland';
			} elseif ( $vandaag < $this->eind_datum ) {
				return 'stop gepland';
			}
			return 'actief';
		}
		return 'aangemeld';
	}

	/**
	 * Controleer of er een incasso actief is
	 *
	 * @since 4.3.0
	 *
	 * @return bool Als true, dan is incasso actief.
	 */
	public function incasso_actief() {
		return Kleistad_Betalen::actief( $this->abonnee_id, $this->subscriptie_id );
	}

	/**
	 * Maak de vervolgbetaling. In de callback wordt de automatische incasso gestart.
	 *
	 * @since 4.3.0
	 */
	public function betalen() {
		// Doe de eerste betaling om het mandaat te verkrijgen.
		$betalen = new Kleistad_Betalen();
		$betalen->order(
			$this->abonnee_id,
			__CLASS__ . '-' . $this->code . '-incasso',
			$this->bedrag( self::BEDRAG_OVERBRUGGING ),
			'Kleistad abonnement ' . $this->code . ' periode tot ' . strftime( '%d-%m-%y', $this->reguliere_datum - 60 * 60 * 24 ),
			'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging',
			true
		);
	}

	/**
	 * Voer een uitgestelde actie eenmalig uit.
	 *
	 * @since 4.3.0
	 *
	 * @param string $actie De uit te voeren actie.
	 * @param int    $datum Het moment waarop de actie moet worden uitgevoerd.
	 * @param int    $oude_datum Het moment van een eerdere gelijke actie die geannuleerd moet worden.
	 */
	private function schedule( $actie, $datum, $oude_datum = null ) {
		if ( ! is_null( $oude_datum ) ) {
			wp_unschedule_event(
				$oude_datum,
				self::META_KEY,
				[
					$this->abonnee_id,
					$actie,
					$oude_datum,
				]
			);
		}
		wp_schedule_single_event(
			$datum,
			self::META_KEY,
			[
				$this->abonnee_id,
				$actie,
				$datum,
			]
		);
	}

	/**
	 * Start automatisch betalen per incasso datum.
	 *
	 * @since 4.3.0
	 *
	 * @param int $datum Datum waarop abonnement incasso gestart moet worden.
	 */
	private function herhaalbetalen( $datum ) {
		$betalen = new Kleistad_Betalen();
		return $betalen->herhaalorder(
			$this->abonnee_id,
			$this->bedrag( self::BEDRAG_MAAND ),
			'Kleistad abonnement ' . $this->code,
			$datum
		);
	}

	/**
	 * Autoriseer de abonnee zodat deze de oven reservering mag doen en toegang tot leden pagina's krijgt.
	 *
	 * @since 4.3.0
	 *
	 * @param boolean $valid Als true, geef de autorisatie, als false haal de autorisatie weg.
	 */
	private function autoriseer( $valid ) {
		$abonnee = new WP_User( $this->abonnee_id );
		if ( $valid ) {
			$abonnee->add_cap( 'leden' );
			$abonnee->add_cap( Kleistad_Roles::RESERVEER );
			// Alternatief is wellicht abonnee add of remove role subscriber.
		} else {
			$abonnee->remove_cap( 'leden' );
			$abonnee->remove_cap( Kleistad_Roles::RESERVEER );
			$abonnee->remove_role( 'subscriber' );
		}
	}

	/**
	 * Geef de prijs van een extra.
	 *
	 * @param string $extra De extra abonnements functie.
	 * @return float Het bedrag.
	 */
	private function extra_bedrag( $extra ) {
		$options = Kleistad::get_options();
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
	 * Bereken de maandelijkse kosten, de overbrugging, de borg of het startbedrag.
	 *
	 * @since 4.5.2
	 *
	 * @param  int $soort Welk bedrag gevraagd wordt, standaard het maandbedrag.
	 * @return float Het maandbedrag.
	 */
	public function bedrag( $soort ) {
		$options = Kleistad::get_options();
		$bedrag  = (float) $options[ $this->soort . '_abonnement' ];
		foreach ( $this->extras as $extra ) {
			$bedrag += $this->extra_bedrag( $extra );
		}

		switch ( $soort ) {
			case self::BEDRAG_MAAND:
				return $bedrag;
			case self::BEDRAG_BORG:
				return (float) $options['borg_kast'];
			case self::BEDRAG_START:
				return 3 * $bedrag;
			case self::BEDRAG_START_EN_BORG:
				return 3 * $bedrag + (float) $options['borg_kast'];
			case self::BEDRAG_OVERBRUGGING:
				if ( '1' === date( 'j', $this->start_datum ) ) {
					return $bedrag;
				} else {
					// De fractie is het aantal dagen tussen vandaag en reguliere betaling, gedeeld door het aantal dagen in de maand.
					$dag            = 60 * 60 * 24; // Aantal seconden in een dag.
					$aantal_dagen   = ( $this->reguliere_datum - $this->driemaand_datum ) / $dag;
					$dagen_in_maand = intval( date( 'j', $this->reguliere_datum - $dag ) );
					return $bedrag * $aantal_dagen / $dagen_in_maand;
				}
		}
	}

	/**
	 * Pauzeer het abonnement per pauze datum.
	 *
	 * @since 4.3.0
	 *
	 * @param int  $pauze_datum    Pauzedatum.
	 * @param int  $herstart_datum Herstartdatum.
	 * @param bool $admin          Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function pauzeren( $pauze_datum, $herstart_datum, $admin = false ) {
		// Op de pauze_datum wordt de status gewijzigd naar gepauzeerd.
		$this->schedule( 'pauze', $pauze_datum, $this->pauze_datum );
		// Op de herstart_datum wordt de status weer gewijzigd naar niet-gepauzeerd.
		$this->schedule( 'herstart', $herstart_datum, $this->herstart_datum );
		$this->pauze_datum    = $pauze_datum;
		$this->herstart_datum = $herstart_datum;
		$betalen              = new Kleistad_Betalen();
		$this->subscriptie_id = $betalen->annuleer( $this->abonnee_id, $this->subscriptie_id );
		if ( $betalen->heeft_mandaat( $this->abonnee_id ) ) {
			$this->subscriptie_id = $this->herhaalbetalen( $herstart_datum );
		}
		$this->save();
		if ( ! $admin ) {
			$this->email( '_gewijzigd', 'Je hebt het abonnement per ' . strftime( '%d-%m-%y', $this->pauze_datum ) . ' gepauzeerd en start weer per ' . strftime( '%d-%m-%y', $this->herstart_datum ) );
		}
		return true;
	}

	/**
	 * Herstart het abonnement per datum.
	 *
	 * @since 4.3.0
	 *
	 * @param int  $herstart_datum Herstartdatum.
	 * @param bool $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function herstarten( $herstart_datum, $admin = false ) {
		// Op de herstart_datum wordt de gepauzeerd status verwijderd.
		$this->schedule( 'herstart', $herstart_datum, $this->herstart_datum );
		$this->herstart_datum = $herstart_datum;
		$betalen              = new Kleistad_Betalen();
		if ( $betalen->heeft_mandaat( $this->abonnee_id ) ) {
			$this->subscriptie_id = $this->herhaalbetalen( $herstart_datum );
		}
		$this->save();
		if ( ! $admin ) {
			$this->email(
				'_gewijzigd',
				'Je hebt het abonnement per ' . strftime( '%d-%m-%y', $this->herstart_datum ) . ' herstart.'
			);
		}
		return true;
	}

	/**
	 * Annuleer het abonnement per datum.
	 *
	 * @since 4.3.0
	 *
	 * @param int  $eind_datum Einddatum.
	 * @param bool $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function annuleren( $eind_datum, $admin = false ) {
		// Op de einddatum wordt de subscriber rol van het abonnee account verwijderd.
		$this->schedule( 'eind', $eind_datum, $this->eind_datum );

		// Een eventuele subscriptie wordt geannuleerd en mandaten worden verwijderd.
		$this->eind_datum     = $eind_datum;
		$betalen              = new Kleistad_Betalen();
		$this->subscriptie_id = $betalen->annuleer( $this->abonnee_id, $this->subscriptie_id );
		$betalen->verwijder_mandaat( $this->abonnee_id );
		$this->save();
		if ( ! $admin ) {
			$this->email(
				'_gewijzigd',
				'Je hebt het abonnement per ' . strftime( '%d-%m-%y', $this->eind_datum ) . ' beëindigd.'
			);
		}
		return true;
	}

	/**
	 * Wijzig het abonnement per datum.
	 *
	 * @since 4.3.0
	 *
	 * @param int    $wijzig_datum Wijzigdatum.
	 * @param string $type         Soort wijziging: soort abonnement of de extras.
	 * @param mixed  $soort        Beperkt/onbeperkt wijziging of de extras.
	 * @param string $dag          Dag voor beperkt abonnement.
	 * @param bool   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function wijzigen( $wijzig_datum, $type, $soort, $dag = '', $admin = false ) {
		$huidig_bedrag = $this->bedrag( self::BEDRAG_MAAND );
		switch ( $type ) {
			case 'soort':
				$this->soort = $soort;
				$this->dag   = $dag;
				$bericht     = 'Je hebt het abonnement per ' . strftime( '%d-%m-%y', $wijzig_datum ) . ' gewijzigd naar ' . $this->soort .
					( 'beperkt' === $this->soort ? ' (' . $this->dag . ')' : '' );
				break;
			case 'extras':
				$this->extras = $soort;
				$bericht      = 'Je gaat voortaan per ' . strftime( '%d-%m-%y', $wijzig_datum ) .
					( count( $soort ) ? ' gebruik maken van ' . implode( ', ', $soort ) : ' geen gebruik meer van extras' );
				break;
			default:
				$bericht = '';
		}
		if ( $this->bedrag( self::BEDRAG_MAAND ) !== $huidig_bedrag ) {
			$betalen              = new Kleistad_Betalen();
			$this->subscriptie_id = $betalen->annuleer( $this->abonnee_id, $this->subscriptie_id );
			/**
			 * Een automatische incasso wordt alleen gewijzigd als de abonnee zelf de wijziging doet.
			 */
			if ( ! $admin && $betalen->heeft_mandaat( $this->abonnee_id ) ) {
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
	 * Wijzig de betaalwijze van het abonnement per datum.
	 *
	 * @since 4.3.0
	 *
	 * @param int    $wijzig_datum Wijzigdatum.
	 * @param string $betaalwijze  Ideal of bankstorting.
	 * @param bool   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function betaalwijze( $wijzig_datum, $betaalwijze, $admin = false ) {
		$betalen              = new Kleistad_Betalen();
		$this->subscriptie_id = $betalen->annuleer( $this->abonnee_id, $this->subscriptie_id ); // Verwijder een eventuele bestaande subscriptie.

		if ( 'ideal' === $betaalwijze ) {
			// Doe een proefbetaling om het mandaat te verkrijgen. De wijzig datum is de 1e van de volgende maand.
			$this->incasso_datum = $wijzig_datum;
			$this->save();
			$betalen->order(
				$this->abonnee_id,
				__CLASS__ . '-' . $this->code . '-incasso',
				0.01,
				'Kleistad abonnement ' . $this->code . ' machtiging tot sepa-incasso',
				'Bedankt voor de betaling! De wijziging is verwerkt en er wordt een email verzonden met bevestiging',
				true
			);
		} else {
			$betalen->verwijder_mandaat( $this->abonnee_id );
			$this->subscriptie_id = '';
			$this->save();
			if ( ! $admin ) {
				$this->email( '_betaalwijze_bank' );
			}
			return true;
		}
	}

	/**
	 * Start de betaling van een nieuw abonnement.
	 *
	 * @since 4.3.0
	 *
	 * @param int    $start_datum Datum waarop abonnement gestart wordt.
	 * @param string $betaalwijze Ideal of bank.
	 * @param bool   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function start( $start_datum, $betaalwijze, $admin = false ) {
		$dag                  = 60 * 60 * 24; // Aantal seconden in een dag.
		$this->start_datum    = $start_datum;
		$this->geannuleerd    = false; // Reset ingeval het een hervatting is van een oud abonnement.
		$this->gepauzeerd     = false; // Idem.
		$this->pauze_datum    = null;
		$this->herstart_datum = null;
		$this->eind_datum     = null;
		$this->save();
		// Verzend 1 week vooraf verstrijken drie maanden termijn de email voor het vervolg.
		$this->schedule( 'vervolg', $this->driemaand_datum - 7 * $dag );

		if ( 'ideal' === $betaalwijze ) {
			$betalen = new Kleistad_Betalen();
			$betalen->order(
				$this->abonnee_id,
				__CLASS__ . '-' . $this->code . '-start_ideal',
				$this->bedrag( self::BEDRAG_START_EN_BORG ),
				'Kleistad abonnement ' . $this->code . ' periode ' . strftime( '%d-%m-%y', $this->start_datum ) . ' tot ' . strftime( '%d-%m-%y', $this->driemaand_datum ) . ' en borg',
				'Bedankt voor de betaling! De abonnement inschrijving is verwerkt en er wordt een email verzonden met bevestiging'
			);
		} else {
			if ( $admin ) {
				// Abonnement wordt door admin geactiveerd.
				$this->autoriseer( true );
				$this->email( '_start_ideal' );
			} else {
				$this->email( '_start_bank' );
			}
		}
	}

	/**
	 * Service functie voor update abonnee batch job.
	 * Datum wordt apart meegegeven, ondanks dat het de datum heden is.
	 * Omdat de uitvoeringstijd van de batchjob niet vastligt beter om de oorspronkelijke timestamp vast te leggen.
	 *
	 * @since 4.3.0
	 *
	 * @param string $actie De actie die op datum uitgevoerd moet worden.
	 * @param int    $datum De datum / tijdstip waarop de actie nodig is.
	 */
	public function event( $actie, $datum ) {

		switch ( $actie ) {
			case 'pauze':
				$this->pauze_datum = $datum;
				$this->gepauzeerd  = true;
				break;
			case 'eind':
				$this->eind_datum  = $datum;
				$this->geannuleerd = true;
				if ( ! is_super_admin( $this->abonnee_id ) ) { // Voorkom dat de admin zijn rol kwijtraakt.
					$this->autoriseer( false );
				}
				break;
			case 'herstart':
				$this->herstart_datum = $datum;
				$this->gepauzeerd     = false;
				break;
			case 'vervolg':
				if ( ! $this->geannuleerd ) {
					$this->email( '_vervolg' );
				}
				break;
			default:
				break;
		}
		$this->save();
	}

	/**
	 * (Her)activeer een abonnement. Wordt aangeroepen vanuit de betaal callback.
	 *
	 * @since 4.3.0
	 *
	 * @param array  $parameters De parameters 0: gebruiker-id, 1: de melddatum.
	 * @param string $bedrag     Geeft aan of het een eerste start of een herstart betreft.
	 * @param bool   $betaald    Of er werkelijk betaald is.
	 */
	public static function callback( $parameters, $bedrag, $betaald = true ) {
		if ( $betaald ) {
			$abonnement = new static( intval( $parameters[0] ) );
			$abonnement->autoriseer( true );
			if ( 'incasso' === $parameters[1] ) {
				// Een succesvolle betaling van een vervolg.
				if ( $abonnement->herstart_datum > $abonnement->incasso_datum ) {
					$abonnement->subscriptie_id = $abonnement->herhaalbetalen( $abonnement->herstart_datum );
				} else {
					$abonnement->subscriptie_id = $abonnement->herhaalbetalen( $abonnement->incasso_datum );
				}
				$email = '_betaalwijze_ideal';
			} else {
				$email = '_' . $parameters[1];
			}
			$abonnement->save();
			$abonnement->email( $email );
		}
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
				$arr[ $abonnee->ID ] = new Kleistad_Abonnement( $abonnee->ID );
				$arr[ $abonnee->ID ]->load( $abonnement );
			}
		}
		return $arr;
	}
}
