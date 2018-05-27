<?php
/**
 * The file that defines the cursus class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Description of class-kleistad-abonnement
 *
 * @author espra
 */
class Kleistad_Abonnement extends Kleistad_Entity {

	/**
	 * Store the cursist id
	 *
	 * @since 4.0.87
	 * @access private
	 * @var int $_cursist_id the wp user id the of cursist.
	 */
	private $_abonnee_id;

	/**
	 * De beginwaarden van een abonnement.
	 *
	 * @since 4.3.0
	 * @access private
	 * @var array $_default_data de standaard waarden bij het aanmaken van een abonnement.
	 */
	private $_default_data = [
		'code'             => '',
		'datum'            => '',
		'start_datum'      => '',
		'dag'              => '',
		'geannuleerd'      => 0,
		'opmerking'        => '',
		'soort'            => 'onbeperkt',
		'pauze_datum'      => '',
		'eind_datum'       => '',
		'herstart_datum'   => '',
		'incasso_datum'    => '',
		'gepauzeerd'       => 0,
		'subscriptie_id'   => '',
	];

	/**
	 * Constructor
	 *
	 * Create the abonnee object .
	 *
	 * @since 4.0.87
	 *
	 * @param int $abonnee_id id of the abonnee.
	 */
	public function __construct( $abonnee_id ) {
		$this->_abonnee_id = $abonnee_id;
		$this->_default_data['code'] = "A$abonnee_id";
		$this->_default_data['datum'] = date( 'Y-m-d' );

		$abonnement = get_user_meta( $this->_abonnee_id, 'kleistad_abonnement', true );
		if ( is_array( $abonnement ) ) {
			$this->_data = wp_parse_args( $abonnement, $this->_default_data );
		} else {
			$this->_data = $this->_default_data;
		}
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
			case 'datum':
			case 'start_datum':
			case 'pauze_datum':
			case 'eind_datum':
			case 'herstart_datum':
			case 'incasso_datum':
				return strtotime( $this->_data[ $attribuut ] );
			case 'geannuleerd':
			case 'gepauzeerd':
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
			case 'datum':
			case 'start_datum':
			case 'pauze_datum':
			case 'eind_datum':
			case 'herstart_datum':
			case 'incasso_datum':
				$this->_data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'geannuleerd':
			case 'gepauzeerd':
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
		update_user_meta( $this->_abonnee_id, 'kleistad_abonnement', $this->_data );
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
	 * Verzenden van de welkomst email.
	 *
	 * @param string $type Welke email er verstuurd moet worden.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type ) {
		$options = get_option( 'kleistad-opties' );
		$abonnee   = get_userdata( $this->_abonnee_id );
		$to        = "$abonnee->first_name $abonnee->last_name <$abonnee->user_email>";
		return Kleistad_public::compose_email(
			$to, ( strpos( $type, 'start' ) ) ? 'Welkom bij Kleistad' : 'Wijziging abonnement Kleistad', 'kleistad_email_abonnement' . $type, [
				'voornaam'             => $abonnee->first_name,
				'achternaam'           => $abonnee->last_name,
				'loginnaam'            => $abonnee->user_login,
				'start_datum'          => strftime( '%d-%m-%y', $this->start_datum ),
				'pauze_datum'          => strftime( '%d-%m-%y', $this->pauze_datum ),
				'eind_datum'           => strftime( '%d-%m-%y', $this->eind_datum ),
				'herstart_datum'       => strftime( '%d-%m-%y', $this->herstart_datum ),
				'incasso_datum'        => strftime( '%d-%m-%y', $this->incasso_datum ),
				'abonnement'           => $this->soort,
				'abonnement_code'      => $this->code,
				'abonnement_dag'       => $this->dag,
				'abonnement_opmerking' => $this->opmerking,
				'abonnement_startgeld' => number_format( 3 * $options[ $this->soort . '_abonnement' ], 2, ',', '' ),
				'abonnement_maandgeld' => number_format( $options[ $this->soort . '_abonnement' ], 2, ',', '' ),
			]
		);
	}

	/**
	 * Voer een uitgestelde actie eenmalig uit.
	 *
	 * @param string    $actie De uit te voeren actie.
	 * @param timestamp $datum Het moment waarop de actie moet worden uitgevoerd.
	 */
	private function schedule( $actie, $datum ) {
		wp_schedule_single_event(
			$datum, 'kleistad_abonnement', [
				$this->_abonnee_id,
				$actie,
				$datum,
			]
		);
	}

	/**
	 * Autoriseer de abonnee zodat deze de oven reservering mag doen en toegang tot leden pagina's krijgt.
	 *
	 * @param boolean $valid Als true, geef de autorisatie, als false haal de autorisatie weg.
	 */
	private function autoriseer( $valid ) {
		$abonnee = new WP_User( $this->_abonnee_id );
		if ( $valid ) {
			$abonnee->add_cap( 'leden' );
			$abonnee->add_cap( Kleistad_Roles::RESERVEER );
			// Alternatief is wellicht abonnee add of remove role subscriber.
		} else {
			$abonnee->remove_cap( 'leden' );
			$abonnee->remove_cap( Kleistad_Roles::RESERVEER );
		}
	}


	/**
	 * Pauzeer het abonnement per pauze datum.
	 *
	 * @param timestamp $pauze_datum    Pauzedatum.
	 * @param timestamp $herstart_datum Herstartdatum.
	 * @param boolean   $admin          Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function pauzeren( $pauze_datum, $herstart_datum, $admin = false ) {
		// Op de pauze_datum wordt de status gewijzigd naar gepauzeerd.
		$this->schedule( 'pauze', $pauze_datum );
		$this->pauze_datum    = $pauze_datum;
		$this->herstart_datum = $herstart_datum;
		if ( '' !== $this->subscriptie_id ) {
			$betalen = new Kleistad_Betalen();
			$betalen->annuleer( $this->_abonnee_id, $this->subscriptie_id );
		}
		if ( ! $admin ) {
			$this->email( '_gepauzeerd' );
		}
		$this->save();
		return true;
	}

	/**
	 * Herstart het abonnement per datum.
	 *
	 * @param timestamp $herstart_datum Herstartdatum.
	 */
	public function herstarten( $herstart_datum ) {
		// Op de herstart_datum wordt de gepauzeerd status verwijderd.
		$this->schedule( 'herstart', $herstart_datum );
		$this->herstart_datum = $herstart_datum;

		$betalen = new Kleistad_Betalen();
		if ( $betalen->heeft_mandaat( $this->_abonnee_id ) ) {
			$this->subscriptie_id = $this->herhaalbetalen( $herstart_datum );
			$this->email( '_herstart_ideal' );
		} else {
			$this->email( '_herstart_bank' );
		}
		$this->save();
		return true;
	}

	/**
	 * Annuleer het abonnement per datum.
	 *
	 * @param timestamp $eind_datum Einddatum.
	 * @param boolean   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function annuleren( $eind_datum, $admin = false ) {
		// Op de einddatum wordt de subscriber rol van het abonnee account verwijderd.
		$this->schedule( 'eind', $eind_datum );
		$this->eind_datum = $eind_datum;

		$betalen = new Kleistad_Betalen();
		if ( '' !== $this->subscriptie_id ) {
			$betalen->verwijder( $this->_abonnee_id );
			$this->subscriptie_id = '';
		}
		if ( ! $admin ) {
			$this->email( '_geannuleerd' );
		}
		$this->save();
		return true;
	}

	/**
	 * Wijzig het abonnement per datum.
	 *
	 * @param timestamp $wijzig_datum Wijzigdatum.
	 * @param string    $soort        Beperkt/onbeperkt.
	 * @param dag       $dag          Dag voor beperkt abonnement.
	 * @param boolean   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function wijzigen( $wijzig_datum, $soort, $dag, $admin = false ) {
		$this->soort = $soort;
		$this->dag = $dag;
		$this->wijzig_datum = $wijzig_datum;

		$betalen = new Kleistad_Betalen();
		if ( $admin ) {
			if ( $betalen->heeft_mandaat( $this->_abonnee_id ) ) {
				$betalen->annuleer( $this->_abonnee_id, $this->subscriptie_id );
			}
		} else {
			if ( $betalen->heeft_mandaat( $this->_abonnee_id ) ) {
				$betalen->annuleer( $this->_abonnee_id, $this->subscriptie_id );
				$this->subscriptie_id = $this->herhaalbetalen( $wijzig_datum );
			}
			$this->email( '_gewijzigd' );
		}
		$this->save();
		return true;
	}

	/**
	 * Wijzig de betaalwijze van het abonnement per datum.
	 *
	 * @param timestamp $wijzig_datum Wijzigdatum.
	 * @param string    $betaalwijze  Ideal of bankstorting.
	 */
	public function betaalwijze( $wijzig_datum, $betaalwijze ) {
		$betalen = new Kleistad_Betalen();

		if ( 'ideal' === $betaalwijze ) {
			// Doe een proefbetaling om het mandaat te verkrijgen.
			$this->incasso_datum = $wijzig_datum;
			$this->save();
			$betalen->order(
				$this->_abonnee_id,
				$this->code . '-_betaalwijze_ideal',
				0.01,
				'Kleistad abonnement ' . $this->code,
				'Bedankt voor de betaling! De wijziging is verwerkt en er wordt een email verzonden met bevestiging',
				true
			);
			// De email 'betaalwijze_ideal' moet via de callback verzonden worden.
		} else {
			$this->incasso_datum = null;
			$betalen->verwijder( $this->_abonnee_id );
			$this->email( '_betaalwijze_bank' );
		}
		$this->save();
		return true;
	}

	/**
	 * Start de betaling van een nieuw abonnement.
	 *
	 * @param timestamp $start_datum Datum waarop abonnement gestart wordt.
	 * @param string    $betaalwijze Ideal of bank.
	 * @param boolean   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function start( $start_datum, $betaalwijze, $admin = false ) {
		$options = get_option( 'kleistad-opties' );
		$driemaand_datum = mktime( 0, 0, 0, date( 'n', $start_datum ) + 3, date( 'j', $start_datum ), date( 'Y', $start_datum ) );

		// Na drie maanden moet de overbrugging betaald worden voor de dagen tot de 1e van de volgende maand. We doen dit ongeacht of er nu al mandaat beschikbaar is.
		$this->schedule( 'overbrugging', $driemaand_datum );
		$this->start_datum = $start_datum;

		if ( 'ideal' === $betaalwijze ) {
			// Bepaal de datum vanaf wanneer de reguliere incasso gaat starten.
			$this->incasso_datum = ( 1 === date( 'j', $start_datum ) ) ? $start_datum : mktime( 0, 0, 0, date( 'n', $start_datum ) + 4, 1, date( 'Y', $start_datum ) );
			$this->save();

			$betalen = new Kleistad_Betalen();
			$betalen->order(
				$this->_abonnee_id,
				$this->code . '-_start',
				$options[ $this->soort . '_abonnement' ] * 3 + $options['borg_kast'],
				'Kleistad abonnement ' . $this->code . ' periode ' . strftime( '%d-%m-%y', $this->start_datum ) . ' tot ' . strftime( '%d-%m-%y', $driemaand_datum ) . ' en borg',
				'Bedankt voor de betaling! De abonnement inschrijving is verwerkt en er wordt een email verzonden met bevestiging',
				true
			);
			// De email '_betaald' moet via de callback verzonden worden.
		} else {
			if ( $admin ) {
				// Abonnement wordt door admin geactiveerd.
				if ( ! Kleistad_Roles::reserveer( $this->_abonnee_id ) ) {
					$this->autoriseer( true );
				}
				$this->email( '_start' );
			} else {
				$this->email( '_start_bank' );
			}
		}
		return true;
	}

	/**
	 * Start automatisch betalen per incasso datum.
	 */
	public function herhaalbetalen() {
		$betalen = new Kleistad_Betalen();
		$options = get_option( 'kleistad-opties' );

		return $betalen->herhaalorder(
			$this->_abonnee_id,
			$options[ $this->soort . '_abonnement' ],
			'Kleistad abonnement ' . $this->code,
			$this->incasso_datum
		);
	}

	/**
	 * Service functie voor update abonnee batch job.
	 * Datum wordt apart meegegeven, ondanks dat het de datum heden is.
	 * Omdat de uitvoeringstijd van de batchjob niet vastligt beter om de oorspronkelijke timestamp vast te leggen.
	 *
	 * @param string    $actie De actie die op datum uitgevoerd moet worden.
	 * @param timestamp $datum De datum / tijdstip waarop de actie nodig is.
	 */
	public function event( $actie, $datum ) {

		$options = get_option( 'kleistad-opties' );

		switch ( $actie ) {
			case 'pauze':
				$this->pauze_datum = $datum;
				$this->gepauzeerd  = true;
				break;
			case 'eind':
				$this->eind_datum  = $datum;
				$this->geannuleerd = true;
				if ( ! is_super_admin( $this->_abonnee_id ) ) { // Voorkom dat de admin zijn rol kwijtraakt.
					$this->autoriseer( false );
				}
				break;
			case 'herstart':
				$this->herstart_datum = $datum;
				$this->gepauzeerd     = false;
				break;
			case 'overbrugging':
				$betalen = new Kleistad_Betalen();
				if ( $betalen->heeft_mandaat( $this->_abonnee_id ) ) {
					if ( $this->incasso_datum > $datum ) {
						// Alleen als er een mandaat is en de reguliere incasso al niet vandaag moet starten dan is er nog een overbrugging incasso nodig.
						// De fractie is het aantal dagen tussen vandaag en reguliere betaling, gedeeld door het aantal dagen in de maand.
						$aantal_dagen = ( $this->incasso_datum - $datum ) / ( 60 * 60 * 24 );
						$fractie = $aantal_dagen / cal_days_in_month( CAL_GREGORIAN, date( 'n', $datum ), date( 'Y', $datum ) );
						$bedrag = $options[ $this->soort . '_abonnement' ] * $fractie;
						if ( $bedrag >= 1 ) {
							$betalen->on_demand_order(
								$this->_abonnee_id,
								$bedrag,
								'Kleistad abonnement ' . $this->code . ' periode ' . strftime( '%d-%m-%y', $datum ) . ' tot ' . strftime( '%d-%m-%y', $this->incasso_datum )
							);
						}
					}
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
	 * @param string $email Geeft aan of het een eerste start of een herstart betreft.
	 */
	public function callback( $email ) {
		if ( ! Kleistad_Roles::reserveer( $this->_abonnee_id ) ) {
			$this->autoriseer( true );
		}
		$this->subscriptie_id = $this->herhaalbetalen();
		$this->email( $email );
		$this->save();
	}
}

/**
   * Collection of Abonnement
   *
   * Collection of Abonnementen, loaded from the database.
   *
   * @since 4.0.87
   *
   * @see class Kleistad_Abonnement
   * @link URL
    */
class Kleistad_Abonnementen extends Kleistad_EntityStore {

	/**
	 * Constructor
	 *
	 * Loads the data from the database.
	 *
	 * @since 4.0.91
	 *
	 * @return null.
	 */
	public function __construct() {
		$abonnees = get_users(
			[
				'meta_key' => 'kleistad_abonnement',
			]
		);
		foreach ( $abonnees as $abonnee ) {
			$abonnement = get_user_meta( $abonnee->ID, 'kleistad_abonnement', true );
			$this->_data[ $abonnee->ID ] = new Kleistad_Abonnement( $abonnee->ID );
			$this->_data[ $abonnee->ID ]->load( $abonnement );
		}
	}
}

/**
 * Description of class-kleistad-dagdelenkaart
 *
 * @author espra
 */
class Kleistad_Dagdelenkaart extends Kleistad_Entity {

	/**
	 * Store the cursist id
	 *
	 * @since 4.0.87
	 * @access private
	 * @var int $_cursist_id the wp user id the of gebruiker.
	 */
	private $_gebruiker_id;

	/**
	 * De beginwaarden van een dagdelenkaart.
	 *
	 * @since 4.3.0
	 * @access private
	 * @var array $_default_data de standaard waarden bij het aanmaken van een dagdelenkaart.
	 */
	private $_default_data = [
		'code'             => '',
		'datum'            => '',
		'start_datum'      => '',
		'opmerking'        => '',
	];

	/**
	 * Constructor
	 *
	 * Create the dagdelenkaart object .
	 *
	 * @since 4.3.0
	 *
	 * @param int $gebruiker_id id of the gebruiker.
	 */
	public function __construct( $gebruiker_id ) {
		$this->_gebruiker_id = $gebruiker_id;
		$this->_default_data['code'] = "K$gebruiker_id-" . strftime( '%y%m%d', time() );

		$this->_default_data['datum'] = date( 'Y-m-d' );

		$dagdelenkaart = get_user_meta( $this->_gebruiker_id, 'kleistad_dagdelenkaart', true );
		if ( is_array( $dagdelenkaart ) ) {
			$this->_data = wp_parse_args( $dagdelenkaart, $this->_default_data );
		} else {
			$this->_data = $this->_default_data;
		}
	}

	/**
	 * Getter, using the magic function
	 *
	 * Get attribuut from the object.
	 *
	 * @since 4.3.0
	 *
	 * @param string $attribuut Attribuut name.
	 * @return mixed Attribute value.
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
	 * Setter, using the magic function
	 *
	 * Set attribuut from the object.
	 *
	 * @since 4.3.0
	 *
	 * @param string $attribuut Attribuut name.
	 * @param mixed  $waarde Attribuut value.
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
	 * Save the data
	 *
	 * Saves the data to the database.
	 *
	 * @since 4.3.0
	 */
	public function save() {
		update_user_meta( $this->_gebruiker_id, 'kleistad_dagdelenkaart', $this->_data );
	}

	/**
	 * Omdat een dagdelenkaart een meta data object betreft kan het niet omgezet worden naar de laatste versie.
	 *
	 * @param object $data het te laden object.
	 */
	public function load( $data ) {
		$this->_data = wp_parse_args( $data, $this->_default_data );
	}

	/**
	 * Verzenden van de welkomst email.
	 *
	 * @param string $type Welke email er verstuurd moet worden.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type ) {
		$options = get_option( 'kleistad-opties' );
		$gebruiker   = get_userdata( $this->_gebruiker_id );
		$to        = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
		return Kleistad_public::compose_email(
			$to, 'Welkom bij Kleistad', 'kleistad_email_dagdelenkaart' . $type, [
				'voornaam'                => $gebruiker->first_name,
				'achternaam'              => $gebruiker->last_name,
				'loginnaam'               => $gebruiker->user_login,
				'start_datum'             => strftime( '%d-%m-%y', $this->start_datum ),
				'dagdelenkaart_code'      => $this->code,
				'dagdelenkaart_opmerking' => $this->opmerking,
				'dagdelenkaart_prijs'     => number_format( 3 * $options['dagdelenkaart'], 2, ',', '' ),
			]
		);
	}

	/**
	 * Start de betaling van een nieuw dagdelenkaart.
	 *
	 * @param timestamp $start_datum Datum waarop dagdelenkaart gestart wordt.
	 * @param string    $betaalwijze Ideal of bank.
	 * @param boolean   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function start( $start_datum, $betaalwijze, $admin = false ) {
		$this->start_datum = $start_datum;
		$options = get_option( 'kleistad-opties' );

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
			// De email '_betaald' moet via de callback verzonden worden.
		} else {
			$this->email( '_bank' );
		}
		return true;
	}

	/**
	 * Activeer een dagdelenkaart. Wordt aangeroepen vanuit de betaal callback.
	 *
	 * @param string $email Geeft aan of het een eerste start of een herstart betreft.
	 */
	public function callback( $email ) {
		$this->email( $email );
		$this->save();
	}
}

/**
   * Collection of Dagdelenkaart
   *
   * Collection of Dagdelenkaarten, loaded from the database.
   *
   * @since 4.3.0
   *
   * @see class Kleistad_Dagdelenkaart
   * @link URL
    */
class Kleistad_Dagdelenkaarten extends Kleistad_EntityStore {

	/**
	 * Constructor
	 *
	 * Loads the data from the database.
	 *
	 * @since 4.3.0
	 *
	 * @return null.
	 */
	public function __construct() {
		$gebruikers = get_users(
			[
				'meta_key' => 'kleistad_dagdelenkaart',
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			$dagdelenkaart = get_user_meta( $gebruiker->ID, 'kleistad_dagdelenkaart', true );
			$this->_data[ $gebruiker->ID ] = new Kleistad_Dagdelenkaart( $gebruiker->ID );
			$this->_data[ $gebruiker->ID ]->load( $dagdelenkaart );
		}
	}
}
