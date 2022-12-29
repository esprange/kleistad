<?php
/**
 * Class voor weergave profiel.
 *
 * @link       https://www.kleistad.nl
 * @since      6.20.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_User;
use WP_REST_Response;

/**
 * Definitie van de profiel class.
 */
class Profiel {

	/**
	 * Register rest URI's.
	 *
	 * @since 6.20.3
	 */
	public static function register_rest_routes() : void {
		register_rest_route(
			KLEISTAD_API,
			'/profiel',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_profiel' ],
				'permission_callback' => function() {
					return true;
				},
			]
		);
	}

	/**
	 * Ajax callback voor profiel functie.
	 *
	 * @return WP_REST_Response
	 */
	public static function callback_profiel() : WP_REST_Response {
		$html = '';
		$user = wp_get_current_user();
		if ( $user->exists() && setup()['profiel'] ) {
			$profiel = new Profiel();
			$html    = $profiel->prepare( $user ); // phpcs:ignore
		}
		return new WP_REST_Response( [ 'html' => $html ] );
	}

	/**
	 * Start een nieuw profiel
	 *
	 * @param int|WP_User $user Gebruiker of gebruik_id.
	 * @return string Het html opgemaakte profiel.
	 */
	public function reset( int|WP_User $user ) : string {
		if ( defined( 'DOING_CRON' ) ) {
			return '';
		}
		if ( is_int( $user ) ) {
			if ( 0 === $user ) {
				return '';
			}
			$user = get_user_by( 'ID', $user );
			if ( ! is_a( $user, 'WP_User' ) ) {
				return '';
			}
		}
		delete_transient( "kleistad_profiel_$user->ID" );
		return $this->prepare( $user );
	}

	/**
	 * Geef een profiel
	 *
	 * @param WP_User $user Gebruiker.
	 * @return string Het html opgemaakte profiel.
	 */
	public function prepare( WP_User $user ) : string {
		$profiel = 'development' !== wp_get_environment_type() ? get_transient( "kleistad_profiel_$user->ID" ) : false;
		if ( false === $profiel ) {
			/**
			 * Bepaal openstaande vorderingen
			 */
			$lijst     = $this->openstaande_orders( new Orders( [ 'klant_id' => $user->ID ] ) );
			$maxstatus = empty( $lijst ) ? 0 : max( array_column( $lijst, 'status' ) );
			$style     = [ 'display: none', 'background-color: lightblue', 'background-color: orange', 'background-color: red' ];
			ob_start();
			?>
	<div id="kleistad_profiel_container">
		<strong>Welkom <?php echo esc_html( $user->display_name ); ?></strong>
			<?php if ( count( $lijst ) ) : ?>
		<button id="kleistad_betaalinfo" class="kleistad-betaalinfo" style="<?php echo esc_attr( $style[ $maxstatus ] ); ?>;">&euro;</button>
		<br/>
		<div id="kleistad_openstaand" class="kleistad-openstaand" style="display: none;" >
			<table>
				<tr>
					<td colspan="4"><strong>Openstaande facturen</strong></td>
				</tr>
					<?php foreach ( $lijst as $item ) : ?>
				<tr>
					<td><span class="kleistad-dot" style="<?php echo esc_attr( $style[ $item['status'] ] ); ?>"></span></td>
					<td>factuur</td>
					<td><?php echo $item['link']; // phpcs:ignore ?></td>
					<td style="text-align: right">&euro; <?php echo esc_html( $item['bedrag'] ); ?></td>
				</tr>
				<?php endforeach ?>
			</table>
		</div>
	</div>
				<?php
			endif;
			$profiel = ob_get_clean();
			set_transient( "kleistad_profiel_$user->ID", $profiel, 900 );
		}
		return $profiel;
	}

	/**
	 * Bepaald de openstaande orders, inclusief hun status.
	 *
	 * @param Orders $orders De orders.
	 *
	 * @return array Assoc array met links, vervaldatum en status.
	 */
	private function openstaande_orders( Orders $orders ) : array {
		$lijst           = [];
		$vandaag         = strtotime( 'today ' );
		$tweewekeneerder = strtotime( '- 2 weeks 0:00', $vandaag );
		$artikelregister = new Artikelregister();
		foreach ( $orders as $order ) {
			if ( $order->gesloten || $order->transactie_id ) {
				continue;
			}
			$artikel = $artikelregister->get_object( $order->referentie );
			if ( is_null( $artikel ) ) {
				continue;
			}
			$lijst[] = [
				'status' => $order->verval_datum > $vandaag ? 1 : ( $order->verval_datum > $tweewekeneerder ? 2 : 3 ),
				'link'   => $artikel->get_link( [ 'order' => $order->id ], 'betaling', $order->get_factuurnummer() ),
				'bedrag' => number_format_i18n( $order->get_te_betalen(), 2 ),
			];
		}
		return $lijst;
	}

}
