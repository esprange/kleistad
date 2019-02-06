<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Reservering extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'saldo' form
	 *
	 * @param array $data data to be prepared.
	 * @return \WP_ERROR|bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$error = new WP_Error();
		if ( ! Kleistad_Roles::reserveer() ) {
			$error->add( 'security', 'hiervoor moet je ingelogd zijn' );
			return $error;
		}
		$atts = shortcode_atts(
			[ 'oven' => 'niet ingevuld' ],
			$this->atts,
			'kleistad_reservering'
		);
		if ( is_numeric( $atts['oven'] ) ) {
			$oven_id = $atts['oven'];

			$oven = new Kleistad_Oven( $oven_id );
			if ( ! intval( $oven->id ) ) {
				$error->add( 'fout', 'oven met id ' . $oven_id . ' is niet bekend in de database !' );
				return $error;
			}

			$gebruikers = get_users(
				[
					'fields'  => [ 'ID', 'display_name' ],
					'orderby' => [ 'nicename' ],
				]
			);

			$stokers = [];
			foreach ( $gebruikers as $gebruiker ) {
				if ( Kleistad_Roles::reserveer( $gebruiker->ID ) ) {
					$stokers[] = $gebruiker;
				}
			}
			$huidige_gebruiker = wp_get_current_user();
			$data              = [
				'stokers'           => $stokers,
				'oven'              => $oven,
				'huidige_gebruiker' => $huidige_gebruiker,
			];
			return true;
		} else {
			$error->add( 'fout', 'de shortcode bevat geen oven nummer tussen 1 en 999 !' );
			return $error;
		}
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 4.5.3
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Kleistad_Public::url(),
			'/reserveer',
			[
				'methods'             => 'POST,PUT,DELETE',
				'callback'            => [ __CLASS__, 'callback_muteer' ],
				'args'                => [
					'reservering' => [
						'required' => true,
					],
					'oven_id'     => [
						'required' => true,
						'type'     => 'int',
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			]
		);
		register_rest_route(
			Kleistad_Public::url(),
			'/reserveer',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_show' ],
				'args'                => [
					'maand'   => [
						'required' => true,
						'type'     => 'int',
					],
					'jaar'    => [
						'required' => true,
						'type'     => 'int',
					],
					'oven_id' => [
						'required' => true,
						'type'     => 'int',
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			]
		);
	}

	/**
	 * Toon de reserveringen voor bepaalde maand
	 *
	 * @param int $oven_id Het id van de oven.
	 * @param int $maand   De maand.
	 * @param int $jaar    Het jaar.
	 * @return string De Html code.
	 */
	private static function toon_reserveringen( $oven_id, $maand, $jaar ) {
		$rows                 = [];
		$volgende_maand       = intval( date( 'n', mktime( 0, 0, 0, $maand + 1, 1, $jaar ) ) );
		$vorige_maand         = intval( date( 'n', mktime( 0, 0, 0, $maand - 1, 1, $jaar ) ) );
		$volgende_maand_jaar  = intval( date( 'Y', mktime( 0, 0, 0, $maand + 1, 1, $jaar ) ) );
		$vorige_maand_jaar    = intval( date( 'Y', mktime( 0, 0, 0, $maand - 1, 1, $jaar ) ) );
		$aantaldagen          = intval( date( 't', mktime( 0, 0, 0, $maand, 1, $jaar ) ) );
		$huidige_gebruiker_id = get_current_user_id();
		$oven                 = new Kleistad_Oven( $oven_id );
		for ( $dag = 1; $dag <= $aantaldagen; $dag++ ) {
			$datum   = mktime( 23, 59, 0, $maand, $dag, $jaar );
			$dagnaam = strftime( '%A', $datum );
			if ( ! $oven->{$dagnaam} ) {
				continue;
			}
			$reservering      = new Kleistad_Reservering( $oven_id, $datum );
			$stoker_id        = $reservering->actief ? $reservering->gebruiker_id : $huidige_gebruiker_id;
			$stoker_naam      = get_userdata( $stoker_id )->display_name;
			$datum_verstreken = $datum < strtotime( 'today' );

			if ( $stoker_id === $huidige_gebruiker_id ) {
				$kleur         = $reservering->actief && ! $datum_verstreken ? 'lightgreen' : 'white';
				$verwijderbaar = Kleistad_Roles::override() ? ( ! $reservering->verwerkt ) : ( ! $datum_verstreken );
				$wijzigbaar    = ( ! $reservering->verwerkt ) || is_super_admin();
			} else {
				$kleur         = $reservering->actief && ! $datum_verstreken ? 'pink' : 'white';
				$verwijderbaar = Kleistad_Roles::override() ? ( ! $reservering->verwerkt ) : false;
				$wijzigbaar    = $verwijderbaar || is_super_admin();
			}
			$selectie = [
				'oven_id'       => $oven_id,
				'dag'           => $dag,
				'maand'         => $maand,
				'jaar'          => $jaar,
				'soortstook'    => $reservering->soortstook,
				'temperatuur'   => $reservering->temperatuur,
				'programma'     => $reservering->programma,
				'verdeling'     => $reservering->actief ? $reservering->verdeling : [
					[
						'id'   => $stoker_id,
						'perc' => 100,
					],
				],
				'gereserveerd'  => $reservering->actief ? 1 : 0,
				'verwijderbaar' => $verwijderbaar ? 1 : 0,
				'wijzigbaar'    => $wijzigbaar ? 1 : 0,
				'wie'           => $reservering->actief ? $stoker_naam : ( ( $wijzigbaar && ! $datum_verstreken ) ? '-beschikbaar-' : '' ),
				'gebruiker_id'  => $stoker_id,
				'gebruiker'     => $stoker_naam,
			];

			if ( Kleistad_Reservering::ONDERHOUD === $reservering->soortstook ) {
				$kleur = 'gray';
			}
			$row_html      = "<tr style=\"background-color: $kleur\">";
			$json_selectie = wp_json_encode( $selectie );
			if ( false !== $json_selectie && $wijzigbaar ) {
				$row_html .= "<td><a class=\"kleistad_box\" data-form='$json_selectie' >$dag $dagnaam</a></td>";
			} else {
				$row_html .= "<td>$dag $dagnaam</td>";
			}
			$row_html .= "<td>{$selectie['wie']}</td><td>{$selectie['soortstook']}</td><td>{$selectie['temperatuur']}</td></tr>";
			$rows[]    = $row_html;
		}

		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/kleistad-public-show-reservering.php';
		$html = ob_get_contents();
		ob_clean();
		return $html;
	}


	/**
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response Ajax response.
	 */
	public static function callback_show( WP_REST_Request $request ) {
		$oven_id = intval( $request->get_param( 'oven_id' ) );
		$maand   = intval( $request->get_param( 'maand' ) );
		$jaar    = intval( $request->get_param( 'jaar' ) );
		return new WP_REST_response(
			[
				'html'    => self::toon_reserveringen( $oven_id, $maand, $jaar ),
				'oven_id' => $oven_id,
			]
		);
	}

	/**
	 *
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response Ajax response.
	 */
	public static function callback_muteer( WP_REST_Request $request ) {
		$method       = $request->get_method();
		$input        = $request->get_param( 'reservering' );
		$oven_id      = $request->get_param( 'oven_id' );
		$gebruiker_id = intval( $input['gebruiker_id'] );
		$jaar         = intval( $input['jaar'] );
		$maand        = intval( $input['maand'] );
		$dag          = intval( $input['dag'] );
		$reservering  = new Kleistad_Reservering( $oven_id, mktime( 23, 59, 0, $maand, $dag, $jaar ) );

		if ( 'PUT' === $method || 'POST' === $method ) {
			// het betreft een toevoeging of wijziging, in het eerste geval controleren of er niet snel door een ander een reservering is gedaan.
			if ( ! $reservering->actief || ( $reservering->gebruiker_id == $gebruiker_id ) || Kleistad_Roles::override() ) { // phpcs:ignore
				$reservering->gebruiker_id = $gebruiker_id;
				$reservering->dag          = $dag;
				$reservering->maand        = $maand;
				$reservering->jaar         = $jaar;
				$reservering->temperatuur  = intval( $input['temperatuur'] );
				$reservering->soortstook   = sanitize_text_field( $input['soortstook'] );
				$reservering->programma    = intval( $input['programma'] );
				$reservering->verdeling    = $input['verdeling'];
				$reservering->save();
			}
		} elseif ( 'DELETE' === $method ) {
			// het betreft een annulering, controleer of deze al niet verwijderd is.
			if ( $reservering->actief && ( ( $reservering->gebruiker_id == $gebruiker_id ) || Kleistad_Roles::override() ) ) { // phpcs:ignore
				$reservering->delete();
			}
		}
		return new WP_REST_response(
			[
				'html'    => self::toon_reserveringen( $oven_id, $maand, $jaar ),
				'oven_id' => $oven_id,
			]
		);
	}

}
