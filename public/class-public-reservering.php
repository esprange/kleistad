<?php
/**
 * De overn reservering.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De reservering form.
 */
class Public_Reservering extends Shortcode {

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
	protected function prepare( &$data = null ) {
		global $wpdb;
		$error = new \WP_Error();

		if ( ! \Kleistad\Roles::reserveer() ) {
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
			$oven    = new \Kleistad\Oven( $oven_id );
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
				if ( \Kleistad\Roles::reserveer( $gebruiker->ID ) ) {
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
				'override' => \Kleistad\Roles::override(),
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
			Public_Main::api(),
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
			Public_Main::api(),
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
		$reservering  = new \Kleistad\Reservering( $oven_id, mktime( 0, 0, 0, $maand, $dag, $jaar ) );
		$gebruiker_id = get_current_user_id();
		$stoker_id    = $reservering->gereserveerd ? $reservering->verdeling[0]['id'] : $gebruiker_id;
		$stoker_naam  = get_userdata( $stoker_id )->display_name;
		$kleur        = \Kleistad\Reservering::ONDERHOUD === $reservering->soortstook ? 'kleistad_reservering_onderhoud' :
			( $reservering->verdeling[0]['id'] === $gebruiker_id ? 'kleistad_reservering_zelf' : 'kleistad_reservering_ander' );
		$logica       = [
			\Kleistad\Reservering::ONGEBRUIKT    => [
				'wie'          => '',
				'temperatuur'  => '',
				'programma'    => '',
				'verdeling'    => [],
				'soortstook'   => '',
				'kleur'        => 'kleistad_reservering_ongebruikt',
				'select'       => false,
				'gebruiker_id' => 0,
			],
			\Kleistad\Reservering::RESERVEERBAAR => [
				'wie'          => '- beschikbaar -',
				'temperatuur'  => '',
				'programma'    => '',
				'verdeling'    => [ [ 'id' => $stoker_id, 'perc' => 100 ] ], // phpcs:ignore
				'soortstook'   => '',
				'kleur'        => 'kleistad_reservering_reserveerbaar',
				'select'       => true,
				'gebruiker_id' => $gebruiker_id,
			],
			\Kleistad\Reservering::WIJZIGBAAR    => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $reservering->temperatuur,
				'programma'    => $reservering->programma,
				'verdeling'    => $reservering->verdeling,
				'soortstook'   => $reservering->soortstook,
				'kleur'        => $kleur,
				'select'       => true,
				'gebruiker_id' => $reservering->gebruiker_id,
			],
			\Kleistad\Reservering::ALLEENLEZEN   => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $reservering->temperatuur,
				'programma'    => $reservering->programma,
				'verdeling'    => $reservering->verdeling,
				'soortstook'   => $reservering->soortstook,
				'kleur'        => $kleur,
				'select'       => true,
				'gebruiker_id' => $reservering->gebruiker_id,
			],
			\Kleistad\Reservering::VERWIJDERBAAR => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $reservering->temperatuur,
				'programma'    => $reservering->programma,
				'verdeling'    => $reservering->verdeling,
				'soortstook'   => $reservering->soortstook,
				'kleur'        => $kleur,
				'select'       => true,
				'gebruiker_id' => $reservering->gebruiker_id,
			],
			\Kleistad\Reservering::DEFINITIEF    => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $reservering->temperatuur,
				'programma'    => $reservering->programma,
				'verdeling'    => $reservering->verdeling,
				'soortstook'   => $reservering->soortstook,
				'kleur'        => 'kleistad_reservering_definitief',
				'select'       => true,
				'gebruiker_id' => $reservering->gebruiker_id,
			],
		];
		$status       = $logica[ $reservering->status() ];
		$html         = "<tr class=\"{$status['kleur']}\"";
		if ( $status['select'] ) {
			$json_selectie = wp_json_encode(
				[
					'dag'          => $dag,
					'maand'        => $maand,
					'jaar'         => $jaar,
					'soortstook'   => $status['soortstook'],
					'temperatuur'  => $status['temperatuur'],
					'programma'    => $status['programma'],
					'verdeling'    => $status['verdeling'],
					'status'       => $reservering->status(),
					'kleur'        => $status['kleur'],
					'gebruiker_id' => $status['gebruiker_id'],
				]
			);
			if ( false === $json_selectie ) {
				$json_selectie = '{}';
			}
			$html .= "data-form='" . htmlspecialchars( $json_selectie, ENT_QUOTES, 'UTF-8' ) . "' ";
		}
		$html .= "><td>$dag $dagnaam</td><td> {$status['wie']}</td><td>{$status['soortstook']}</td><td>{$status['temperatuur']}</td></tr>";
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
		$oven        = new \Kleistad\Oven( $oven_id );
		for ( $dag = 1; $dag <= $aantaldagen; $dag++ ) {
			$dagnaam = strftime( '%A', mktime( 0, 0, 0, $maand, $dag, $jaar ) );
			if ( ! $oven->{$dagnaam} ) {
				continue;
			}
			$tabelinhoud .= self::maak_regel( $oven_id, $dagnaam, $maand, $dag, $jaar );
		}
		return $tabelinhoud;
	}

	/**
	 * Callback from Ajax request
	 *
	 * @param \WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response Ajax response.
	 */
	public static function callback_show( \WP_REST_Request $request ) {
		$oven_id = intval( $request->get_param( 'oven_id' ) );
		$periode = mktime( 0, 0, 0, intval( $request->get_param( 'maand' ) ), 1, intval( $request->get_param( 'jaar' ) ) );
		$maand   = intval( date( 'n', $periode ) );
		$jaar    = intval( date( 'Y', $periode ) );
		return new \WP_REST_Response(
			[
				'html'    => self::toon_reserveringen( $oven_id, $maand, $jaar ),
				'oven_id' => $oven_id,
				'maand'   => $maand,
				'jaar'    => $jaar,
				'periode' => strftime( '%B-%Y', $periode ),
			]
		);
	}

	/**
	 *
	 * Callback from Ajax request
	 *
	 * @param \WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response Ajax response.
	 */
	public static function callback_muteer( \WP_REST_Request $request ) {
		$input       = $request->get_param( 'reservering' );
		$oven_id     = $request->get_param( 'oven_id' );
		$jaar        = intval( $input['jaar'] );
		$maand       = intval( $input['maand'] );
		$dag         = intval( $input['dag'] );
		$reservering = new \Kleistad\Reservering( $oven_id, mktime( 23, 59, 0, $maand, $dag, $jaar ) );

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
		return new \WP_REST_Response(
			[
				'html'    => self::toon_reserveringen( $oven_id, $maand, $jaar ),
				'oven_id' => $oven_id,
				'maand'   => $maand,
				'jaar'    => $jaar,
				'periode' => strftime( '%B-%Y', mktime( 0, 0, 0, $maand, 1, $jaar ) ),
			]
		);
	}

}
