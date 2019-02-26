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
	 * Shortcode actief vlag.
	 *
	 * @var bool $actief Vlag om te voorkomen dat er meer dan 1 reserveringstabel getoond wordt.
	 */
	private static $actief = false;

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
		global $wpdb;
		$error = new WP_Error();

		if ( ! Kleistad_Roles::reserveer() ) {
			$error->add( 'security', 'hiervoor moet je ingelogd zijn' );
			return $error;
		}
		if ( self::$actief ) {
			$error->add( 'fout', 'er kan maar één tabel met oven reserveringen tegelijk getoond worden' );
			return $error;
		} else {
			self::$actief = true; // Voorkomen dat twee reserveringstabellen op één pagina getoond worden.
		}
		$atts = shortcode_atts(
			[ 'oven' => 'niet ingevuld' ],
			$this->atts,
			'kleistad_reservering'
		);
		if ( is_numeric( $atts['oven'] ) ) {
			$oven_id = $atts['oven'];
			$oven    = new Kleistad_Oven( $oven_id );
			if ( ! intval( $oven->id ) ) {
				$error->add( 'fout', 'oven met id ' . $oven_id . ' is niet bekend in de database !' );
				return $error;
			}
			$stokers    = [];
			$gebruikers = get_users(
				[
					'fields'  => [ 'ID', 'display_name' ],
					'orderby' => [ 'nicename' ],
				]
			);
			foreach ( $gebruikers as $gebruiker ) {
				if ( Kleistad_Roles::reserveer( $gebruiker->ID ) ) {
					$stokers[] = [
						'id'   => intval( $gebruiker->ID ),
						'naam' => $gebruiker->display_name,
					];
				}
			}
			$data = [
				'stokers'  => $stokers,
				'oven'     => [
					'id'   => $oven->id,
					'naam' => $oven->naam,
				],
				'override' => Kleistad_Roles::override(),
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
		$stoker_id     = $reservering->gereserveerd ? $reservering->verdeling[0]['id'] : $gebruiker_id;
		$stoker_naam   = get_userdata( $stoker_id )->display_name;
		$eigendom      = $reservering->verdeling[0]['id'] === $gebruiker_id;
		$logica        = [
			Kleistad_Reservering::ONGEBRUIKT    => [
				'wie'         => '',
				'temperatuur' => '',
				'kleur'       => 'white',
				'select'      => false,
				'update'      => false,
			],
			Kleistad_Reservering::RESERVEERBAAR => [
				'wie'         => '- beschikbaar -',
				'temperatuur' => '',
				'kleur'       => 'white',
				'select'      => true,
				'update'      => true,
			],
			Kleistad_Reservering::WIJZIGBAAR    => [
				'wie'         => $stoker_naam,
				'temperatuur' => $reservering->temperatuur,
				'kleur'       => Kleistad_Reservering::ONDERHOUD === $reservering->soortstook ? 'lightgray' : ( $eigendom ? 'lightgreen' : 'pink' ),
				'select'      => true,
				'update'      => $eigendom || Kleistad_Roles::override(),
			],
			Kleistad_Reservering::VERWIJDERBAAR => [
				'wie'         => $stoker_naam,
				'temperatuur' => $reservering->temperatuur,
				'kleur'       => Kleistad_Reservering::ONDERHOUD === $reservering->soortstook ? 'lightgray' : ( $eigendom ? 'lightgreen' : 'pink' ),
				'select'      => true,
				'update'      => $eigendom || Kleistad_Roles::override(),
			],
			Kleistad_Reservering::DEFINITIEF    => [
				'wie'         => $stoker_naam,
				'temperatuur' => $reservering->temperatuur,
				'kleur'       => 'white',
				'select'      => true,
				'update'      => false,
			],
		];
		$status        = $logica[ $reservering->status() ];
		$json_selectie = wp_json_encode(
			[
				'dag'          => $dag,
				'maand'        => $maand,
				'jaar'         => $jaar,
				'soortstook'   => $reservering->soortstook,
				'temperatuur'  => $reservering->gereserveerd ? $reservering->temperatuur : '',
				'programma'    => $reservering->gereserveerd ? $reservering->programma : '',
				'verdeling'    => $reservering->gereserveerd ? $reservering->verdeling : [ [ 'id' => $stoker_id, 'perc' => 100 ] ], // phpcs:ignore
				'status'       => $reservering->status(),
				'update'       => $status['update'],
				'gebruiker_id' => $gebruiker_id,
			]
		);
		if ( false === $json_selectie ) {
			$json_selectie = '{}';
		}
		$html = '<tr style="background-color:' . $status['kleur'] . ';"';
		if ( $status['select'] ) {
			$html .= 'class="kleistad_box" data-form=' . "'" . htmlspecialchars( $json_selectie, ENT_QUOTES, 'UTF-8' ) . "' ";
		}
		$html .= "><td>$dag $dagnaam</td><td>" . $status['wie'] . "</td><td>$reservering->soortstook</td><td>" . $status['temperatuur'] . '</td></tr>';
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
		$input       = $request->get_param( 'reservering' );
		$oven_id     = $request->get_param( 'oven_id' );
		$jaar        = intval( $input['jaar'] );
		$maand       = intval( $input['maand'] );
		$dag         = intval( $input['dag'] );
		$reservering = new Kleistad_Reservering( $oven_id, mktime( 23, 59, 0, $maand, $dag, $jaar ) );

		switch ( $request->get_method() ) {
			case 'POST':
				// Het betreft een toevoeging, in dit geval controleren of er niet snel door een ander een reservering is gedaan.
				if ( $reservering->gereserveerd ) {
					break;
				}
				$reservering->gebruiker_id = get_current_user_id();
				$reservering->dag          = $dag;
				$reservering->maand        = $maand;
				$reservering->jaar         = $jaar;
				$reservering->temperatuur  = intval( $input['temperatuur'] );
				$reservering->soortstook   = sanitize_text_field( $input['soortstook'] );
				$reservering->programma    = intval( $input['programma'] );
				$reservering->verdeling    = $input['verdeling'];
				$reservering->save();
				break;
			case 'PUT':
				// Het betreft een wijziging bestaande reservering. Controleer of deze al niet verwijderd is.
				if ( ! $reservering->gereserveerd ) {
					break;
				}
				$reservering->temperatuur = intval( $input['temperatuur'] );
				$reservering->soortstook  = sanitize_text_field( $input['soortstook'] );
				$reservering->programma   = intval( $input['programma'] );
				$reservering->verdeling   = $input['verdeling'];
				$reservering->save();
				break;
			case 'DELETE':
				// het betreft een annulering, controleer of deze al niet verwijderd is.
				if ( ! $reservering->gereserveerd ) {
					break;
				}
				$reservering->delete();
				break;
			default:
				break;
		}
		return new WP_REST_response(
			[
				'html'    => self::toon_reserveringen( $oven_id, $maand, $jaar ),
				'oven_id' => $oven_id,
			]
		);
	}

}
