<?php
/**
 * De docent functies.
 *
 * @link       https://www.kleistad.nl
 * @since      7.0.0
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
class Public_Docent extends Shortcode {

	/**
	 *
	 * Prepareer 'planning' form
	 *
	 * @return string
	 */
	protected function prepare() : string {
		return $this->content();
	}

	/**
	 * Register rest URI's.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			KLEISTAD_API,
			'/docent_planning',
			[
				'methods'             => 'POST, PUT',
				'callback'            => [ __CLASS__, 'callback_muteer' ],
				'args'                => [
					'planning' => [
						'required' => true,
						'type'     => 'array',
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in() && current_user_can( DOCENT );
				},
			]
		);
		register_rest_route(
			KLEISTAD_API,
			'/docent_planning',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_show' ],
				'args'                => [
					'datum' => [
						'required' => true,
						'type'     => 'string',
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in() && current_user_can( DOCENT );
				},
			]
		);
	}

	/**
	 * Toon de week planning
	 *
	 * @param int $maandag De datum waarin de week start.
	 *
	 * @return string
	 */
	private static function show( int $maandag ) : string {
		$docent_id     = get_current_user_id();
		$docent        = new Docent( $docent_id );
		$vandaag       = strtotime( 'today' );
		$reserveringen = [];
		foreach ( new Cursussen( $vandaag ) as $cursus ) {
			if ( intval( $cursus->docent ) === $docent_id && ! $cursus->vervallen ) {
				foreach ( $cursus->lesdatums as $lesdatum ) {
					$reserveringen[ $lesdatum ][ bepaal_dagdeel( $cursus->start_tijd, $cursus->eind_tijd ) ] = Docent::GERESERVEERD;
				}
			}
		}
		foreach ( new Workshops( $vandaag ) as $workshop ) {
			if ( intval( $workshop->docent ) === $docent_id && ! $workshop->vervallen ) {
				$reserveringen[ $workshop->datum ][ bepaal_dagdeel( $workshop->start_tijd, $workshop->eind_tijd ) ] = $workshop->definitief ? Docent::GERESERVEERD : Docent::OPTIE;
			}
		}
		$html      = <<<EOT
<thead>
	<tr>
		<th></th>
EOT;
		$weekdagen = [ 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag' ];
		foreach ( $weekdagen as $dagnr => $weekdag ) {
			$datum_str = date( 'd-m-Y', $maandag + $dagnr * DAY_IN_SECONDS );
			$html     .= <<<EOT
		<th>$weekdag<br/>$datum_str</th>
EOT;
		}
		$html .= <<<EOT
	</tr>
</thead>
<tbody>
EOT;
		foreach ( DAGDEEL as $dagdeel ) {
			$html .= <<<EOT
	<tr>
		<td>$dagdeel</td>
EOT;
			for ( $dagnr = 0; $dagnr < 7; $dagnr++ ) {
				$datum           = $maandag + $dagnr * DAY_IN_SECONDS;
				$beschikbaarheid = $docent->beschikbaarheid( $datum, $dagdeel );
				if ( isset( $reserveringen[ $datum ][ $dagdeel ] ) ) {
					$indicator = Docent::OPTIE === $reserveringen[ $datum ][ $dagdeel ] ? [ 'kleistad-inzet-optie', 'O' ] : [ 'kleistad-inzet-definitief', 'R' ];
					$html     .= <<<EOT
		<td><span class="$indicator[0]">$indicator[1]</span></td>
EOT;
					continue;
				}
				$checked = checked( $beschikbaarheid, Docent::BESCHIKBAAR, false );
				$html   .= <<<EOT
		<td>
			<input type="checkbox" class="planning" $checked data-datum="$datum" data-dagdeel="$dagdeel" ></td>
EOT;
			}
			$html .= <<<EOT
	</tr>
EOT;
		}
		$html .= <<<EOT
</tbody>
EOT;
		return $html;
	}

	/**
	 * Update de beschikbaarheid
	 *
	 * @param array $planning De planning.
	 */
	private static function wijzig( array $planning ) {
		$docent = new Docent( get_current_user_id() );
		$docent->beschikbaarlijst( $planning );
	}

	/**
	 * Update de standaard beschikbaarheid
	 *
	 * @param array $planning De planning.
	 */
	private static function default( array $planning ) {
		$docent = new Docent( get_current_user_id() );
		foreach ( $planning as $key => $item ) {
			$planning[ $key ]['datum'] = intval( date( 'N', $item['datum'] ) ) - 1;
		}
		$docent->beschikbaarlijst( $planning );
	}

	/**
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response|WP_Error Ajax response.
	 */
	public static function callback_show( WP_REST_Request $request ) {
		$datum_str = $request->get_param( 'datum' );
		if ( is_string( $datum_str ) ) {
			$maandag = strtotime( 'Monday this week', strtotime( $datum_str ) );
			return new WP_REST_Response(
				[
					'planning' => self::show( $maandag ),
					'datum'    => date( 'Y-m-d', $maandag ),
				]
			);
		}
		return new WP_Error( 'param', 'Onjuiste data ontvangen' );
	}

	/**
	 *
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 *
	 * @return WP_REST_Response|WP_Error Ajax response.
	 */
	public static function callback_muteer( WP_REST_Request $request ) {
		$datum_str = $request->get_param( 'datum' );
		$planning  = $request->get_param( 'planning' );
		if ( is_string( $datum_str ) && is_array( $planning ) ) {
			$maandag = strtotime( 'Monday this week', strtotime( $datum_str ) );
			$method  = $request->get_method();
			if ( 'POST' === $method ) {
				self::default( $planning );
			}
			if ( 'PUT' === $method ) {
				self::wijzig( $planning );
			}
			return new WP_REST_Response(
				[
					'planning' => self::show( $maandag ),
					'datum'    => date( 'Y-m-d', $maandag ),
				]
			);
		}
		return new WP_Error( 'param', 'Onjuiste data ontvangen' );
	}
}
