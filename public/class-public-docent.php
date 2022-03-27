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
class Public_Docent extends ShortcodeForm {

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
		$reserveringen = self::docent_op_cursussen( $maandag, $docent, $cursussen ) + self::docent_op_workshops( $maandag, $docent, $workshops );
		foreach ( Docent::DOCENT_DAGDEEL as $dagdeel ) {
			for ( $datum = $maandag;  $datum < $maandag + WEEK_IN_SECONDS; $datum += DAY_IN_SECONDS ) {
				if ( ! isset( $reserveringen[ $datum ][ $dagdeel ] ) ) {
					$reserveringen[ $datum ][ $dagdeel ] = $docent->beschikbaarheid( $datum, $dagdeel );
				}
			}
		}
		return $reserveringen;
	}

	/**
	 * Hulpfunctie om te bepalen wanneer de docent al bezet is ivm een cursus
	 *
	 * @param int       $maandag   De datum waarin de week start.
	 * @param Docent    $docent    Het docent object.
	 * @param Cursussen $cursussen De cursussen verzameling.
	 *
	 * @return array De reserveringen
	 */
	private static function docent_op_cursussen( int $maandag, Docent $docent, Cursussen $cursussen ) : array {
		$reserveringen = [];
		foreach ( $cursussen as $cursus ) {
			if ( intval( $cursus->docent ) !== $docent->ID || $cursus->vervallen ) {
				continue;
			}
			foreach ( $cursus->lesdatums as $lesdatum ) {
				if ( $lesdatum >= $maandag && $lesdatum < ( $maandag + WEEK_IN_SECONDS ) ) {
					foreach ( bepaal_dagdelen( $cursus->start_tijd, $cursus->eind_tijd ) as $dagdeel ) {
						$reserveringen[ $lesdatum ][ $dagdeel ] = Docent::GERESERVEERD;
					}
				}
			}
		}
		return $reserveringen;
	}

	/**
	 * Hulpfunctie om te bepalen wanneer de docent al bezet is ivm een workshop
	 *
	 * @param int       $maandag   De datum waarin de week start.
	 * @param Docent    $docent    Het docent object.
	 * @param Workshops $workshops De workshops verzameling.
	 *
	 * @return array De reserveringen
	 */
	private static function docent_op_workshops( int $maandag, Docent $docent, Workshops $workshops ) : array {
		$reserveringen = [];
		foreach ( $workshops as $workshop ) {
			$docent_ids = array_map( 'intval', explode( ';', $workshop->docent ) );
			if ( in_array( $docent->ID, $docent_ids, true ) && ! $workshop->vervallen ) {
				if ( $workshop->datum >= $maandag && $workshop->datum < ( $maandag + WEEK_IN_SECONDS ) ) {
					foreach ( bepaal_dagdelen( $workshop->start_tijd, $workshop->eind_tijd ) as $dagdeel ) {
						$reserveringen[ $workshop->datum ][ $dagdeel ] = $workshop->definitief ? Docent::GERESERVEERD : Docent::OPTIE;
					}
				}
			}
		}
		return $reserveringen;
	}

	/**
	 * Maak de tabel
	 *
	 * @param int    $maandag De datum waarin de week start.
	 * @param string $functie De functie die de inhoud van de cellen invult.
	 * @param array  $args De extra argumenten voor de functie.
	 *
	 * @return string
	 */
	private static function tabel( int $maandag, string $functie, array $args ) : string {
		$html = <<<EOT
<thead>
	<tr>
		<th></th>
EOT;
		for ( $datum = $maandag; $datum < $maandag + WEEK_IN_SECONDS; $datum += DAY_IN_SECONDS ) {
			$weekdag = strftime( '%A<br/>%d-%m-%Y', $datum );
			$html   .= <<<EOT
		<th class="kleistad-cell_center">$weekdag</th>
EOT;
		}
		$html .= <<<EOT
	</tr>
</thead>
<tbody>
EOT;
		foreach ( Docent::DOCENT_DAGDEEL as $dagdeel ) {
			$html .= <<<EOT
	<tr>
		<td class="kleistad-cell_center">$dagdeel</td>
EOT;
			for ( $datum = $maandag; $datum < $maandag + WEEK_IN_SECONDS; $datum += DAY_IN_SECONDS ) {
				$html .= <<<EOT
		<td class="kleistad-cell_center">
EOT;
				$html .= match ( $functie ) {
					'overzicht' => self::show_overzicht_cell( $datum, $dagdeel, $args ),
					'planning'  => self::show_planning_cell( $datum, $dagdeel, $args ),
				};
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
	 * Toon de week planning
	 *
	 * @param int $maandag De datum waarin de week start.
	 *
	 * @return string
	 */
	private static function show_planning( int $maandag ) : string {
		$docent_id = get_current_user_id();
		$vandaag   = strtotime( 'today' );
		return self::tabel(
			$maandag,
			'planning',
			[
				'reserveringen' => self::docent_beschikbaarheid( $maandag, new Docent( $docent_id ), new Cursussen( $vandaag ), new Workshops( $vandaag ) ),
			]
		);
	}

	/**
	 * Toon de planning van een dag, dagdeel
	 *
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 * @param array  $args    De argumenten (eerste betreft reserveringen).
	 *
	 * @return string
	 */
	private static function show_planning_cell( int $datum, string $dagdeel, array $args ) : string {
		$reservering = $args['reserveringen'][ $datum ][ $dagdeel ] ?? Docent::NIET_BESCHIKBAAR;
		$formats     = [
			Docent::NIET_BESCHIKBAAR => '<input type="checkbox" name="planning" class="kleistad-checkbox" data-datum="%s" data-dagdeel="%s" >',
			Docent::BESCHIKBAAR      => '<input type="checkbox" name="planning" class="kleistad-checkbox" data-datum="%s" data-dagdeel="%s" checked="checked" >',
			Docent::STANDAARD        => '<input type="checkbox" name="planning" class="kleistad-checkbox" style="background-color: mediumpurple" data-datum="%s" data-dagdeel="%s" checked="checked" >',
			Docent::OPTIE            => '<span class="kleistad-inzet kleistad-inzet-optie" style="width:21px">O</span>',
			Docent::GERESERVEERD     => '<span class="kleistad-inzet kleistad-inzet-definitief" style="width:21px">R</span>',
		];
		return sprintf( $formats[ $reservering ], $datum, $dagdeel );
	}

	/**
	 * Toon het week overzicht van de docenten beschikbaarheid.
	 *
	 * @param int $maandag De datum waarin de week start.
	 *
	 * @return string
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
		return self::tabel(
			$maandag,
			'overzicht',
			[
				'reserveringen' => $reserveringen,
				'docenten'      => $docenten,
			]
		);
	}

	/**
	 * Toon de beschikbaarheid van een dag, dagdeel
	 *
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 * @param array  $args    De argumenten (eerste betreft reserveringen, tweede de docenten).
	 *
	 * @return string
	 */
	private static function show_overzicht_cell( int $datum, string $dagdeel, array $args ) : string {
		$html    = '';
		$formats = [
			Docent::NIET_BESCHIKBAAR => '',
			Docent::BESCHIKBAAR      => '<span class="kleistad-inzet" >%s</span><br/>',
			Docent::STANDAARD        => '<span class="kleistad-inzet" >%s</span><br/>',
			Docent::OPTIE            => '<span class="kleistad-inzet kleistad-inzet-optie" >%s</span><br/>',
			Docent::GERESERVEERD     => '<span class="kleistad-inzet kleistad-inzet-definitief" >%s</span><br/>',
		];
		foreach ( $args['docenten'] as $docent ) {
			$reservering = $args['reserveringen'][ $docent->ID ][ $datum ][ $dagdeel ] ?? Docent::NIET_BESCHIKBAAR;
			$html       .= sprintf( $formats[ $reservering ], $docent->display_name );
		}
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
	 * @return WP_Error|WP_REST_Response Ajax response.
	 */
	public static function callback_show( WP_REST_Request $request ): WP_Error|WP_REST_Response {
		$datum_str = $request->get_param( 'datum' );
		$actie     = $request->get_param( 'actie' );
		if ( is_string( $datum_str ) && is_string( $actie ) ) {
			$maandag = strtotime( 'Monday this week', strtotime( $datum_str ) );
			return new WP_REST_Response(
				[
					'content' => match ( $actie ) {
						'overzicht' => self::show_overzicht( $maandag ),
						'planning'  => self::show_planning( $maandag ), //phpcs:ignore
					},
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
	 * @return WP_Error|WP_REST_Response Ajax response.
	 */
	public static function callback_muteer( WP_REST_Request $request ): WP_Error|WP_REST_Response {
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

	/**
	 * Omdat het formulier via ajax calls wordt afgehandeld is er geen processing nodig.
	 *
	 * @return array
	 */
	public function process(): array {
		return [];
	}
}
