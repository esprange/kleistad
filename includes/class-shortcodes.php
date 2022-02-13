<?php
/**
 * De  class voor shortcode definities.
 *
 * @link       https://www.kleistad.nl
 * @since      6.12.3
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * De class voor de shortcode definities
 */
class Shortcodes {

	/**
	 * De shortcodes van kleistad
	 *
	 * @var array shortcodes met hun style en jscript afhankelijkheden.
	 */
	public array $definities;

	/**
	 * De constructor.
	 *
	 * @suppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function __construct() {
		$this->definities =
		[
			/**
			 * Inschrijving abonnee
			 */
			'abonnee_inschrijving'  => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-datepicker', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [],
			],
			/**
			 * Abonnement wijzigen
			 */
			'abonnee_wijziging'     => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-datepicker', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [ LID ],
			],
			/**
			 * Abonnee overzicht
			 */
			'abonnement_overzicht'  => (object) [
				'script' => false,
				'js'     => [ 'jquery', 'datatables' ],
				'css'    => [ 'datatables' ],
				'access' => [ BESTUUR ],
			],
			/**
			 * Betaling via link
			 */
			'betaling'              => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables', 'jquery-ui-selectmenu' ],
				'css'    => [ 'datatables', 'jquery-ui' ],
				'access' => [],
			],
			/**
			 * Contact formulier
			 */
			'contact'               => (object) [
				'script' => false,
				'js'     => [],
				'css'    => [],
				'access' => [],
			],
			/**
			 * Cursus beheer
			 *    Overzicht
			 *    Toevoegen
			 *    Wijzigen
			 */
			'cursus_beheer'         => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
				'access' => [ BESTUUR ],
			],
			/**
			 * Extra cursisten aanmelden
			 */
			'cursus_extra'          => (object) [
				'script' => true,
				'js'     => [ 'jquery' ],
				'css'    => [],
				'access' => [],
			],
			/**
			 * Inschrijven op cursus
			 *    Inschrijven
			 *    Indelen_na_wachten
			 *    Stop_wachten
			 */
			'cursus_inschrijving'   => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-spinner', 'jquery-ui-tooltip', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [],
			],
			/**
			 * Overzicht cursussen
			 *    Cursisten
			 *    Indelen
			 *    Uitschrijven
			 *    Overzicht
			 */
			'cursus_overzicht'      => (object) [
				'script' => false,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
				'access' => [ DOCENT, BESTUUR ],
			],
			/**
			 * Dagdelenkaart kopen
			 */
			'dagdelenkaart'         => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [],
			],
			/**
			 * Debiteuren overzicht
			 *    Blokkade
			 *    Debiteur
			 *    Zoek
			 *    Overzicht
			 */
			'debiteuren'            => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables' ],
				'access' => [ BOEKHOUD ],
			],
			/**
			 * Docent
			 *    Planning
			 */
			'docent'                => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [ DOCENT, BESTUUR ],
			],
			/**
			 * Verzend email
			 */
			'email'                 => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jstree' ],
				'css'    => [ 'jquery-ui', 'jstree' ],
				'access' => [ DOCENT, BESTUUR ],
			],
			/**
			 * Toon kalender
			 */
			'kalender'              => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'fullcalendar' ],
				'css'    => [ 'fullcalendar' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			/**
			 * Toon omzet rapportage
			 *    Details
			 *    Overzicht
			 */
			'omzet_rapportage'      => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables' ],
				'access' => [ BESTUUR ],
			],
			/**
			 * Toon stook rapport
			 */
			'rapport'               => (object) [
				'script' => false,
				'js'     => [ 'jquery', 'datatables' ],
				'css'    => [ 'datatables', 'dashicons' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			/**
			 * Beheer recepten
			 *    Toveoegen
			 *    Wijzigen
			 *    Overzicht
			 */
			'recept_beheer'         => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			/**
			 * Toon recept
			 */
			'recept'                => (object) [
				'script' => true,
				'js'     => [ 'jquery' ],
				'css'    => [ 'dashicons' ],
				'access' => [],
			],
			/**
			 * Toon overzicht van registraties
			 */
			'registratie_overzicht' => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
				'access' => [ BESTUUR ],
			],
			/**
			 * Registratie gebruikergegevens
			 */
			'registratie'           => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'password-strength-meter' ],
				'css'    => [],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			/**
			 * Reservering stook
			 */
			'reservering'           => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog' ],
				'css'    => [ 'jquery-ui', 'dashicons' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			/**
			 * Bijstorten saldo
			 */
			'saldo'                 => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			/**
			 * Overzicht alle stokers
			 */
			'stookbestand'          => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [ BESTUUR ],
			],
			/**
			 * Verkoop overige artikelen
			 */
			'verkoop'               => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [ BESTUUR ],
			],
			/**
			 * Werkplek reservering
			 */
			'werkplek'              => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-dialog' ],
				'css'    => [ 'jquery-ui', 'dashicons' ],
				'access' => [ DOCENT, LID, BESTUUR, 'cursist-1' ],
			],
			/**
			 * Rapportage Werkplekgebruik
			 *    Individueel
			 *    Overzicht
			 */
			'werkplekrapport'       => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables' ],
				'access' => [ BESTUUR ],
			],
			/**
			 * Aanvraag workshop
			 */
			'workshop_aanvraag'     => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [],
			],
			/**
			 * Beheer workshops
			 *    Toevoegen
			 *    Wijzigen
			 *    Inplannen
			 *    Tonen
			 *    Overzicht
			 */
			'workshop_beheer'       => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'jquery-ui-tabs', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables' ],
				'access' => [ BESTUUR ],
			],
		];
	}

	/**
	 * Controleer toegang tot deze shortcode.
	 *
	 * @since 5.7.2
	 *
	 * @param string $shortcode De shortcode.
	 * @return bool Of er toegang is.
	 */
	public function check_access( string $shortcode ) : bool {
		$access = $this->definities[ $shortcode ]->access;
		if ( ! empty( $access ) ) {
			$gebruiker = wp_get_current_user();
			return $gebruiker->ID && 0 !== count( array_intersect( $access, $gebruiker->roles ) );
		}
		return true;
	}

	/**
	 * Controleer of huidige pagina een shortcode bevat.
	 *
	 * @return array Array of shortcodes.
	 */
	public function heeft_shortcode() : array {
		global $post;
		$tags   = [];
		$result = [];
		if ( is_a( $post, 'WP_Post' ) ) {
			foreach ( array_keys( $this->definities ) as $tag ) {
				$tags[] = "kleistad_$tag";
			}
			preg_match_all( '/' . get_shortcode_regex( $tags ) . '/', $post->post_content, $matches, PREG_SET_ORDER );
			foreach ( $matches as $match ) {
				$result[] = substr( $match[2], strlen( 'kleistad_' ) );
			}
		}
		return $result;
	}

}
