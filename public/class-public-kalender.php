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
		$events    = \Kleistad\Event::query(
			[
				'timeMin' => $request->get_param( 'start' ),
				'timeMax' => $request->get_param( 'eind' ),
			]
		);
		$fc_events = [];
		foreach ( $events as $event ) {
			if ( isset( $event->properties['class'] ) ) {
				$id = $event->properties['id'];
				if ( strpos( $event->properties['class'], 'Workshop' ) ) {
					$workshop = new \Kleistad\Workshop( $id );
					if ( ! $workshop->vervallen ) {
						$fc_events[] = [
							'id'            => $event->id,
							'title'         => "$workshop->naam ($workshop->code)",
							'start'         => $event->start->format( \DateTime::ATOM ),
							'end'           => $event->eind->format( \DateTime::ATOM ),
							'className'     => $workshop->betaald ? 'kleistad_workshop_betaald' :
								( $workshop->definitief ? 'kleistad_workshop_definitief' : 'kleistad_workshop_concept' ),
							'extendedProps' => [
								'naam'       => $workshop->naam,
								'aantal'     => $workshop->aantal,
								'docent'     => $workshop->docent,
								'technieken' => implode( ', ', $workshop->technieken ),
							],
						];
					}
				} elseif ( strpos( $event->properties['class'], 'Cursus' ) ) {
					$cursus = new \Kleistad\Cursus( $id );
					if ( ! $cursus->vervallen ) {
						$lopend      = $cursus->start_datum < strtotime( 'today' );
						$fc_events[] = [
							'id'            => $event->id,
							'title'         => $cursus->naam,
							'start'         => $event->start->format( \DateTime::ATOM ),
							'end'           => $event->eind->format( \DateTime::ATOM ),
							'className'     => $cursus->tonen || $lopend ? 'kleistad_cursus_tonen' : 'kleistad_cursus_concept',
							'extendedProps' => [
								'naam'       => "cursus $cursus->code",
								'aantal'     => $cursus->maximum - $cursus->ruimte(),
								'docent'     => $cursus->docent,
								'technieken' => implode( ', ', $cursus->technieken ),
							],
						];
					}
				}
			} else {
				$fc_events[] = [
					'id'              => $event->id,
					'title'           => $event->titel,
					'start'           => $event->start->format( \DateTime::ATOM ),
					'end'             => $event->eind->format( \DateTime::ATOM ),
					'backgroundColor' => 'violet',
					'textColor'       => 'black',
					'extendedProps'   => [
						'naam' => $event->titel,
					],
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
