<?php
/**
 * Shortcode workshop.
 *
 * @link       https://www.kleistad.nl
 * @since      5.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_REST_Request;
use WP_REST_Response;
use DateTime;

/**
 * De kleistad kalender class.
 */
class Public_Kalender extends Shortcode {

	/**
	 * Register rest URI's.
	 *
	 * @since 5.0.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			KLEISTAD_API,
			'/kalender',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_kalender' ],
				'args'                => [
					'start' => [
						'required' => true,
					],
					'eind'  => [
						'required' => true,
					],
				],
				'permission_callback' => function() {
						return true;
				},
			]
		);
	}

	/**
	 * Verwerk een workshop event
	 *
	 * @param Event $event Het event.
	 * @return array
	 */
	private static function workshop_event( Event $event ) : array {
		$workshop = new Workshop( $event->properties['id'] );
		if ( ! $workshop->vervallen ) {
			$betaald = $workshop->is_betaald();
			return [
				'id'              => $event->id,
				'title'           => "$workshop->naam ($workshop->code)",
				'start'           => $event->start->format( DateTime::ATOM ),
				'end'             => $event->eind->format( DateTime::ATOM ),
				'backgroundColor' => $betaald ? 'green' : ( $workshop->definitief ? 'springgreen' : 'orange' ),
				'textColor'       => $betaald ? 'white' : 'black',
				'extendedProps'   => [
					'naam'       => $workshop->naam,
					'aantal'     => $workshop->aantal,
					'docent'     => $workshop->docent ?: 'n.b.]',
					'technieken' => implode( ', ', $workshop->technieken ),
				],
			];
		}
		return [];
	}

	/**
	 * Verwerk een cursus event
	 *
	 * @param Event $event Het event.
	 * @return array
	 */
	private static function cursus_event( Event $event ) : array {
		$cursus = new Cursus( $event->properties['id'] );
		if ( ! $cursus->vervallen ) {
			return [
				'id'              => $event->id,
				'title'           => "$cursus->naam ($cursus->code)",
				'start'           => $event->start->format( DateTime::ATOM ),
				'end'             => $event->eind->format( DateTime::ATOM ),
				'backgroundColor' => $cursus->tonen || $cursus->start_datum < strtotime( 'today' ) ? 'slateblue' : 'lightblue',
				'textColor'       => $cursus->tonen || $cursus->start_datum < strtotime( 'today' ) ? 'white' : 'black',
				'extendedProps'   => [
					'naam'       => 'cursus',
					'aantal'     => $cursus->maximum - $cursus->ruimte(),
					'docent'     => $cursus->docent_naam() ?: 'n.b.',
					'technieken' => implode( ', ', $cursus->technieken ),
				],
			];
		}
		return [];
	}

	/**
	 * Verwerk een overig event
	 *
	 * @param Event $event Het event.
	 * @return array
	 */
	private static function overig_event( Event $event ) : array {
		return [
			'id'              => $event->id,
			'title'           => $event->titel ?: '',
			'start'           => $event->start->format( DateTime::ATOM ),
			'end'             => $event->eind->format( DateTime::ATOM ),
			'backgroundColor' => 'violet',
			'textColor'       => 'black',
		];
	}

	/**
	 * Ajax callback voor workshop functie.
	 *
	 * @param WP_REST_Request $request De parameters van de Ajax call.
	 * @return WP_REST_Response
	 */
	public static function callback_kalender( WP_REST_Request $request ) : WP_REST_Response {
		$events    = new Events(
			[
				'timeMin' => $request->get_param( 'start' ),
				'timeMax' => $request->get_param( 'eind' ),
			]
		);
		$fc_events = [];
		foreach ( $events as $event ) {
			$class = $event->properties['class'] ?? '';
			if ( strpos( $class, 'Workshop' ) ) {
				$fc_event = self::workshop_event( $event );
				if ( ! empty( $fc_event ) ) {
					$fc_events[] = $fc_event;
				}
			}
			if ( strpos( $class, 'Cursus' ) ) {
				$fc_event = self::cursus_event( $event );
				if ( ! empty( $fc_event ) ) {
					$fc_events[] = $fc_event;
				}
			}
			if ( empty( $class ) ) {
				$fc_events[] = self::overig_event( $event );
			}
		}
		return new WP_REST_Response(
			[
				'events' => $fc_events,
			]
		);
	}

	/**
	 * Prepareer 'kalender' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   5.0.0
	 */
	protected function prepare( array &$data ) {
		return isset( $data ); // Dummy statement.
	}

}
