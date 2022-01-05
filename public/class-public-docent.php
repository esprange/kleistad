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
	 * Prepareer de tabel
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
					'actie' => [
						'required' => true,
						'type'     => 'string',
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in() && current_user_can( DOCENT );
				},
			]
		);
		register_rest_route(
			KLEISTAD_API,
			'/docent_overzicht',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_show' ],
				'args'                => [
					'datum' => [
						'required' => true,
						'type'     => 'string',
					],
					'actie' => [
						'required' => true,
						'type'     => 'string',
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in() && current_user_can( BESTUUR );
				},
			]
		);
	}

	/**
	 * Hulpfunctie om volledig overzicht van docent te maken
	 *
	 * @param int       $maandag   De datum waarin de week start.
	 * @param Docent    $docent    Het docent object.
	 * @param Cursussen $cursussen De cursussen verzameling.
	 * @param Workshops $workshops De workshops verzameling.
	 *
	 * @return array Het overzicht.
	 */
	private static function docent_beschikbaarheid( int $maandag, Docent $docent, Cursussen $cursussen, Workshops $workshops ) : array {
		$reserveringen = [];
		$maandag_2     = $maandag + 7 * DAY_IN_SECONDS;
		foreach ( $cursussen as $cursus ) {
			if ( intval( $cursus->docent ) === $docent->ID && ! $cursus->vervallen ) {
				foreach ( $cursus->lesdatums as $lesdatum ) {
					if ( $lesdatum >= $maandag && $lesdatum < $maandag_2 ) {
						$reserveringen[ $lesdatum ][ bepaal_dagdeel( $cursus->start_tijd, $cursus->eind_tijd ) ] = Docent::GERESERVEERD;
					}
				}
			}
		}
		foreach ( $workshops as $workshop ) {
			if ( intval( $workshop->docent ) === $docent->ID && ! $workshop->vervallen ) {
				if ( $workshop->datum >= $maandag && $workshop->datum < $maandag_2 ) {
					$reserveringen[ $workshop->datum ][ bepaal_dagdeel( $workshop->start_tijd, $workshop->eind_tijd ) ] = $workshop->definitief ? Docent::GERESERVEERD : Docent::OPTIE;
				}
			}
		}
		foreach ( DAGDEEL as $dagdeel ) {
			for ( $dagnr = 0; $dagnr < 7; $dagnr ++ ) {
				$datum = $maandag + $dagnr * DAY_IN_SECONDS;
				if ( ! isset( $reserveringen[ $datum ][ $dagdeel ] ) ) {
					$reserveringen[ $datum ][ $dagdeel ] = $docent->beschikbaarheid( $datum, $dagdeel );
				}
			}
		}
		return $reserveringen;
	}

	/**
	 * Maak de header van de tabel
	 *
	 * @param int $maandag De datum waarin de week start.
	 *
	 * @return string
	 */
	private static function header( int $maandag ) : string {
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
		return $html;
	}

	/**
	 * Toon de week planning
	 *
	 * @param int $maandag De datum waarin de week start.
	 *
	 * @return string
	 */
	private static function show_planning( int $maandag ) : string {
		$docent_id     = get_current_user_id();
		$vandaag       = strtotime( 'today' );
		$reserveringen = self::docent_beschikbaarheid( $maandag, new Docent( $docent_id ), new Cursussen( $vandaag ), new Workshops( $vandaag ) );
		$html          = self::header( $maandag );
		foreach ( DAGDEEL as $dagdeel ) {
			$html .= <<<EOT
	<tr>
		<td>$dagdeel</td>
EOT;
			for ( $dagnr = 0; $dagnr < 7; $dagnr++ ) {
				$datum       = $maandag + $dagnr * DAY_IN_SECONDS;
				$reservering = $reserveringen[ $datum ][ $dagdeel ] ?? Docent::NIET_BESCHIKBAAR;
				switch ( $reservering ) {
					case Docent::NIET_BESCHIKBAAR:
						$html .= <<<EOT
		<td><input type="checkbox" class="planning" data-datum="$datum" data-dagdeel="$dagdeel" ></td>
EOT;
						break;
					case Docent::BESCHIKBAAR:
						$html .= <<<EOT
		<td><input type="checkbox" class="planning" data-datum="$datum" data-dagdeel="$dagdeel" checked="checked" ></td>
EOT;
						break;
					case Docent::OPTIE:
						$html .= <<<EOT
		<td><span class="kleistad-inzet kleistad-inzet-optie" style="width:21px">O</span></td>
EOT;
						break;
					case Docent::GERESERVEERD:
						$html .= <<<EOT
		<td><span class="kleistad-inzet kleistad-inzet-definitief" style="width:21px">R</span></td>
EOT;
				}
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
	 * Toon het week overzicht van de docenten beschikbaarheid.
	 *
	 * @param int $maandag De datum waarin de week start.
	 *
	 * @return string
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private static function show_overzicht( int $maandag ) : string {
		$vandaag       = strtotime( 'today' );
		$reserveringen = [];
		$cursussen     = new Cursussen( $vandaag );
		$workshops     = new Workshops( $vandaag );
		$docenten      = new Docenten();
		foreach ( $docenten as $docent ) {
			$reserveringen[ $docent->ID ] = self::docent_beschikbaarheid( $maandag, $docent, $cursussen, $workshops );
		}
		$html = self::header( $maandag );
		foreach ( DAGDEEL as $dagdeel ) {
			$html .= <<<EOT
	<tr>
		<td>$dagdeel</td>
EOT;
			for ( $dagnr = 0; $dagnr < 7; $dagnr ++ ) {
				$datum = $maandag + $dagnr * DAY_IN_SECONDS;
				$html .= <<<EOT
		<td>
EOT;
				foreach ( $docenten as $docent ) {
					$reservering = $reserveringen[ $docent->ID ][ $datum ][ $dagdeel ] ?? Docent::NIET_BESCHIKBAAR;
					switch ( $reservering ) {
						case Docent::NIET_BESCHIKBAAR:
							break;
						case Docent::BESCHIKBAAR:
							$html .= <<<EOT
			$docent->display_name<br/>
EOT;
							break;
						case Docent::OPTIE:
							$html .= <<<EOT
			<span class="kleistad-inzet kleistad-inzet-optie">$docent->display_name</span><br/>
EOT;
							break;
						case Docent::GERESERVEERD:
							$html .= <<<EOT
			<span class="kleistad-inzet kleistad-inzet-definitief">$docent->display_name</span><br/>
EOT;
					}
				}
				$html .= <<<EOT
		</td>
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
		$actie     = $request->get_param( 'actie' );
		if ( is_string( $datum_str ) && is_string( $actie ) ) {
			$maandag = strtotime( 'Monday this week', strtotime( $datum_str ) );
			$show    = "show_$actie";
			return new WP_REST_Response(
				[
					'content' => self::$show( $maandag ),
					'datum'   => date( 'Y-m-d', $maandag ),
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
					'planning' => self::show_planning( $maandag ),
					'datum'    => date( 'Y-m-d', $maandag ),
				]
			);
		}
		return new WP_Error( 'param', 'Onjuiste data ontvangen' );
	}
}
