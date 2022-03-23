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
use DateTimeInterface;

/**
 * De kleistad kalender class.
 */
class Public_Kalender extends Shortcode {

	/**
	 * Prepareer 'kalender' form
	 *
	 * @return string
	 */
	protected function prepare() : string {
		return $this->content();
	}

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
	 * @param Afspraak $afspraak De afspraak.
	 * @return array
	 */
	private static function workshop_event( Afspraak $afspraak ) : array {
		$workshop = new Workshop( (int) strpbrk( $afspraak->id, '0123456789' ) );
		if ( ! $workshop->vervallen ) {
			$betaald = $workshop->is_betaald();
			return [
				'id'              => $afspraak->id,
				'title'           => "$workshop->naam ($workshop->code)",
				'start'           => $afspraak->start->format( DateTimeInterface::ATOM ),
				'end'             => $afspraak->eind->format( DateTimeInterface::ATOM ),
				'backgroundColor' => $betaald ? 'green' : ( $workshop->definitief ? 'springgreen' : 'orange' ),
				'textColor'       => $betaald ? 'white' : 'black',
				'extendedProps'   => [
					'naam'       => $workshop->naam,
					'aantal'     => $workshop->aantal,
					'docent'     => $workshop->get_docent_naam() ?: 'n.b.',
					'technieken' => implode( ', ', $workshop->technieken ),
				],
			];
		}
		return [];
	}

	/**
	 * Verwerk een cursus event
	 *
	 * @param Afspraak $afspraak De afspraak.
	 * @return array
	 */
	private static function cursus_event( Afspraak $afspraak ) : array {
		$cursus = new Cursus( (int) strpbrk( $afspraak->id, '0123456789' ) );
		if ( ! $cursus->vervallen ) {
			return [
				'id'              => $afspraak->id,
				'title'           => "$cursus->naam ($cursus->code)",
				'start'           => $afspraak->start->format( DateTimeInterface::ATOM ),
				'end'             => $afspraak->eind->format( DateTimeInterface::ATOM ),
				'backgroundColor' => $cursus->tonen || $cursus->start_datum < strtotime( 'today' ) ? 'slateblue' : 'lightblue',
				'textColor'       => $cursus->tonen || $cursus->start_datum < strtotime( 'today' ) ? 'white' : 'black',
				'extendedProps'   => [
					'naam'       => 'cursus',
					'aantal'     => $cursus->maximum - $cursus->get_ruimte(),
					'docent'     => $cursus->get_docent_naam() ?: 'n.b.',
					'technieken' => implode( ', ', $cursus->technieken ),
				],
			];
		}
		return [];
	}

	/**
	 * Verwerk een overig event
	 *
	 * @param Afspraak $afspraak De afspraak.
	 * @return array
	 */
	private static function overig_event( Afspraak $afspraak ) : array {
		return [
			'id'              => $afspraak->id,
			'title'           => $afspraak->titel ?: '',
			'start'           => $afspraak->start->format( DateTimeInterface::ATOM ),
			'end'             => $afspraak->eind->format( DateTimeInterface::ATOM ),
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
		$afspraken = new Afspraken(
			[
				'timeMin' => $request->get_param( 'start' ),
				'timeMax' => $request->get_param( 'eind' ),
			]
		);
		$fc_events = [];
		foreach ( $afspraken as $afspraak ) {
			if ( str_contains( $afspraak->id, 'kleistadevent' ) ) {
				$fc_events[] = self::workshop_event( $afspraak );
				continue;
			}
			if ( str_contains( $afspraak->id, 'kleistadcursus' ) ) {
				$fc_events[] = self::cursus_event( $afspraak );
				continue;
			}
			$fc_events[] = self::overig_event( $afspraak );
		}
		return new WP_REST_Response(
			[
				'events' => array_filter( $fc_events ),
			]
		);
	}

}
