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
			'abonnee_inschrijving'  => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-datepicker', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [],
			],
			'abonnee_wijziging'     => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-datepicker', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [ LID ],
			],
			'abonnement_overzicht'  => (object) [
				'script' => false,
				'js'     => [ 'jquery', 'datatables' ],
				'css'    => [ 'datatables' ],
				'access' => [ BESTUUR ],
			],
			'betaling'              => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'datatables', 'jquery-ui-selectmenu' ],
				'css'    => [ 'datatables', 'jquery-ui' ],
				'access' => [],
			],
			'contact'               => (object) [
				'script' => false,
				'js'     => [],
				'css'    => [],
				'access' => [],
			],
			'cursus_beheer'         => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
				'access' => [ BESTUUR ],
			],
			'cursus_extra'          => (object) [
				'script' => true,
				'js'     => [ 'jquery' ],
				'css'    => [],
				'access' => [],
			],
			'cursus_inschrijving'   => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-spinner', 'jquery-ui-tooltip', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [],
			],
			'cursus_overzicht'      => (object) [
				'script' => false,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
				'access' => [ DOCENT, BESTUUR ],
			],
			'dagdelenkaart'         => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [],
			],
			'debiteuren'            => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables' ],
				'access' => [ BOEKHOUD ],
			],
			'email'                 => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jstree' ],
				'css'    => [ 'jquery-ui', 'jstree' ],
				'access' => [ DOCENT, BESTUUR ],
			],
			'kalender'              => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'fullcalendar' ],
				'css'    => [ 'fullcalendar' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			'omzet_rapportage'      => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables' ],
				'access' => [ BESTUUR ],
			],
			'rapport'               => (object) [
				'script' => false,
				'js'     => [ 'jquery', 'datatables' ],
				'css'    => [ 'datatables', 'dashicons' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			'recept_beheer'         => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			'recept'                => (object) [
				'script' => true,
				'js'     => [ 'jquery' ],
				'css'    => [ 'dashicons' ],
				'access' => [],
			],
			'registratie_overzicht' => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
				'access' => [ BESTUUR ],
			],
			'registratie'           => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'password-strength-meter' ],
				'css'    => [],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			'reservering'           => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog' ],
				'css'    => [ 'jquery-ui', 'dashicons' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			'saldo_overzicht'       => (object) [
				'script' => false,
				'js'     => [ 'jquery', 'datatables' ],
				'css'    => [ 'datatables' ],
				'access' => [ BESTUUR ],
			],
			'saldo'                 => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-selectmenu' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [ DOCENT, LID, BESTUUR ],
			],
			'stookbestand'          => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [ BESTUUR ],
			],
			'verkoop'               => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs' ],
				'css'    => [ 'jquery-ui' ],
				'access' => [ BESTUUR ],
			],
			'werkplek'              => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-dialog' ],
				'css'    => [ 'jquery-ui', 'dashicons' ],
				'access' => [ DOCENT, LID, BESTUUR, 'cursist-1' ],
			],
			'werkplekrapport'       => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-datepicker', 'datatables' ],
				'css'    => [ 'jquery-ui', 'datatables' ],
				'access' => [ BESTUUR ],
			],
			'workshop_aanvraag'     => (object) [
				'script' => false,
				'js'     => [ 'jquery' ],
				'css'    => [],
				'access' => [],
			],
			'workshop_beheer'       => (object) [
				'script' => true,
				'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'datatables' ],
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
			return $gebruiker->ID && 0 !== count( array_intersect( $access, (array) $gebruiker->roles ) );
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
