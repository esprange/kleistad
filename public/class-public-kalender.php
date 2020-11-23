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
			Public_Main::api(),
			'/kalender',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_kalender' ],
				'args'                => [
					'start',
					'eind',
				],
				'permission_callback' => function() {
						return true;
				},
			]
		);
	}

	/**
	 * Ajax callback voor workshop functie.
	 *
	 * @param \WP_REST_Request $request De parameters van de Ajax call.
	 * @return \WP_REST_Response
	 */
	public static function callback_kalender( \WP_REST_Request $request ) {
		$events    = Event::query(
			[
				'timeMin' => $request->get_param( 'start' ),
				'timeMax' => $request->get_param( 'eind' ),
			]
		);
		$fc_events = [];
		foreach ( $events as $event ) {
			$class = $event->properties['class'] ?? '';
			if ( strpos( $class, 'Workshop' ) ) {
				$workshop = new Workshop( $event->properties['id'] );
				if ( ! $workshop->vervallen ) {
					$fc_events[] = [
						'id'              => $event->id,
						'title'           => "$workshop->naam ($workshop->code)",
						'start'           => $event->start->format( \DateTime::ATOM ),
						'end'             => $event->eind->format( \DateTime::ATOM ),
						'backgroundColor' => $workshop->betaald ? 'green' : ( $workshop->definitief ? 'springgreen' : 'orange' ),
						'textColor'       => $workshop->betaald ? 'white' : ( $workshop->definitief ? 'black' : 'black' ),
						'extendedProps'   => [
							'naam'       => $workshop->naam,
							'aantal'     => $workshop->aantal,
							'docent'     => $workshop->docent ?: 'n.b.]',
							'technieken' => implode( ', ', $workshop->technieken ),
						],
					];
				}
			} elseif ( strpos( $class, 'Cursus' ) ) {
				$cursus = new Cursus( $event->properties['id'] );
				if ( ! $cursus->vervallen ) {
					$fc_events[] = [
						'id'              => $event->id,
						'title'           => "$cursus->naam ($cursus->code)",
						'start'           => $event->start->format( \DateTime::ATOM ),
						'end'             => $event->eind->format( \DateTime::ATOM ),
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
			} else {
				$fc_events[] = [
					'id'              => $event->id,
					'title'           => $event->titel ?: '',
					'start'           => $event->start->format( \DateTime::ATOM ),
					'end'             => $event->eind->format( \DateTime::ATOM ),
					'backgroundColor' => 'violet',
					'textColor'       => 'black',
				];
			}
		}
		return new \WP_REST_Response(
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
	protected function prepare( &$data ) {
		return true;
	}

}
