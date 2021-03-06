<?php
/**
 * De werkplek reservering.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
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
class Public_Werkplek extends Shortcode {

	/**
	 * Kijk voor 3 maanden vooraf wat de mogelijke data zijn voor werkplekken.
	 *
	 * @return array De mogelijke datums.
	 */
	private function geef_mogelijke_datums() : array {
		$werkplekconfigs = new WerkplekConfigs();
		if ( 0 === count( $werkplekconfigs ) ) {
			return [];
		}
		$datums         = [];
		$feestdagen     = new Feestdagen();
		$weken          = opties()['weken_werkplek'];
		$vandaag        = strtotime( 'today' );
		$driemaand      = strtotime( "+$weken weeks", $vandaag );
		$werkplekconfig = $werkplekconfigs->find( $vandaag ) ?: new WerkplekConfig();
		for ( $dagteller = $vandaag; $dagteller < $driemaand; $dagteller += DAY_IN_SECONDS ) {
			if ( $feestdagen->is_feestdag( $dagteller ) ) {
				continue;
			}
			$werkplekken = 0;
			if ( $dagteller > $werkplekconfig->eind_datum && 0 !== $werkplekconfig->eind_datum ) {
				$werkplekconfigs->next();
				$werkplekconfig = $werkplekconfigs->current();
			}
			foreach ( $werkplekconfig->config[ strftime( '%A', $dagteller ) ] as $dagdeel ) {
				$werkplekken += array_sum( $dagdeel );
			}
			if ( $werkplekken ) {
				$datums[] = date( 'd-m-Y', $dagteller );
			}
		}
		return $datums;
	}

	/**
	 * Voor de ad hoc selectie van werkplaatsbeheerders, bepaal wie die taak mogen uitvoeren.
	 *
	 * @return array
	 */
	private function geef_meesters() : array {
		return get_users(
			[
				'fields'   => [ 'display_name', 'ID' ],
				'orderby'  => 'display_name',
				'role__in' => [ LID, DOCENT, BESTUUR ],
			]
		);
	}

	/**
	 * Voor het selecteren van andere gebruikers, bepaal wie er daarvoor geselecteerd staan.
	 *
	 * @return array
	 */
	private function geef_cursisten() : array {
		$cursisten = [];
		foreach ( new Cursisten() as $cursist ) {
			if ( user_can( $cursist->ID, LID ) || user_can( $cursist->ID, BESTUUR ) || user_can( $cursist->ID, DOCENT ) ) {
				continue;
			}
			if ( $cursist->is_actief() ) {
				$cursisten[] = [
					'id'   => $cursist->ID,
					'naam' => $cursist->display_name,
				];
			}
		}
		return $cursisten;
	}


	/**
	 *
	 * Prepareer 'reservering' form
	 *
	 * @param array $data data to be prepared.
	 * @return WP_ERROR|bool
	 *
	 * @since   6.11.0
	 */
	protected function prepare( array &$data ) {
		$data['datums']    = $this->geef_mogelijke_datums();
		$data['meesters']  = $this->geef_meesters();
		$data['cursisten'] = $this->geef_cursisten();
		if ( 0 === count( $data['datums'] ) ) {
			return new WP_Error( 'config', 'Er zijn geen datums beschikbaar' );
		}
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
			'/werkplek',
			[
				'methods'             => 'POST,DELETE',
				'callback'            => [ __CLASS__, 'callback_muteer' ],
				'args'                => [
					'id'         => [
						'required' => true,
						'type'     => 'int',
					],
					'datum'      => [
						'required' => true,
						'type'     => 'string',
					],
					'dagdeel'    => [
						'required' => true,
						'type'     => 'string',
					],
					'activiteit' => [
						'required' => true,
						'type'     => 'string',
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			]
		);
		register_rest_route(
			KLEISTAD_API,
			'/meester',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_meester' ],
				'args'                => [
					'id'      => [
						'required' => true,
						'type'     => 'int',
					],
					'datum'   => [
						'required' => true,
						'type'     => 'string',
					],
					'dagdeel' => [
						'required' => true,
						'type'     => 'string',
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			]
		);
		register_rest_route(
			KLEISTAD_API,
			'/werkplek',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_show' ],
				'args'                => [
					'id'    => [
						'required' => true,
						'type'     => 'int',
					],
					'datum' => [
						'required' => true,
						'type'     => 'string',
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			]
		);
	}

	/**
	 * Toon de meesters sectie
	 *
	 * @param WerkplekGebruik $werkplekgebruik Het werkplekgebruik.
	 * @return string De html tekst.
	 */
	private static function toon_meesters( WerkplekGebruik $werkplekgebruik ) : string {
		$html = <<<EOT
<div class="kleistad-row kleistad-meesters" >
	<div class="kleistad-col-kwart" >
		<strong>beheerder</strong>
	</div>
EOT;
		foreach ( $werkplekgebruik->geef_meesters() as $dagdeel => $meester ) {
			$meester_naam = is_object( $meester ) ? $meester->display_name : '...';
			$meester_id   = is_object( $meester ) ? $meester->ID : 0;
			if ( current_user_can( BESTUUR ) ) {
				$html .= <<<EOT
	<div class="kleistad-col-kwart" >
		<button class="kleistad-button kleistad-meester" type="button" data-dagdeel="$dagdeel" value="$meester_id" name="meester" >$meester_naam</button>
	</div>
EOT;
				continue;
			}
			$html .= <<<EOT
	<div class="kleistad-col-kwart" style="white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">
		$meester_naam
	</div>
EOT;
		}
		$html .= <<<EOT
</div>
EOT;
		return $html;
	}

	/**
	 * Toon de werkplekken op een dag.
	 *
	 * @param int $gebruiker_id   De gebruiker waarvoor de reservering plaatsvindt.
	 * @param int $datum          De datum.
	 * @return string HTML content.
	 */
	private static function toon_werkplekken( int $gebruiker_id, int $datum ) : string {
		$werkplekgebruik = new WerkplekGebruik( $datum );
		$button          = [];
		$html            = self::toon_meesters( $werkplekgebruik );
		foreach ( WerkplekConfig::ACTIVITEIT as $activiteit ) {
			$kleur = WerkplekConfig::ACTIEKLEUR[ $activiteit ];
			$html .= <<<EOT
<div class="kleistad-row" style="background: $kleur">
	<div class="kleistad-col-kwart">
		<strong>$activiteit</strong>
	</div>
EOT;
			foreach ( $werkplekgebruik->config() as $dagdeel => $werkplekken ) {
				$button[ $dagdeel ] = $button[ $dagdeel ] ?? [];
				$gebruikers         = $werkplekgebruik->geef( $dagdeel, $activiteit );
				$aanwezig           = $werkplekgebruik->is_aanwezig( $dagdeel, $gebruiker_id );
				$html              .= <<<EOT
	<div class="kleistad-col-kwart" >
		<span class="kleistad-werkplek-dagdeel">$dagdeel</span>
		<br/>
EOT;
				for ( $werkplek = 0; $werkplek < $werkplekken[ $activiteit ]; $werkplek++ ) {
					if ( isset( $gebruikers[ $werkplek ] ) ) {
						if ( intval( $gebruikers[ $werkplek ]->ID ) !== $gebruiker_id ) {
							$html .= <<<EOT
				<div class="kleistad-werkplek-bezet" >{$gebruikers[$werkplek]->display_name}</div>
EOT;
							continue;
						}
						$button[ $dagdeel ][ $activiteit ] = true;
						$html                             .= <<<EOT
				<button class="kleistad-button kleistad-werkplek kleistad-werkplek-gereserveerd" type="button" value="$gebruiker_id" data-dagdeel="$dagdeel" data-activiteit="$activiteit" >{$gebruikers[$werkplek]->display_name}</button>
EOT;
						continue;
					}
					if ( ! $aanwezig && ! ( $button[ $dagdeel ][ $activiteit ] ?? false ) ) {
						$button[ $dagdeel ][ $activiteit ] = true;
						$html                             .= <<<EOT
				<button class="kleistad-button kleistad-werkplek kleistad-werkplek-reserveerbaar" type="button" value="$gebruiker_id" data-dagdeel="$dagdeel" data-activiteit="$activiteit" >reserveren</button>
EOT;
						continue;
					}
					$html .= <<<EOT
				<div class="kleistad-werkplek-vrij" >&nbsp;</div>
EOT;
				}
				$html .= <<<EOT
	</div>
EOT;
			}
			$html .= <<<EOT
</div>
EOT;
		}
		return $html;
	}

	/**
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response|WP_Error Ajax response.
	 */
	public static function callback_show( WP_REST_Request $request ) {
		$datum_str    = $request->get_param( 'datum' );
		$gebruiker_id = intval( $request->get_param( 'id' ) );
		if ( is_null( $datum_str ) || 0 === $gebruiker_id ) {
			return new WP_Error( 'param', 'Onjuiste data ontvangen' );
		}
		$datum = strtotime( $datum_str );
		return new WP_REST_Response(
			[
				'content' => self::toon_werkplekken( $gebruiker_id, $datum ),
				'datum'   => strftime( '%A %e %B', $datum ),
			]
		);
	}

	/**
	 *
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response|WP_Error Ajax response.
	 */
	public static function callback_muteer( WP_REST_Request $request ) {
		$datum_str    = $request->get_param( 'datum' );
		$gebruiker_id = intval( $request->get_param( 'id' ) );
		$dagdeel      = $request->get_param( 'dagdeel' );
		$activiteit   = $request->get_param( 'activiteit' );
		if ( is_null( $dagdeel ) || is_null( $activiteit ) || 0 === $gebruiker_id || is_null( $datum_str ) ) {
			return new WP_Error( 'param', 'Onjuiste data ontvangen' );
		}
		$datum           = strtotime( $datum_str );
		$werkplekgebruik = new WerkplekGebruik( $datum );
		$gebruiker_ids   = array_map( 'intval', array_column( $werkplekgebruik->geef( $dagdeel, $activiteit ), 'ID' ) );
		if ( 'POST' === $request->get_method() ) {
			if ( ! in_array( $gebruiker_id, $gebruiker_ids, true ) ) {
				$gebruiker_ids[] = $gebruiker_id;
				$werkplekgebruik->wijzig( $dagdeel, $activiteit, $gebruiker_ids );
			}
		}
		if ( 'DELETE' === $request->get_method() ) {
			$key = array_search( $gebruiker_id, $gebruiker_ids, true );
			if ( false !== $key ) {
				unset( $gebruiker_ids[ $key ] );
				$werkplekgebruik->wijzig( $dagdeel, $activiteit, $gebruiker_ids );
			}
		}
		return new WP_REST_Response(
			[
				'content' => self::toon_werkplekken( $gebruiker_id, $datum ),
			]
		);
	}

	/**
	 *
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response|WP_Error Ajax response.
	 */
	public static function callback_meester( WP_REST_Request $request ) {
		$datum_str  = $request->get_param( 'datum' );
		$meester_id = intval( $request->get_param( 'id' ) );
		$dagdeel    = $request->get_param( 'dagdeel' );
		if ( is_null( $dagdeel ) || is_null( $meester_id ) || is_null( $datum_str ) ) {
			return new WP_Error( 'param', 'Onjuiste data ontvangen' );
		}
		$datum           = strtotime( $datum_str );
		$werkplekgebruik = new WerkplekGebruik( $datum );
		$werkplekgebruik->wijzig_meester( $dagdeel, $meester_id );
		$meesters = $werkplekgebruik->geef_meesters();
		return new WP_REST_Response(
			[
				'id'      => is_object( $meesters[ $dagdeel ] ) ? $meesters[ $dagdeel ]->ID : 0,
				'dagdeel' => $dagdeel,
				'naam'    => is_object( $meesters[ $dagdeel ] ) ? $meesters[ $dagdeel ]->display_name : '...',
			]
		);
	}

}
