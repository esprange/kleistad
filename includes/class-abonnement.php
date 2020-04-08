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
 * @property string dag
 * @property bool   geannuleerd
 * @property string opmerking
 * @property string soort
 * @property int    pauze_datum
 * @property int    eind_datum
 * @property int    herstart_datum
 * @property int    driemaand_datum
 * @property int    reguliere_datum
 * @property bool   gepauzeerd
 * @property bool   overbrugging_email
 * @property array  extras
 * @property int    factuur_maand
 */
class Abonnement extends Artikel {

	public const META_KEY        = 'kleistad_abonnement';
	public const MAX_PAUZE_WEKEN = 17;
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
		'datum'              => '',
		'start_datum'        => '',
		'dag'                => '',
		'geannuleerd'        => 0,
		'opmerking'          => '',
		'soort'              => 'onbeperkt',
		'pauze_datum'        => '',
		'eind_datum'         => '',
		'herstart_datum'     => '',
		'gepauzeerd'         => 0,
		'overbrugging_email' => 0,
		'extras'             => [],
		'factuur_maand'      => '',
	];

	/**
	 * De tekst voor een eventueel bericht in de email
	 *
	 * @access private
	 * @var string $bericht De tekst.
	 */
	private $bericht = '';

	/**
	 * Constructor, maak het abonnement object .
	 *
	 * @param int $klant_id wp user id van de abonnee.
	 */
	public function __construct( $klant_id ) {
		$this->klant_id              = $klant_id;
		$this->betalen               = new \Kleistad\Betalen();
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
		switch ( $attribuut ) {
			case 'datum':
			case 'start_datum':
			case 'pauze_datum':
			case 'eind_datum':
			case 'herstart_datum':
				return strtotime( $this->data[ $attribuut ] );
			case 'driemaand_datum':
				return strtotime( '+3 month ' . $this->data['start_datum'] );
			case 'reguliere_datum':
				return strtotime( 'first day of +4 month ' . $this->data['start_datum'] );
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
				$this->data[ $attribuut ] = $waarde ? date( 'Y-m-d', $waarde ) : '';
				break;
			default:
				$this->data[ $attribuut ] = is_string( $waarde ) ? trim( $waarde ) : ( is_bool( $waarde ) ? (int) $waarde : $waarde );
		}
	}

	/**
	 * Verwijder het abonnement
	 */
	public function erase() {
		delete_user_meta( $this->klant_id, self::META_KEY );
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
	 * Geef de referentie terug.
	 *
	 * @return string
	 */
	public function referentie() {
		if ( strpos( ' regulier pauze', $this->artikel_type ) ) {
			return "$this->code-$this->artikel_type-" . date( 'Ym' );
		} else {
			return "$this->code-$this->artikel_type";
		}
	}

	/**
	 * Wijzig de betaalwijze van het abonnement naar sepa incasso.
	 *
	 * @return string|bool De redirect uri of false als de betaling niet lukt.
	 */
	public function start_incasso() {
		$this->artikel_type = 'mandaat';
		return $this->ideal( 'Bedankt voor de betaling! De wijziging is verwerkt en er wordt een email verzonden met bevestiging', $this->referentie() );
	}

	/**
	 * Wijzig de betaalwijze van het abonnement naar bank.
	 *
	 * @param bool $admin Als functie vanuit admin scherm wordt aangeroepen.
	 * @return bool In dit geval altijd true.
	 */
	public function stop_incasso( $admin = false ) {
		$this->betalen->verwijder_mandaat( $this->klant_id );
		if ( ! $admin ) {
			$this->bericht = 'Je gaat het abonnement voortaan per bank betalen';
			$this->email( '_gewijzigd' );
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
	public function ideal( $bericht, $referentie, $openstaand = null ) {
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
			$openstaand ?? $this->bedrag( "#{$this->artikel_type}" ),
			"Kleistad abonnement {$this->code}$vermelding",
			$bericht,
			$mandaat
		);
	}

	/**
	 * Maak de sepa incasso betalingen.
	 */
	private function sepa_incasso() {
		$bedrag = $this->bedrag( "#{$this->artikel_type}" );
		if ( 0.0 < $bedrag ) {
			return $this->betalen->eenmalig(
				$this->klant_id,
				$this->referentie(),
				$bedrag,
				"Kleistad abonnement {$this->code} " . strftime( '%B %Y', strtotime( 'today' ) ),
			);
		}
	}

	/**
	 * Verzenden van de email.
	 *
	 * @param  string $type      Welke email er verstuurd moet worden.
	 * @param  string $factuur   Bij de sluiten factuur.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $factuur = '' ) {
		$abonnee = get_userdata( $this->klant_id );
		$emailer = new \Kleistad\Email();
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
					'abonnement_extras'       => count( $this->extras ) ? 'Je hebt de volgende extras gekozen: ' . $this->extras_lijst() : '',
					'abonnement_startgeld'    => number_format_i18n( $this->bedrag( '#start' ), 2 ),
					'abonnement_maandgeld'    => number_format_i18n( $this->bedrag( '#regulier' ), 2 ),
					'abonnement_overbrugging' => number_format_i18n( $this->bedrag( '#overbrugging' ), 2 ),
					'abonnement_link'         => $this->betaal_link,
				],
			]
		);
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
		$this->save();
		if ( ! $admin ) {
			if ( $this->gepauzeerd ) {
				$this->bericht = 'Je hebt aangegeven dat je abonnement, dat nu gepauzeerd is, hervat wordt per ' . strftime( '%d-%m-%Y', $this->herstart_datum );
			} else {
				$this->bericht = 'Je pauzeert het abonnement per ' . strftime( '%d-%m-%Y', $this->pauze_datum ) . ' en hervat het per ' . strftime( '%d-%m-%Y', $this->herstart_datum );
			}
			$this->email( '_gewijzigd' );
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
			} elseif ( $vandaag < $this->driemaand_datum ) {
				return $uitgebreid ? 'gesstart sinds ' . strftime( '%x', $this->start_datum ) : 'gestart';
			} elseif ( $vandaag < $this->reguliere_datum ) {
				return 'overbrugging';
			}
			return $uitgebreid ? 'actief sinds ' . strftime( '%x', $this->start_datum ) : 'actief';
		}
		return $uitgebreid ? 'aangemeld per ' . strftime( '%x', $this->datum ) . ', start per ' . strftime( '%x', $this->start_datum ) : 'aangemeld';
	}

	/**
	 * Stop het abonnement per datum.
	 *
	 * @param int  $eind_datum Einddatum.
	 * @param bool $admin      Als functie vanuit admin scherm wordt aangeroepen.
	 */
	public function stoppen( $eind_datum, $admin = false ) {
		$this->eind_datum = $eind_datum;
		$this->betalen->verwijder_mandaat( $this->klant_id );
		$this->save();
		if ( ! $admin ) {
			$this->bericht = 'Je hebt het abonnement per ' . strftime( '%d-%m-%Y', $this->eind_datum ) . ' beëindigd.';
			$this->email( '_gewijzigd' );
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
	public function verwerk_betaling( $order_id, $bedrag, $betaald, $type, $transactie_id = '' ) {
		if ( $betaald ) {
			if ( $order_id ) {
				/**
				 * Er bestaat blijkbaar al een order voor deze referentie. Het komt dan vanaf een email betaal link of incasso of betaling per bank.
				 */
				$factuur = $this->ontvang_order( $order_id, $bedrag, $transactie_id );
				if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
					$this->email( '_ideal_betaald' );
				} elseif ( 'directdebit' === $type ) { // Als het een incasso is dan wordt er ook een factuur aangemaakt.
					$this->email( '_regulier_incasso', $factuur );
				} // Anders is het een bank betaling en daarvoor wordt geen bedank email verzonden.
			} elseif ( 'mandaat' === $this->artikel_type ) {
				/**
				 * Bij een mandaat ( 1 eurocent ) hoeven we geen factuur te sturen en is er dus geen order aangemaakt.
				 */
				$this->bericht = 'Je hebt Kleistad toestemming gegeven voor een maandelijkse incasso van het abonnement';
				$this->email( '_gewijzigd' );
			} elseif ( 'start' === $this->artikel_type ) {
				/**
				 * Bij een start en nog niet bestaande order moet dit wel afkomstig zijn van het invullen van
				 * een inschrijving formulier.
				 */
				$this->email( '_start_ideal', $this->bestel_order( $bedrag, $this->start_datum, '', $transactie_id ) );
			}
		} elseif ( 'directdebit' === $type ) {
			/**
			 * Als het een incasso betreft die gefaald is dan maken we alsnog de order aan.
			 */
			$this->email( '_regulier_mislukt', $this->bestel_order( 0.0, strtotime( '+7 days 0:00' ) ) );
		}
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
		$gewijzigd = false;
		switch ( $type ) {
			case 'soort':
				$gewijzigd   = $this->soort != $soort || $this->dag != $dag; // phpcs:ignore
				$this->soort   = $soort;
				$this->dag     = $dag;
				$this->bericht = 'Je hebt het abonnement per ' . strftime( '%d-%m-%Y', $wijzig_datum ) . ' gewijzigd naar ' . $this->soort .
					( 'beperkt' === $this->soort ? ' (' . $this->dag . ')' : '' );
				break;
			case 'extras':
				$gewijzigd    = $this->extras != $soort; // phpcs:ignore
				$this->extras  = $soort;
				$this->bericht = 'Je gaat voortaan per ' . strftime( '%d-%m-%Y', $wijzig_datum ) .
					( count( $soort ) ? ' gebruik maken van ' . implode( ', ', $soort ) : ' geen gebruik meer van extras' );
				break;
			default:
				$this->bericht = '';
		}
		if ( $gewijzigd ) {
			$this->save();
			$this->email( '_gewijzigd' );
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
				$vanaf  = strftime( '%d-%m-%Y', $this->start_datum );
				$tot    = strftime( '%d-%m-%Y', $this->driemaand_datum );
				$basis  = "{$this->soort} abonnement {$this->code} vanaf $vanaf tot $tot";
				$aantal = 3;
				break;
			case 'overbrugging':
				$vanaf  = strftime( '%d-%m-%Y', strtotime( '+1 day', $this->driemaand_datum ) );
				$tot    = strftime( '%d-%m-%Y', strtotime( '-1 day', $this->reguliere_datum ) );
				$basis  = "{$this->soort} abonnement {$this->code} vanaf $vanaf tot $tot";
				$aantal = $this->overbrugging_fractie();
				break;
			case 'regulier':
				$periode = strftime( '%B %Y', strtotime( 'today' ) );
				$basis   = "{$this->soort} abonnement {$this->code} periode $periode";
				$aantal  = 1;
				break;
			case 'pauze':
				$periode = strftime( '%B %Y', strtotime( 'today' ) );
				$basis   = "{$this->soort} abonnement {$this->code} periode $periode (deels gepauzeerd)";
				$aantal  = $this->pauze_fractie();
				break;
			default:
				$basis  = '';
				$aantal = 0;
		}
		if ( 0 < $aantal ) {
			$regels = [
				array_merge(
					self::split_bedrag( $this->bedrag() ),
					[
						'artikel' => $basis,
						'aantal'  => $aantal,
					]
				),
			];
			foreach ( $this->extras as $extra ) {
				$regels[] = array_merge(
					self::split_bedrag( $this->bedrag_extra( $extra ) ),
					[
						'artikel' => "gebruik $extra",
						'aantal'  => $aantal,
					]
				);
			}
		} else {
			$regels = [];
		}
		return $regels;
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
	 * Bereken de prijs van een extra.
	 *
	 * @param string $extra het extra element.
	 * @return float Het maandbedrag van de extra.
	 */
	private function bedrag_extra( $extra ) {
		$options = \Kleistad\Kleistad::get_options();
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
	private function bedrag( $type = '' ) {
		$options       = \Kleistad\Kleistad::get_options();
		$basis_bedrag  = (float) $options[ $this->soort . '_abonnement' ];
		$extras_bedrag = 0.0;
		foreach ( $this->extras as $extra ) {
			$extras_bedrag += $this->bedrag_extra( $extra );
		}
		switch ( $type ) {
			case '#mandaat':
				return 0.01;
			case '#start':
				return 3 * $basis_bedrag;
			case '#overbrugging':
				return $this->overbrugging_fractie() * $basis_bedrag;
			case '#regulier':
				return $basis_bedrag + $extras_bedrag;
			case '#pauze':
				return $this->pauze_fractie() * ( $basis_bedrag + $extras_bedrag );
			default:
				return $basis_bedrag;
		};
	}

	/**
	 * Geef de fractie terug wat er betaald moet worden in de overbruggingsmaand.
	 *
	 * @return float De fractie.
	 */
	private function overbrugging_fractie() {
		$overbrugging_datum = strtotime( '+1 day', $this->driemaand_datum );
		$aantal_dagen       = intval( ( $this->reguliere_datum - $overbrugging_datum ) / ( 60 * 60 * 24 ) );
		return ( 0 < $aantal_dagen ) ? round( $aantal_dagen / intval( date( 't', $this->driemaand_datum ) ), 2 ) : 0.00;
	}

	/**
	 * Geef de fractie terug wat er betaald moet worden in de huidige pauzemaand.
	 *
	 * @return float De fractie.
	 */
	private function pauze_fractie() {
		$tot_pauze      = min( 0, $this->pauze_datum - strtotime( 'first day of month 00:00' ) );
		$vanaf_herstart = min( 0, strtotime( 'last day of month 00:00' ) - $this->herstart_datum );
		$aantal_dagen   = intval( ( $tot_pauze + $vanaf_herstart ) / ( 60 * 60 * 24 ) );
		return ( 0 < $aantal_dagen ) ? round( $aantal_dagen / intval( date( 't' ) ), 2 ) : 0.00;
	}

	/**
	 * Maak een tekst met de extras inclusief vermelding van de prijs per maand.
	 */
	private function extras_lijst() {
		$lijst = [];
		foreach ( $this->extras as $extra ) {
			$lijst[] = $extra . ' ( € ' . number_format_i18n( $this->bedrag_extra( $extra ), 2 ) . ' p.m.)';
		}
		return implode( ', ', $lijst );
	}

	/**
	 * Factureer de maand
	 *
	 * @param \Kleistad\Abonnement $abonnement Het abonnement.
	 */
	private static function factureer( $abonnement ) {
		$vandaag       = strtotime( 'today' );
		$factuur_maand = (int) date( 'Ym', $vandaag );
		if ( $abonnement->factuur_maand >= $factuur_maand ) {
			return;
		}
		$volgende_maand = strtotime( 'first day of next month 00:00' );
		$deze_maand     = strtotime( 'first day of this month 00:00' );
		$betalen        = new \Kleistad\Betalen();
		// Als het abonnement in deze maand wordt gepauzeerd of herstart dan is er sprake van een gedeeltelijke .
		if ( ( $abonnement->herstart_datum > $deze_maand && $abonnement->herstart_datum < $volgende_maand ) ||
			( $abonnement->pauze_datum >= $deze_maand && $abonnement->pauze_datum < $volgende_maand ) ) {
			$abonnement->artikel_type = 'pauze';
		} elseif ( $abonnement->herstart_datum >= $volgende_maand && $abonnement->pauze_datum <= $deze_maand ) {
			return; // geen order, de gehele maand wordt gepauzeerd.
		} else {
			$abonnement->artikel_type = 'regulier';
		}
		if ( $betalen->heeft_mandaat( $abonnement->klant_id ) ) {
			$abonnement->bestel_order( 0.0, strtotime( '+14 days 0:00' ), '', $abonnement->sepa_incasso(), false );
		} else {
			$abonnement->email( '_regulier_bank', $abonnement->bestel_order( 0.0, strtotime( '+14 days 0:00' ) ) );
		}
		$abonnement->factuur_maand = $factuur_maand;
	}

	/**
	 * Dagelijkse job
	 */
	public static function dagelijks() {
		$vandaag = strtotime( 'today' );
		foreach ( self::all() as $abonnement ) {
			if ( $abonnement->geannuleerd || $vandaag < $abonnement->start_datum ) {
				// Gestopte abonnementen en abonnementen die nog moeten starten hebben geen actie nodig.
				continue;
			} elseif ( $abonnement->eind_datum && $vandaag > $abonnement->eind_datum ) {
				// Abonnementen waarvan de einddatum verstreken is worden gestopt.
				$abonnement->geannuleerd = true;
				$abonnement->autoriseer( false );
				$abonnement->save();
				continue;
			}
			$abonnement->autoriseer( true );
			// Abonnementen waarvan de driemaanden termijn over 1 week verstrijkt krijgen de overbrugging email en factuur, mits er nog geen einddatum ingevuld is.
			if ( $vandaag < $abonnement->reguliere_datum ) {
				if ( $vandaag >= strtotime( '-7 days', $abonnement->driemaand_datum ) && ! $abonnement->eind_datum && ! $abonnement->overbrugging_email ) {
					$abonnement->artikel_type = 'overbrugging';
					$abonnement->email( '_vervolg', $abonnement->bestel_order( 0.0, strtotime( '+7 days 0:00' ) ) );
					$abonnement->overbrugging_email = true;
					$abonnement->save();
				}
				continue; // Meer actie is niet nodig. Abonnee zit nog in startperiode of overbrugging.
			}
			// Abonnementen zijn gepauzeerd als het vandaag tussen de pauze en herstart datum is, anders niet.
			$abonnement->gepauzeerd = $vandaag < $abonnement->herstart_datum && $vandaag >= $abonnement->pauze_datum;
			// Hierna wordt er niets meer aan het abonnement aangepast, nu nog factureren indien nodig.
			self::factureer( $abonnement );
			$abonnement->save();
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
				$arr[ $abonnee->ID ] = new \Kleistad\Abonnement( $abonnee->ID );
			}
		}
		return $arr;
	}
}
