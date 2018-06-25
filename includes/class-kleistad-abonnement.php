<?php
/**
 * The file that defines the abonnement class
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

	const META_KEY = 'kleistad_abonnement';

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
		$this->_abonnee_id            = $abonnee_id;
		$this->_default_data['code']  = "A$abonnee_id";
		$this->_default_data['datum'] = date( 'Y-m-d' );

		$abonnement = get_user_meta( $this->_abonnee_id, self::META_KEY, true );
		if ( is_array( $abonnement ) ) {
			$this->_data = wp_parse_args( $abonnement, $this->_default_data );
		} else {
			$this->_data = $this->_default_data;
		}
		// Deze datum zit niet in de 'oude' abonnees.
		if ( '' === $this->_data['incasso_datum'] ) {
			$this->incasso_datum = mktime( 0, 0, 0, date( 'n', $this->start_datum ) + 4, 1, date( 'Y', $this->start_datum ) );
		}
	}

	/**
	 * Export functie privacy gevoelige data.
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (abonnement info).
	 */
	public static function export( $gebruiker_id ) {
		$abonnement = new static( $gebruiker_id );
		$items[]    = [
			'group_id'    => 'abonnementinfo',
			'group_label' => 'abonnement informatie',
			'item_id'     => 'abonnement-1',
			'data'        => [
				[
					'name'  => 'aanmeld datum',
					'value' => strftime( '%d-%m-%y', $abonnement->datum ),
				],
				[
					'name'  => 'start datum',
					'value' => $abonnement->start_datum > 0 ? strftime( '%d-%m-%y', $abonnement->start_datum ) : '',
				],
				[
					'name'  => 'eind datum',
					'value' => $abonnement->eind_datum > 0 ? strftime( '%d-%m-%y', $abonnement->eind_datum ) : '',
				],
				[
					'name'  => 'pauze datum',
					'value' => $abonnement->pauze_datum > 0 ? strftime( '%d-%m-%y', $abonnement->pauze_datum ) : '',
				],
				[
					'name'  => 'herstart datum',
					'value' => $abonnement->herstart_datum > 0 ? strftime( '%d-%m-%y', $abonnement->herstart_datum ) : '',
				],
				[
					'name'  => 'opmerking',
					'value' => $abonnement->opmerking,
				],
				[
					'name'  => 'soort abonnement',
					'value' => $abonnement->soort,
				],
				[
					'name'  => 'dag',
					'value' => $abonnement->dag,
				],
			],
		];
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
			case 'dag':
				return 'beperkt' === $this->soort ? $this->_data[ $attribuut ] : '';
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
		update_user_meta( $this->_abonnee_id, self::META_KEY, $this->_data );
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
	 * @param  string $type      Welke email er verstuurd moet worden.
	 * @param  string $wijziging Ingeval van een wijziging, de tekst die dit beschrijft.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $wijziging = '' ) {
		$options = get_option( 'kleistad-opties' );
		$abonnee = get_userdata( $this->_abonnee_id );
		$to      = "$abonnee->first_name $abonnee->last_name <$abonnee->user_email>";
		return Kleistad_public::compose_email(
			$to, ( false !== strpos( $type, '_start' ) ) ? 'Welkom bij Kleistad' : 'Abonnement Kleistad', 'kleistad_email_abonnement' . $type, [
				'voornaam'                => $abonnee->first_name,
				'achternaam'              => $abonnee->last_name,
				'loginnaam'               => $abonnee->user_login,
				'start_datum'             => strftime( '%d-%m-%y', $this->start_datum ),
				'pauze_datum'             => ( $this->pauze_datum > 0 ) ? strftime( '%d-%m-%y', $this->pauze_datum ) : '',
				'eind_datum'              => ( $this->eind_datum > 0 ) ? strftime( '%d-%m-%y', $this->eind_datum ) : '',
				'herstart_datum'          => ( $this->herstart_datum > 0 ) ? strftime( '%d-%m-%y', $this->herstart_datum ) : '',
				'incasso_datum'           => ( $this->incasso_datum > 0 ) ? strftime( '%d-%m-%y', $this->incasso_datum ) : '',
				'abonnement'              => $this->soort,
				'abonnement_code'         => $this->code,
				'abonnement_dag'          => $this->dag,
				'abonnement_opmerking'    => $this->opmerking,
				'abonnement_wijziging'    => $wijziging,
				'abonnement_borg'         => number_format_i18n( $options['borg_kast'], 2 ),
				'abonnement_startgeld'    => number_format_i18n( 3 * $options[ $this->soort . '_abonnement' ], 2 ),
				'abonnement_maandgeld'    => number_format_i18n( $options[ $this->soort . '_abonnement' ], 2 ),
				'abonnement_overbrugging' => number_format_i18n( $this->overbrugging(), 2 ),
				'abonnement_link'         => '<a href="' . home_url( '/kleistad_abonnement_betaling' ) .
												'?gid=' . $this->_abonnee_id .
												'&abo=1' .
												'&hsh=' . $this->controle() . '" >Kleistad pagina</a>',
			]
		);
	}

	/**
	 * Maak een controle string aan.
	 *
	 * @return string Hash string.
	 */
	public function controle() {
		return hash( 'sha256', "KlEiStAd{$this->_abonnee_id}AcOnTrOlE" );
	}

	/**
	 * Controleer of er een incasso actief is
	 *
	 * @return bool Als true, dan is incasso actief.
	 */
	public function incasso_actief() {
		$betalen = new Kleistad_Betalen();
		return $betalen->actief( $this->_abonnee_id, $this->subscriptie_id );
	}

	/**
	 * Maak de vervolgbetaling. In de callback wordt de automatische incasso gestart.
	 */
	public function betalen() {
		$betalen = new Kleistad_Betalen();
		// Doe de eerste betaling om het mandaat te verkrijgen.
		$betalen->order(
			$this->_abonnee_id,
			$this->code . '-incasso',
			$this->overbrugging(),
			'Kleistad abonnement ' . $this->code . ' periode tot ' . strftime( '%d-%m-%y', $this->incasso_datum ),
			'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging',
			true
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
			$datum, self::META_KEY, [
				$this->_abonnee_id,
				$actie,
				$datum,
			]
		);
	}

	/**
	 * Start automatisch betalen per incasso datum.
	 *
	 * @param int $datum Datum waarop abonnement incasso gestart moet worden.
	 */
	private function herhaalbetalen( $datum ) {
		$betalen = new Kleistad_Betalen();
		$options = get_option( 'kleistad-opties' );

		return $betalen->herhaalorder(
			$this->_abonnee_id,
			$options[ $this->soort . '_abonnement' ],
			'Kleistad abonnement ' . $this->code,
			$datum
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
	 * Bereken het overbruggings bedrag.
	 *
	 * @return float Het bedrag.
	 */
	public function overbrugging() {
		$options = get_option( 'kleistad-opties' );
		if ( '1' === date( 'j', $this->start_datum ) ) {
			$bedrag = $options[ $this->soort . '_abonnement' ];
		} else {
			// De fractie is het aantal dagen tussen vandaag en reguliere betaling, gedeeld door het aantal dagen in de maand.
			$dag             = 60 * 60 * 24; // Aantal seconden in een dag.
			$driemaand_datum = mktime( 0, 0, 0, date( 'n', $this->start_datum ) + 3, date( 'j', $this->start_datum ), date( 'Y', $this->start_datum ) );
			$aantal_dagen    = ( $this->incasso_datum - $driemaand_datum ) / $dag;
			$dagen_in_maand  = intval( date( 'j', $this->incasso_datum - $dag ) );
			$bedrag          = $options[ $this->soort . '_abonnement' ] * $aantal_dagen / $dagen_in_maand;
		}
		return $bedrag;
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
		$betalen              = new Kleistad_Betalen();
		$this->subscriptie_id = $betalen->annuleer( $this->_abonnee_id, $this->subscriptie_id );
		if ( ! $admin ) {
			$this->email( '_gewijzigd', 'Je hebt het abonnement per ' . strftime( '%d-%m-%y', $this->pauze_datum ) . ' gepauzeerd en start weer per ' . strftime( '%d-%m-%y', $this->herstart_datum ) );
		}
		$this->save();
		return true;
	}

	/**
	 * Herstart het abonnement per datum.
	 *
	 * @param timestamp $herstart_datum Herstartdatum.
	 * @param boolean   $admin        Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function herstarten( $herstart_datum, $admin = false ) {
		// Op de herstart_datum wordt de gepauzeerd status verwijderd.
		$this->schedule( 'herstart', $herstart_datum );
		$this->herstart_datum = $herstart_datum;
		$betalen              = new Kleistad_Betalen();
		if ( $betalen->heeft_mandaat( $this->_abonnee_id ) ) {
			$this->subscriptie_id = $this->herhaalbetalen( $herstart_datum );
		}
		if ( ! $admin ) {
			$this->email(
				'_gewijzigd',
				'Je hebt het abonnement per ' . strftime( '%d-%m-%y', $this->herstart_datum ) . ' herstart.'
			);
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

		// Een eventuele subscriptie wordt geannuleerd en mandaten worden verwijderd.
		$this->eind_datum     = $eind_datum;
		$betalen              = new Kleistad_Betalen();
		$this->subscriptie_id = $betalen->annuleer( $this->_abonnee_id, $this->subscriptie_id );
		$betalen->verwijder_mandaat( $this->_abonnee_id );
		if ( ! $admin ) {
			$this->email(
				'_gewijzigd',
				'Je hebt het abonnement per ' . strftime( '%d-%m-%y', $this->eind_datum ) . ' beëindigd.'
			);
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
		$this->soort          = $soort;
		$this->dag            = $dag;
		$this->wijzig_datum   = $wijzig_datum;
		$betalen              = new Kleistad_Betalen();
		$this->subscriptie_id = $betalen->annuleer( $this->_abonnee_id, $this->subscriptie_id );
		if ( ! $admin ) {
			if ( $betalen->heeft_mandaat( $this->_abonnee_id ) ) {
				$this->subscriptie_id = $this->herhaalbetalen( $wijzig_datum );
			}
			$this->email(
				'_gewijzigd',
				'Je hebt het abonnement per ' . strftime( '%d-%m-%y', $this->wijzig_datum ) . ' gewijzigd naar ' . $this->soort
			);
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
			$this->incasso_datum = mktime( 0, 0, 0, date( 'n', $wijzig_datum ), date( 'j', $wijzig_datum ), date( 'Y', $wijzig_datum ) );
			$this->save();
			$betalen->order(
				$this->_abonnee_id,
				$this->code . '-incasso',
				0.01,
				'Kleistad abonnement ' . $this->code,
				'Bedankt voor de betaling! De wijziging is verwerkt en er wordt een email verzonden met bevestiging',
				true
			);
		} else {
			$this->subscriptie_id = $betalen->annuleer( $this->_abonnee_id, $this->subscriptie_id );
			$betalen->verwijder_mandaat( $this->_abonnee_id );
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
		$options             = get_option( 'kleistad-opties' );
		$vervolg_datum       = mktime( 0, 0, 0, date( 'n', $start_datum ) + 3, date( 'j', $start_datum ) - 7, date( 'Y', $start_datum ) );
		$driemaand_datum     = mktime( 0, 0, 0, date( 'n', $start_datum ) + 3, date( 'j', $start_datum ), date( 'Y', $start_datum ) );
		$this->start_datum   = $start_datum;
		$this->incasso_datum = mktime( 0, 0, 0, date( 'n', $start_datum ) + 4, 1, date( 'Y', $start_datum ) );
		$this->save();
		// Verzend 1 week vooraf verstrijken drie maanden termijn de email voor het vervolg.
		$this->schedule( 'vervolg', $vervolg_datum );

		if ( 'ideal' === $betaalwijze ) {
			$betalen = new Kleistad_Betalen();
			$betalen->order(
				$this->_abonnee_id,
				$this->code . '-start_ideal',
				$options[ $this->soort . '_abonnement' ] * 3 + $options['borg_kast'],
				'Kleistad abonnement ' . $this->code . ' periode ' . strftime( '%d-%m-%y', $this->start_datum ) . ' tot ' . strftime( '%d-%m-%y', $driemaand_datum ) . ' en borg',
				'Bedankt voor de betaling! De abonnement inschrijving is verwerkt en er wordt een email verzonden met bevestiging'
			);
		} else {
			if ( $admin ) {
				// Abonnement wordt door admin geactiveerd.
				if ( ! Kleistad_Roles::reserveer( $this->_abonnee_id ) ) {
					$this->autoriseer( true );
				}
				$this->email( '_start_ideal' );
			} else {
				$this->email( '_start_bank' );
			}
		}
		return true;
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
	 * @param string $type Geeft aan of het een eerste start of een herstart betreft.
	 */
	public function callback( $type ) {
		if ( ! Kleistad_Roles::reserveer( $this->_abonnee_id ) ) {
			$this->autoriseer( true );
		}
		if ( 'incasso' === $type ) {
			// Een succesvolle betaling van een vervolg.
			$this->subscriptie_id = $this->herhaalbetalen( $this->incasso_datum );
			$email                = '_betaalwijze_ideal';
		} else {
			$email = '_' . $type;
		}

		$this->email( $email );
		$this->save();
	}

	/**
	 * Return alle abonnementen
	 *
	 * @return array abonnementen.
	 */
	public static function all() {
		static $arr = null;
		if ( is_null( $arr ) ) {
			$abonnees = get_users( [ 'meta_key' => self::META_KEY ] );
			foreach ( $abonnees as $abonnee ) {
				$abonnement          = get_user_meta( $abonnee->ID, self::META_KEY, true );
				$arr[ $abonnee->ID ] = new Kleistad_Abonnement( $abonnee->ID );
				$arr[ $abonnee->ID ]->load( $abonnement );
			}
		}
		return $arr;
	}
}
