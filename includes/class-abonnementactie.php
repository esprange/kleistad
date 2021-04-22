<?php
/**
 * Definieer de abonnement actie class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_User;

/**
 * Kleistad AbonnementActie class.
 *
 * @since 6.14.7
 */
class AbonnementActie {

	/**
	 * Het abonnement
	 *
	 * @var Abonnement $abonnement Het abonnement.
	 */
	private Abonnement $abonnement;

	/**
	 * Constructor
	 *
	 * @param Abonnement $abonnement Het abonnement.
	 */
	public function __construct( Abonnement $abonnement ) {
		$this->abonnement = $abonnement;
	}

	/**
	 * Wijzig de betaalwijze van het abonnement naar sepa incasso.
	 *
	 * @return string|bool De redirect uri of false als de betaling niet lukt.
	 */
	public function start_incasso() {
		$this->log( 'gestart met automatisch betalen' );
		$this->abonnement->save();
		$this->abonnement->artikel_type = 'mandaat';
		$this->abonnement->bericht      = 'Kleistad gaat voortaan automatisch het abonnementsgeld afschrijven van jouw bankrekening';
		return $this->abonnement->betaling->doe_ideal( 'Bedankt voor de betaling! De wijziging is verwerkt en er wordt een email verzonden met bevestiging', 0.01 );
	}

	/**
	 * Wijzig de betaalwijze van het abonnement naar bank.
	 *
	 * @return bool
	 */
	public function stop_incasso() : bool {
		$betalen = new Betalen();
		$betalen->verwijder_mandaat( $this->abonnement->klant_id );
		$this->log( 'gestopt met automatisch betalen' );
		$this->abonnement->save();
		if ( ! is_admin() ) {
			$this->abonnement->bericht = 'Je gaat het abonnement voortaan per bank betalen';
			$this->abonnement->verzend_email( '_gewijzigd' );
		}
		return true;
	}

	/**
	 * Pauzeer het abonnement per pauze datum.
	 *
	 * @param int $pauze_datum    Pauzedatum.
	 * @param int $herstart_datum Herstartdatum.
	 * @return bool
	 */
	public function pauzeren( int $pauze_datum, int $herstart_datum ) : bool {
		$thans_gepauzeerd                 = $this->abonnement->is_gepauzeerd();
		$this->abonnement->pauze_datum    = $pauze_datum;
		$this->abonnement->herstart_datum = $herstart_datum;
		$pauze_datum_str                  = strftime( '%d-%m-%Y', $this->abonnement->pauze_datum );
		$herstart_datum_str               = strftime( '%d-%m-%Y', $this->abonnement->herstart_datum );
		$this->log( "gepauzeerd per $pauze_datum_str en hervat per $herstart_datum_str" );
		$this->abonnement->save();
		$this->abonnement->bericht     = ( $thans_gepauzeerd ) ?
			"Je hebt aangegeven dat je abonnement, dat nu gepauzeerd is, hervat wordt per $herstart_datum_str"
			:
			$this->abonnement->bericht = "Je pauzeert het abonnement per $pauze_datum_str en hervat het per $herstart_datum_str";
		if ( ! is_admin() ) {
			$this->abonnement->verzend_email( '_gewijzigd' );
		}
		return true;
	}

		/**
		 * Start het abonnement per datum.
		 *
		 * @param int    $start_datum Startdatum.
		 * @param string $soort       Beperkt of onbeperkt.
		 * @param string $dag         De dagnaam bij beperkt.
		 * @param string $opmerking   De opmerking.
		 * @param string $betaalwijze De betaalwijze.
		 * @return string|bool Een uri ingeval van betalen per ideal, true als per bank, false als ideal betaling niet mogelijk is.
		 */
	public function starten( int $start_datum, string $soort, string $dag, string $opmerking, string $betaalwijze ) {
		$start_bedrag                         = 3 * opties()[ "{$soort}_abonnement" ];
		$this->abonnement->code               = "A{$this->abonnement->klant_id}";
		$this->abonnement->datum              = time();
		$this->abonnement->soort              = $soort;
		$this->abonnement->opmerking          = $opmerking;
		$this->abonnement->start_datum        = $start_datum;
		$this->abonnement->start_eind_datum   = strtotime( '+3 month', $start_datum );
		$this->abonnement->reguliere_datum    = strtotime( 'first day of +4 month ', $start_datum );
		$this->abonnement->pauze_datum        = 0;
		$this->abonnement->herstart_datum     = 0;
		$this->abonnement->eind_datum         = 0;
		$this->abonnement->dag                = $dag;
		$this->abonnement->artikel_type       = 'start';
		$this->abonnement->overbrugging_email = false;
		$this->abonnement->extras             = [];
		$this->abonnement->factuur_maand      = 'ideal' === $betaalwijze ? 0 : (int) date( 'Ym' );
		$this->autoriseer( true );
		$this->abonnement->save();
		if ( 'ideal' === $betaalwijze ) {
			return $this->abonnement->betaling->doe_ideal( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $start_bedrag );
		}
		$this->abonnement->verzend_email( '_start_bank', $this->abonnement->bestel_order( 0.0, $this->abonnement->start_datum ) );
		return true;
	}

		/**
		 * Stop het abonnement per datum.
		 *
		 * @param int $eind_datum Einddatum.
		 * @return bool
		 */
	public function stoppen( int $eind_datum ) : bool {
		$this->abonnement->eind_datum = $eind_datum;
		$eind_datum_str               = strftime( '%d-%m-%Y', $this->abonnement->eind_datum );
		$betalen                      = new Betalen();
		$betalen->verwijder_mandaat( $this->abonnement->klant_id );
		$this->log( "gestopt per $eind_datum_str" );
		$this->abonnement->bericht = "Je hebt het abonnement per $eind_datum_str beëindigd.";
		$this->abonnement->save();
		if ( ! is_admin() ) {
			$this->abonnement->verzend_email( '_gewijzigd' );
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
	 * @return bool
	 */
	public function wijzigen( int $wijzig_datum, string $type, $soort, string $dag = '' ) : bool {
		$gewijzigd        = false;
		$wijzig_datum_str = strftime( '%d-%m-%Y', $wijzig_datum );
		switch ( $type ) {
			case 'soort':
				$gewijzigd      = $this->abonnement->soort != $soort || $this->abonnement->dag != $dag; // phpcs:ignore
				$this->abonnement->soort = $soort;
				$this->abonnement->dag   = $dag;
				$this->log( "gewijzigd per $wijzig_datum_str naar $soort $dag" );
				$this->abonnement->bericht = "Je hebt het abonnement per $wijzig_datum_str gewijzigd naar {$this->abonnement->soort} " .
					( 'beperkt' === $this->abonnement->soort ? ' (' . $this->abonnement->dag . ')' : '' );
				break;
			case 'extras':
				$gewijzigd    = $this->abonnement->extras != $soort; // phpcs:ignore
				$this->abonnement->extras = $soort;
				$soort_str                = ! is_null( $soort ) ? ( 'gebruik maken van ' . implode( ', ', $soort ) ) : 'geen extras meer gebruiken';
				$this->log( "extras gewijzigd per $wijzig_datum_str naar $soort_str" );
				$this->abonnement->bericht = "Je gaat voortaan per $wijzig_datum_str $soort_str";
				break;
			default:
				$this->abonnement->bericht = '';
		}
		if ( $gewijzigd ) {
			$this->abonnement->save();
			$this->abonnement->verzend_email( '_gewijzigd' );
		}
		return true;
	}

	/**
	 * Geef aan dat er een overbrugging betaald moet worden
	 */
	public function overbrugging() {
		$this->abonnement->artikel_type = 'overbrugging';
		$this->abonnement->verzend_email( '_vervolg', $this->abonnement->bestel_order( 0.0, strtotime( '+7 days 0:00' ) ) );
		$this->abonnement->overbrugging_email = true;
		$this->abonnement->save();
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
		if ( $this->abonnement->factuur_maand >= $factuur_maand ||
		( $this->abonnement->herstart_datum >= $volgende_maand && $this->abonnement->pauze_datum <= $deze_maand )
		) {
			return;
		}
		// Als het abonnement in deze maand wordt gepauzeerd of herstart dan is er sprake van een gedeeltelijke .
		$this->abonnement->artikel_type = ( ( $this->abonnement->herstart_datum > $deze_maand && $this->abonnement->herstart_datum < $volgende_maand ) ||
			( $this->abonnement->pauze_datum >= $deze_maand && $this->abonnement->pauze_datum < $volgende_maand ) ) ? 'pauze' : 'regulier';
		if ( $this->abonnement->betaling->incasso_actief() ) {
			$this->abonnement->bestel_order( 0.0, strtotime( '+14 days 0:00' ), '', $this->abonnement->betaling->doe_sepa_incasso(), false );
		} else {
			$this->abonnement->verzend_email( '_regulier_bank', $this->abonnement->bestel_order( 0.0, strtotime( '+14 days 0:00' ) ) );
		}
		$this->abonnement->factuur_maand = $factuur_maand;
		$this->abonnement->save();
	}

	/**
	 * Autoriseer de abonnee zodat deze de oven reservering mag doen en toegang tot leden pagina's krijgt.
	 *
	 * @param boolean $valid Als true, geef de autorisatie, als false haal de autorisatie weg.
	 */
	public function autoriseer( bool $valid ) {
		$abonnee = new WP_User( $this->abonnement->klant_id );
		if ( is_super_admin( $this->abonnement->klant_id ) ) {
			// Voorkom dat de admin enige rol kwijtraakt.
			return;
		}
		$abonnee->add_cap( LID, $valid );
	}


	/**
	 * Helper functie, om een handeling toe te voegen
	 *
	 * @param string $tekst De handeling.
	 */
	private function log( string $tekst ) : void {
		$this->abonnement->historie = array_merge( $this->abonnement->historie, [ strftime( '%c' ) . " $tekst" ] );
	}

}
