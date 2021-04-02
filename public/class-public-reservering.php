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

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * De reservering form.
 */
class Public_Reservering extends Shortcode {

	/**
	 *
	 * Prepareer 'reservering' form
	 *
	 * @param array $data data to be prepared.
	 * @return WP_ERROR|bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( array &$data ) {
		$atts = shortcode_atts(
			[ 'oven' => 'niet ingevuld' ],
			$this->atts,
			'kleistad_reservering'
		);
		if ( ! is_numeric( $atts['oven'] ) ) {
			return new WP_Error( 'fout', 'de shortcode bevat geen oven nummer tussen 1 en 999 !' );
		}
		$oven_id = $atts['oven'];
		$oven    = new Oven( $oven_id );
		if ( ! intval( $oven->id ) ) {
			return new WP_Error( 'fout', 'oven met id ' . $oven_id . ' is niet bekend in de database !' );
		}
		$data = [
			'stokers'  => get_users(
				[
					'fields'       => [ 'ID', 'display_name' ],
					'orderby'      => 'display_name',
					'role__in'     => [ LID, DOCENT, BESTUUR ],
					'role__not_in' => [ INTERN ],
				]
			),
			'oven'     => [
				'id'   => $oven->id,
				'naam' => $oven->naam,
			],
			'override' => current_user_can( OVERRIDE ),
		];
		return true;
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 4.5.3
	 */
	public static function register_rest_routes() {
		register_rest_route(
			KLEISTAD_API,
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
					return is_user_logged_in() && current_user_can( RESERVEER );
				},
			]
		);
		register_rest_route(
			KLEISTAD_API,
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
					return is_user_logged_in() && current_user_can( RESERVEER );
				},
			]
		);
	}

	/**
	 * Maak een regel op van de tabel
	 *
	 * @param Stook $stook De stook waarvoor de regel moet worden opgemaakt.
	 * @return string html opgemaakte tekstregel.
	 */
	private static function maak_stookregel( Stook $stook ) : string {
		$gebruiker_id = get_current_user_id();
		$stoker_id    = isset( $stook->stookdelen[0] ) ? $stook->stookdelen[0]->medestoker : $gebruiker_id;
		$stoker_naam  = get_userdata( $stoker_id )->display_name;
		$kleur        = Stook::ONDERHOUD === $stook->soort ? 'kleistad-reservering-onderhoud' :
			( $stoker_id === $gebruiker_id ? 'kleistad-reservering-zelf' : 'kleistad-reservering-ander' );
		$logica       = [
			Stook::ONGEBRUIKT    => [
				'wie'          => '',
				'temperatuur'  => '',
				'programma'    => '',
				'verdeling'    => [],
				'soortstook'   => '',
				'kleur'        => 'kleistad-reservering-ongebruikt',
				'select'       => false,
				'gebruiker_id' => 0,
			],
			Stook::RESERVEERBAAR => [
				'wie'          => '- beschikbaar -',
				'temperatuur'  => '',
				'programma'    => '',
				'verdeling'    => $stook->stookdelen,
				'soortstook'   => $stook->soort,
				'kleur'        => 'kleistad-reservering-reserveerbaar',
				'select'       => true,
				'gebruiker_id' => $gebruiker_id,
			],
			Stook::WIJZIGBAAR    => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $stook->temperatuur,
				'programma'    => $stook->programma,
				'verdeling'    => $stook->stookdelen,
				'soortstook'   => $stook->soort,
				'kleur'        => $kleur,
				'select'       => true,
				'gebruiker_id' => $stook->hoofdstoker,
			],
			Stook::ALLEENLEZEN   => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $stook->temperatuur,
				'programma'    => $stook->programma,
				'verdeling'    => $stook->stookdelen,
				'soortstook'   => $stook->soort,
				'kleur'        => $kleur,
				'select'       => true,
				'gebruiker_id' => $stook->hoofdstoker,
			],
			Stook::VERWIJDERBAAR => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $stook->temperatuur,
				'programma'    => $stook->programma,
				'verdeling'    => $stook->stookdelen,
				'soortstook'   => $stook->soort,
				'kleur'        => $kleur,
				'select'       => true,
				'gebruiker_id' => $stook->hoofdstoker,
			],
			Stook::DEFINITIEF    => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $stook->temperatuur,
				'programma'    => $stook->programma,
				'verdeling'    => $stook->stookdelen,
				'soortstook'   => $stook->soort,
				'kleur'        => 'kleistad-reservering-definitief',
				'select'       => true,
				'gebruiker_id' => $stook->hoofdstoker,
			],
		];
		$status       = $logica[ $stook->geef_statustekst() ];
		$html         = "<tr class=\"{$status['kleur']}\"";
		if ( $status['select'] ) {
			$json_selectie = wp_json_encode(
				[
					'dag'          => date( 'd', $stook->datum ),
					'maand'        => date( 'm', $stook->datum ),
					'jaar'         => date( 'Y', $stook->datum ),
					'soortstook'   => $status['soortstook'],
					'temperatuur'  => $status['temperatuur'],
					'programma'    => $status['programma'],
					'verdeling'    => $status['verdeling'],
					'status'       => $stook->geef_statustekst(),
					'kleur'        => $status['kleur'],
					'gebruiker_id' => $status['gebruiker_id'],
				]
			);
			if ( false === $json_selectie ) {
				$json_selectie = '{}';
			}
			$html .= "data-form='" . htmlspecialchars( $json_selectie, ENT_QUOTES, 'UTF-8' ) . "' ";
		}
		$html .= '><td>' . strftime( '%d %A', $stook->datum ) . "</td><td> {$status['wie']}</td><td>{$status['soortstook']}</td><td>{$status['temperatuur']}</td></tr>";
		return $html;
	}

	/**
	 * Toon de stook reserveringen voor bepaalde maand
	 *
	 * @param int $oven_id Het id van de oven.
	 * @param int $maand   De maand.
	 * @param int $jaar    Het jaar.
	 * @return string De Html code voor de body van de tabel.
	 */
	private static function toon_stoken( int $oven_id, int $maand, int $jaar ) : string {
		$vanaf  = mktime( 0, 0, 0, $maand, 1, $jaar );
		$tot    = mktime( 0, 0, 0, $maand + 1, 1, $jaar );
		$oven   = new Oven( $oven_id );
		$body   = '';
		$stoken = [];
		for ( $datum = $vanaf; $datum < $tot; $datum += DAY_IN_SECONDS ) {
			$dagnaam = strftime( '%A', $datum );
			if ( $oven->{$dagnaam} ) {
				$stoken[] = new Stook( $oven_id, $datum );
			}
		}
		foreach ( $stoken as $stook ) {
			$body .= self::maak_stookregel( $stook );
		}
		return $body;
	}

	/**
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response Ajax response.
	 */
	public static function callback_show( WP_REST_Request $request ) : WP_REST_Response {
		$oven_id = intval( $request->get_param( 'oven_id' ) );
		$maand   = intval( $request->get_param( 'maand' ) );
		$jaar    = intval( $request->get_param( 'jaar' ) );
		return new WP_REST_Response(
			[
				'content' => self::toon_stoken( $oven_id, $maand, $jaar ),
				'oven_id' => $oven_id,
				'maand'   => $maand,
				'jaar'    => $jaar,
				'periode' => strftime( '%B-%Y', mktime( 0, 0, 0, $maand, 1, $jaar ) ),
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
	public static function callback_muteer( WP_REST_Request $request ) : WP_REST_Response {
		$input   = $request->get_param( 'reservering' );
		$oven_id = $request->get_param( 'oven_id' ) ?: 0;
		$jaar    = intval( $input['jaar'] );
		$maand   = intval( $input['maand'] );
		$dag     = intval( $input['dag'] );
		$stook   = new Stook( $oven_id, mktime( 0, 0, 0, $maand, $dag, $jaar ) );
		$method  = $request->get_method();
		if ( 'DELETE' === $method ) {
			// het betreft een annulering, controleer of deze al niet verwijderd is.
			$stook->verwijder();
		}
		if ( ( 'POST' === $method && ! $stook->is_gereserveerd() ) ||
				( 'PUT' === $method && $stook->is_gereserveerd() )
			) {
			$stook->temperatuur = intval( $input['temperatuur'] );
			$stook->soort       = sanitize_text_field( $input['soortstook'] );
			$stook->programma   = intval( $input['programma'] );
			$stook->stookdelen  = [];
			foreach ( $input['verdeling'] as $verdeling ) {
				$stook->stookdelen[] = new Stookdeel( $verdeling['id'], $verdeling['perc'], $verdeling['prijs'] = 0 );
			}
			$stook->save();
		}
		return new WP_REST_Response(
			[
				'content' => self::toon_stoken( $oven_id, $maand, $jaar ),
				'oven_id' => $oven_id,
				'maand'   => $maand,
				'jaar'    => $jaar,
				'periode' => strftime( '%B-%Y', mktime( 0, 0, 0, $maand, 1, $jaar ) ),
			]
		);
	}
}
