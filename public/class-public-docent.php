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
		$reserveringen = [];
		foreach ( $cursussen as $cursus ) {
			if ( intval( $cursus->docent ) === $docent->ID && ! $cursus->vervallen ) {
				foreach ( $cursus->lesdatums as $lesdatum ) {
					if ( $lesdatum >= $maandag && $lesdatum < ( $maandag + WEEK_IN_SECONDS ) ) {
						$reserveringen[ $lesdatum ][ bepaal_dagdeel( $cursus->start_tijd, $cursus->eind_tijd ) ] = Docent::GERESERVEERD;
					}
				}
			}
		}
		foreach ( $workshops as $workshop ) {
			if ( intval( $workshop->docent ) === $docent->ID && ! $workshop->vervallen ) {
				if ( $workshop->datum >= $maandag && $workshop->datum < ( $maandag + WEEK_IN_SECONDS ) ) {
					$reserveringen[ $workshop->datum ][ bepaal_dagdeel( $workshop->start_tijd, $workshop->eind_tijd ) ] = $workshop->definitief ? Docent::GERESERVEERD : Docent::OPTIE;
				}
			}
		}
		foreach ( DAGDEEL as $dagdeel ) {
			for ( $datum = $maandag;  $datum < $maandag + WEEK_IN_SECONDS; $datum += DAY_IN_SECONDS ) {
				if ( ! isset( $reserveringen[ $datum ][ $dagdeel ] ) ) {
					$reserveringen[ $datum ][ $dagdeel ] = $docent->beschikbaarheid( $datum, $dagdeel );
				}
			}
		}
		return $reserveringen;
	}

	/**
	 * Maak de tabel
	 *
	 * @param int      $maandag De datum waarin de week start.
	 * @param callable $functie De functie die de inhoud van de cellen invult.
	 * @param array    $args De extra argumenten voor de functie.
	 *
	 * @return string
	 */
	private static function tabel( int $maandag, callable $functie, array $args ) : string {
		$html = <<<EOT
<thead>
	<tr>
		<th></th>
EOT;
		for ( $datum = $maandag; $datum < $maandag + WEEK_IN_SECONDS; $datum += DAY_IN_SECONDS ) {
			$weekdag = strftime( '%A<br/>%d-%m-%Y', $datum );
			$html   .= <<<EOT
		<th>$weekdag</th>
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
			for ( $datum = $maandag; $datum < $maandag + WEEK_IN_SECONDS; $datum += DAY_IN_SECONDS ) {
				$html .= <<<EOT
		<td>
EOT;
				$html .= call_user_func( $functie, $datum, $dagdeel, $args );
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
			[
				__CLASS__,
				'show_planning_cell',
			],
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
		$html        = '';
		$reservering = $args['reserveringen'][ $datum ][ $dagdeel ] ?? Docent::NIET_BESCHIKBAAR;
		switch ( $reservering ) {
			case Docent::NIET_BESCHIKBAAR:
				$html .= <<<EOT
<input type="checkbox" class="planning" data-datum="$datum" data-dagdeel="$dagdeel" >
EOT;
				break;
			case Docent::BESCHIKBAAR:
				$html .= <<<EOT
<input type="checkbox" class="planning" data-datum="$datum" data-dagdeel="$dagdeel" checked="checked" >
EOT;
				break;
			case Docent::OPTIE:
				$html .= <<<EOT
<span class="kleistad-inzet kleistad-inzet-optie" style="width:21px">O</span>
EOT;
				break;
			case Docent::GERESERVEERD:
				$html .= <<<EOT
<span class="kleistad-inzet kleistad-inzet-definitief" style="width:21px">R</span>
EOT;
				break;
			case Docent::STANDAARD:
				$html .= <<<EOT
<input type="checkbox" class="planning" style="background-color: mediumpurple" data-datum="$datum" data-dagdeel="$dagdeel" checked="checked" >
EOT;
				break;
		}
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

		return self::tabel(
			$maandag,
			[
				__CLASS__,
				'show_overzicht_cell',
			],
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
		$html = '';
		foreach ( $args['docenten'] as $docent ) {
			$reservering = $args['reserveringen'][ $docent->ID ][ $datum ][ $dagdeel ] ?? Docent::NIET_BESCHIKBAAR;
			switch ( $reservering ) {
				case Docent::NIET_BESCHIKBAAR:
					break;
				case Docent::BESCHIKBAAR:
				case Docent::STANDAARD:
					$html .= <<<EOT
		<span class="kleistad-inzet" >$docent->display_name</span><br/>
EOT;
					break;
				case Docent::OPTIE:
					$html .= <<<EOT
		<span class="kleistad-inzet kleistad-inzet-optie" >$docent->display_name</span><br/>
EOT;
					break;
				case Docent::GERESERVEERD:
					$html .= <<<EOT
		<span class="kleistad-inzet kleistad-inzet-definitief" >$docent->display_name</span><br/>
EOT;
			}
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

	/**
	 * Omdat het formulier via ajax calls wordt afgehandeld is er geen processing nodig.
	 *
	 * @return array
	 */
	public function process(): array {
		return [];
	}
}
