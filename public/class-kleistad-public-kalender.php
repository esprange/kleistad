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
					'maand',
					'dag',
					'jaar',
					'modus',
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
	 * @suppress PhanPluginMixedKeyNoKey, PhanUnusedVariable
	 */
	public static function callback_kalender( WP_REST_Request $request ) {
		$dag    = $request->get_param( 'dag' );
		$maand  = $request->get_param( 'maand' );
		$jaar   = $request->get_param( 'jaar' );
		$modus  = $request->get_param( 'modus' );
		$events = Kleistad_Event::query(
			[
				'timeMin' => date( 'c', mktime( 0, 0, 0, $maand, 1, $jaar ) ),
				'timeMax' => date( 'c', mktime( 0, 0, 0, $maand + 1, 0, $jaar ) ),
			]
		);
		$dagen  = [];
		if ( 'maand' === $modus ) {
			foreach ( $events as $event ) {
				switch ( $event->properties['class'] ) {
					case 'Kleistad_Workshop':
						$workshop = new Kleistad_Workshop( $event->properties['id'] );
						$dagen[ intval( $event->start->format( 'd' ) ) ][] = [
							'tekst' => strftime( '%H:%M', $workshop->start_tijd ) . ' ' . $workshop->naam,
							'kleur' => $workshop->betaald ? 'green' : ( $workshop->definitief ? 'orange' : 'lightblue' ),
							'info'  => [
								'naam'       => $workshop->naam,
								'aantal'     => $workshop->aantal,
								'code'       => $workshop->code,
								'docent'     => $workshop->docent,
								'technieken' => implode( ', ', $workshop->technieken ),
								'start'      => strftime( '%H:%M', $workshop->start_tijd ),
								'eind'       => strftime( '%H:%M', $workshop->eind_tijd ),
							],
						];
						break;
					case 'Kleistad_Cursus':
						$cursus = new Kleistad_Cursus( $event->properties['id'] );
						$dagen[ intval( $event->start->format( 'd' ) ) ][] = [
							'tekst' => strftime( '%H:%M', $cursus->start_tijd ) . ' cursus',
							'kleur' => $cursus->tonen ? 'green' : 'lightblue',
							'info'  => [
								'naam'       => $cursus->naam,
								'aantal'     => $cursus->maximum - $cursus->ruimte,
								'code'       => $cursus->code,
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
			}
		}
		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/kleistad-public-kalender.php';
		$html = ob_get_contents();
		ob_clean();

		return new WP_REST_response(
			[
				'html' => $html,
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
	public function prepare( &$data = null ) {

		return true;
	}

}
