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

/**
 * Definitie van de profiel class.
 */
class Profiel {

	/**
	 * Start een nieuw profiel
	 *
	 * @param WP_User|int $user Gebruiker of gebruik_id.
	 * @return string Het html opgemaakte profiel.
	 */
	public function reset( $user ) : string {
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
			$lijst     = $this->openstaande_orders( new Orders( $user->ID ) );
			$maxstatus = empty( $lijst ) ? 0 : max( array_column( $lijst, 'status' ) );
			$style     = [ 'display: none', 'background-color: lightblue', 'background-color: orange', 'background-color: red' ];
			ob_start();
			?>
<div class="kleistad kleistad-profiel">
	<p>
		<strong>Welkom <?php echo esc_html( $user->display_name ); ?></strong>
		<button id="kleistad-betaalinfo" class="kleistad-betaalinfo" style="<?php echo esc_attr( $style[ $maxstatus ] ); ?>;">&euro;</button>
	</p>
			<?php if ( count( $lijst ) ) : ?>
	<div class="kleistad-openstaand" style="display:none;">
		<table style="table-layout: auto;">
			<tr>
				<td colspan="2"><strong>Openstaande bestellingen</strong></td>
			</tr>
				<?php foreach ( $lijst as $item ) : ?>
			<tr>
				<td><span class="kleistad-dot" style="<?php echo esc_attr( $style[ $item['status'] ] ); ?>"></span></td>
				<td><?php echo $item['link']; // phpcs:ignore ?></td>
			</tr>
			<?php endforeach ?>
		</table>
	</div>
			<?php endif ?>
</div>
			<?php
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
		$tweewekeneerder = strtotime( '- 2 weeks', $vandaag );
		$artikelregister = new Artikelregister();
		foreach ( $orders as $order ) {
			if ( $order->gesloten || $order->transactie_id ) {
				continue;
			}
			$artikel = $artikelregister->geef_object( $order->referentie );
			if ( is_null( $artikel ) ) {
				continue;
			}
			$lijst[] = [
				'status' => $order->verval_datum > $vandaag ? 1 : ( $order->verval_datum > $tweewekeneerder ? 2 : 3 ),
				'link'   => 'factuur ' . $artikel->maak_link(
					[
						'order' => $order->id,
						'art'   => $artikel->artikel_type,
					],
					'betaling',
					$order->factuurnummer()
				) . ' : &euro;' . number_format_i18n( $order->te_betalen(), 2 ),
			];
		}
		return $lijst;
	}

}
