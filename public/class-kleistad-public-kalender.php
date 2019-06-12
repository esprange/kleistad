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

/**
 * De kleistad kalender class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Kalender extends Kleistad_Shortcode {

	/**
	 * Register rest URI's.
	 *
	 * @since 5.0.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Kleistad_Public::url(),
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
	 * @param WP_REST_Request $request De parameters van de Ajax call.
	 * @return \WP_REST_response
	 */
	public static function callback_kalender( WP_REST_Request $request ) {
		$events    = Kleistad_Event::query(
			[
				'timeMin' => $request->get_param( 'start' ),
				'timeMax' => $request->get_param( 'eind' ),
			]
		);
		$fc_events = [];
		foreach ( $events as $event ) {
			if ( isset( $event->properties['class'] ) ) {
				$id = $event->properties['id'];
				switch ( $event->properties['class'] ) {
					case 'Kleistad_Workshop':
						$workshop = new Kleistad_Workshop( $id );
						if ( $workshop->vervallen ) {
							continue;
						}
						$fc_events[] = [
							'id'            => $event->id,
							'title'         => "$workshop->naam ($workshop->code)",
							'start'         => $event->start->format( DateTime::ATOM ),
							'end'           => $event->eind->format( DateTime::ATOM ),
							'className'     => $workshop->betaald ? 'kleistad_workshop_betaald' :
								( $workshop->definitief ? 'kleistad_workshop_definitief' : 'kleistad_workshop_concept' ),
							'extendedProps' => [
								'naam'       => $workshop->naam,
								'aantal'     => $workshop->aantal,
								'docent'     => $workshop->docent,
								'technieken' => implode( ', ', $workshop->technieken ),
								'start'      => strftime( '%H:%M', $workshop->start_tijd ),
								'eind'       => strftime( '%H:%M', $workshop->eind_tijd ),
							],
						];
						break;
					case 'Kleistad_Cursus':
						$cursus = new Kleistad_Cursus( $id );
						if ( $cursus->vervallen ) {
							continue;
						}
						$lopend      = $cursus->start_datum < strtotime( 'today' );
						$fc_events[] = [
							'id'            => $event->id,
							'title'         => $cursus->naam,
							'start'         => $event->start->format( DateTime::ATOM ),
							'end'           => $event->eind->format( DateTime::ATOM ),
							'className'     => $cursus->tonen || $lopend ? 'kleistad_cursus_tonen' : 'kleistad_cursus_concept',
							'extendedProps' => [
								'naam'       => "cursus $cursus->code",
								'aantal'     => $cursus->maximum - $cursus->ruimte(),
								'docent'     => $cursus->docent,
								'technieken' => implode( ', ', $cursus->technieken ),
								'start'      => strftime( '%H:%M', $cursus->start_tijd ),
								'eind'       => strftime( '%H:%M', $cursus->eind_tijd ),
							],
						];
						break;
					default:
						break;
				}
			} else {
				$fc_events[] = [
					'id'              => $event->id,
					'title'           => $event->titel,
					'start'           => $event->start->format( DateTime::ATOM ),
					'end'             => $event->eind->format( DateTime::ATOM ),
					'backgroundColor' => 'violet',
					'textColor'       => 'black',
					'editable'        => false,
					'extendedProps'   => [
						'naam'  => $event->titel,
						'start' => $event->start->format( 'H:i' ),
						'eind'  => $event->eind->format( 'H:i' ),
					],
				];
			}
		}
		return new WP_REST_response(
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
	protected function prepare( &$data = null ) {
		return true;
	}

}
