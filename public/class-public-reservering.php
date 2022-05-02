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
use Exception;

/**
 * De reservering form.
 */
class Public_Reservering extends Shortcode {

	/**
	 *
	 * Prepareer 'reservering' form
	 *
	 * @since   4.0.87
	 *
	 * @return string
	 */
	protected function prepare() : string {
		if ( ! is_numeric( $this->data['oven'] ) ) {
			return $this->status( new WP_Error( 'fout', 'de shortcode bevat geen oven nummer tussen 1 en 999 !' ) );
		}
		$oven = new Oven( $this->data['oven'] );
		if ( ! $oven->id ) {
			return $this->status( new WP_Error( 'fout', 'oven met id ' . $this->data['oven'] . ' is niet bekend in de database !' ) );
		}
		$stooksoorten = [
			Stook::BISCUIT,
			Stook::GLAZUUR,
			Stook::OVERIG,
		];
		if ( current_user_can( BESTUUR ) ) {
			$stooksoorten[] = Stook::ONDERHOUD;
		}
		$this->data = [
			'stokers'      => get_users(
				[
					'fields'       => [ 'ID', 'display_name' ],
					'orderby'      => 'display_name',
					'role__in'     => [ LID, DOCENT, BESTUUR ],
					'role__not_in' => [ INTERN ],
				]
			),
			'oven'         => [
				'id'   => $oven->id,
				'naam' => $oven->naam,
			],
			'override'     => current_user_can( OVERRIDE ),
			'stooksoorten' => $stooksoorten,
		];
		return $this->content();
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
				'gebruiker_id' => $stook->hoofdstoker_id,
			],
			Stook::ALLEENLEZEN   => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $stook->temperatuur,
				'programma'    => $stook->programma,
				'verdeling'    => $stook->stookdelen,
				'soortstook'   => $stook->soort,
				'kleur'        => $kleur,
				'select'       => true,
				'gebruiker_id' => $stook->hoofdstoker_id,
			],
			Stook::VERWIJDERBAAR => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $stook->temperatuur,
				'programma'    => $stook->programma,
				'verdeling'    => $stook->stookdelen,
				'soortstook'   => $stook->soort,
				'kleur'        => $kleur,
				'select'       => true,
				'gebruiker_id' => $stook->hoofdstoker_id,
			],
			Stook::DEFINITIEF    => [
				'wie'          => $stoker_naam,
				'temperatuur'  => $stook->temperatuur,
				'programma'    => $stook->programma,
				'verdeling'    => $stook->stookdelen,
				'soortstook'   => $stook->soort,
				'kleur'        => 'kleistad-reservering-definitief',
				'select'       => true,
				'gebruiker_id' => $stook->hoofdstoker_id,
			],
		];
		$status       = $logica[ $stook->get_statustekst() ];
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
					'status'       => $stook->get_statustekst(),
					'kleur'        => $status['kleur'],
					'gebruiker_id' => $status['gebruiker_id'],
				]
			);
			if ( false === $json_selectie ) {
				$json_selectie = '{}';
			}
			$html .= "data-form='" . htmlspecialchars( $json_selectie, ENT_QUOTES ) . "' ";
		}
		$html .= '><td>' . wp_date( 'd l', $stook->datum ) . "</td><td> {$status['wie']}</td><td>{$status['soortstook']}</td><td>{$status['temperatuur']}</td></tr>";
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
			$dagnaam = wp_date( 'l', $datum );
			if ( $oven->{$dagnaam} ) {
				$stoken[] = new Stook( $oven, $datum );
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
				'periode' => wp_date( 'F-Y', mktime( 0, 0, 0, $maand, 1, $jaar ) ),
			]
		);
	}

	/**
	 *
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 *
	 * @return WP_REST_Response Ajax response.
	 * @throws Exception Exceptie van stook save.
	 */
	public static function callback_muteer( WP_REST_Request $request ) : WP_REST_Response {
		$input   = $request->get_param( 'reservering' );
		$oven_id = $request->get_param( 'oven_id' ) ?: 0;
		$jaar    = intval( $input['jaar'] );
		$maand   = intval( $input['maand'] );
		$dag     = intval( $input['dag'] );
		$datum   = mktime( 0, 0, 0, $maand, $dag, $jaar );
		$stook   = new Stook( new Oven( $oven_id ), $datum );
		$method  = $request->get_method();
		switch ( $method ) {
			case 'DELETE':
				if ( ! $stook->verwijder() ) {
					return new WP_REST_Response( [ 'status' => melding( 0, 'De gegevens konden niet worden verwijderd. Probeer het eventueel opnieuw' ) ] );
				}
				break;
			/**
			 * Voor zowel Post als Put kan afgesloten worden met de wijziging of toevoeging gegevens.
			 *
			 * @noinspection PhpMissingBreakStatementInspection
			 */
			case 'POST':
				$check = self::is_reservering_toegestaan( $stook );
				if ( ! empty( $check ) ) {
					return new WP_REST_Response( [ 'status' => melding( 0, $check ) ] );
				}
				// Geen break.
			case 'PUT':
				if ( ! $stook->wijzig( intval( $input['temperatuur'] ), sanitize_text_field( $input['soortstook'] ), intval( $input['programma'] ), $input['verdeling'] ) ) {
					return new WP_REST_Response( [ 'status' => melding( 0, 'De gegevens konden niet worden opgeslagen. Probeer het eventueel opnieuw' ) ] );
				}
		}
		return new WP_REST_Response(
			[
				'content' => self::toon_stoken( $oven_id, $maand, $jaar ),
				'oven_id' => $oven_id,
				'maand'   => $maand,
				'jaar'    => $jaar,
				'periode' => wp_date( 'F-Y', mktime( 0, 0, 0, $maand, 1, $jaar ) ),
			]
		);
	}

	/**
	 * Controleer of de gebruiker mag reserveren.
	 *
	 * @param Stook $stook De stook.
	 * @return string
	 */
	private static function is_reservering_toegestaan( Stook $stook ) : string {
		if ( ! current_user_can( BESTUUR ) ) {
			$stoker_id = get_current_user_id();
			$abonnee   = new Abonnee( $stoker_id );
			if ( $abonnee->abonnement->start_datum > $stook->datum ) {
				return 'Op deze datum is je abonnement nog niet gestart. Een reservering is dan nog niet mogelijk';
			}
			if ( $abonnee->abonnement->eind_datum && $abonnee->abonnement->eind_datum < $stook->datum ) {
				return 'Op deze datum is je abonnement al beëindigd. Een reservering is dan niet meer mogelijk.';
			}
			if ( $abonnee->abonnement->pauze_datum < $stook->datum && $abonnee->abonnement->herstart_datum > $stook->datum ) {
				return 'Op deze datum is je abonnement gepauzeeerd. Een reservering is dan niet mogelijk.';
			}
			if ( opties()['stook_max'] <= $abonnee->aantal_actieve_stook() ) {
				return 'Je kan niet meer dan ' . opties()['stook_max'] . ' openstaande reserveringen hebben.';
			}
		}
		if ( $stook->is_gereserveerd() ) {
			return 'Helaas is de oven al gereserveerd';
		}
		return '';
	}
}
