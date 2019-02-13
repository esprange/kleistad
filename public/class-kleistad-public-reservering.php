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
	 * Prepareer 'reservering' form
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
	 * Maak een regel op van de reserveringen tabel
	 *
	 * @param int    $oven_id het id van de oven.
	 * @param string $dagnaam naam van de dag.
	 * @param int    $maand   maand van de reservering.
	 * @param int    $dag     dag van de reservering.
	 * @param int    $jaar    jaar van de reservering.
	 * @return string html opgemaakte tekstregel.
	 */
	private static function maak_regel( $oven_id, $dagnaam, $maand, $dag, $jaar ) {
		$reservering   = new Kleistad_Reservering( $oven_id, mktime( 0, 0, 0, $maand, $dag, $jaar ) );
		$gebruiker_id  = get_current_user_id();
		$stoker_id     = $reservering->actief ? $reservering->gebruiker_id : $gebruiker_id;
		$stoker_naam   = get_userdata( $stoker_id )->display_name;
		$reserveerbaar = strtotime( "$jaar-$maand-$dag 23:59" ) >= strtotime( 'today' );
		if ( $reservering->actief ) {
			if ( $reservering->gebruiker_id === $gebruiker_id ) {
				/**
				 * Er is een bestaande reservering van de ingelogde stoker.
				 */
				$kleur = 'lightgreen';
				$wie   = $stoker_naam;
				/**
				 * Zolang de datum van de reservering nog niet in het verleden ligt mag deze verwijderd worden.
				 */
				$verwijderbaar = $reserveerbaar && ! $reservering->verwerkt;
				/**
				 * Zolang de reservering nog niet financieel verwerkt is mag deze gewijzigd worden.
				 */
				$wijzigbaar = ! $reservering->verwerkt;
			} else {
				/**
				 * Reservering aangemaakt door een andere stoker.
				 */
				$kleur = 'pink';
				$wie   = $stoker_naam;
				/**
				 * Als er al een reservering is en die is nog niet verwerkt dan mag een bestuurslid die verwijderen.
				 */
				$verwijderbaar = ! $reservering->verwerkt && Kleistad_Roles::override();
				/**
				 * Als er wel een reservering actief is en deze is nog niet verwerkt dan mag deze gewijzigd worden door een bestuurslid.
				 */
				$wijzigbaar = $verwijderbaar;
			}
		} else {
			$kleur = 'white';
			$wie   = $reserveerbaar ? '-beschikbaar-' : '';
			/**
			 * Als er geen reservering actief is en de datum ligt niet in het verleden dan mag er een reservering aangemaakt worden.
			 * Alleen de beheerder kan ook in het verleden een reservering aanmaken.
			 */
			$verwijderbaar = false;
			$wijzigbaar    = $reserveerbaar || is_super_admin();
		}
		$kleur         = $reserveerbaar ? ( Kleistad_Reservering::ONDERHOUD === $reservering->soortstook ? 'gray' : $kleur ) : 'white';
		$temperatuur   = 0 !== $reservering->temperatuur ? $reservering->temperatuur : '';
		$json_selectie = wp_json_encode(
			[
				'oven_id'       => $oven_id,
				'dag'           => $dag,
				'maand'         => $maand,
				'jaar'          => $jaar,
				'soortstook'    => $reservering->soortstook,
				'temperatuur'   => $reservering->actief ? $reservering->temperatuur : '',
				'programma'     => $reservering->actief ? $reservering->programma : '',
				'verdeling'     => $reservering->actief ? $reservering->verdeling : [ [ 'id' => $stoker_id, 'perc' => 100 ] ], // phpcs:ignore
				'gereserveerd'  => $reservering->actief,
				'verwijderbaar' => $verwijderbaar,
				'gebruiker_id'  => $stoker_id,
				'gebruiker'     => $stoker_naam,
			]
		);

		$html = "<tr style=\"background-color: $kleur\">";
		if ( false !== $json_selectie && $wijzigbaar ) {
			$html .= "<td><a class=\"kleistad_box\" data-form='" . htmlspecialchars( $json_selectie, ENT_QUOTES, 'UTF-8' ) . "' >$dag $dagnaam</a></td>";
		} else {
			$html .= "<td>$dag $dagnaam</td>";
		}
		$html .= "<td>$wie</td><td>$reservering->soortstook</td><td>$temperatuur</td></tr>";
		return $html;
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
		$tabelinhoud = '';
		$aantaldagen = intval( date( 't', mktime( 0, 0, 0, $maand, 1, $jaar ) ) );
		$oven        = new Kleistad_Oven( $oven_id );
		for ( $dag = 1; $dag <= $aantaldagen; $dag++ ) {
			$dagnaam = strftime( '%A', mktime( 0, 0, 0, $maand, $dag, $jaar ) );
			if ( ! $oven->{$dagnaam} ) {
				continue;
			}
			$tabelinhoud .= self::maak_regel( $oven_id, $dagnaam, $maand, $dag, $jaar );
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
