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
			$betaalstatus    = 0;
			$vandaag         = strtotime( 'today' );
			$artikelregister = new Artikelregister();
			$betaallinks     = [];
			$infokleur       = [ 'lightblue', 'orange', 'red' ];
			foreach ( new Orders( $user->ID ) as $order ) {
				if ( $order->gesloten || $order->transactie_id ) {
					continue;
				}
				$artikel = $artikelregister->geef_object( $order->referentie );
				if ( is_null( $artikel ) ) {
					continue;
				}
				$betaalstatus  = max( $betaalstatus, $order->verval_datum > $vandaag ? 1 : 2 );
				$betaallinks[] = 'factuur ' . $artikel->maak_link(
					[
						'order' => $order->id,
						'art'   => $artikel->artikel_type,
					],
					'betaling',
					$order->factuurnummer()
				) . ' : &euro;' . number_format_i18n( $order->te_betalen(), 2 );
			}
			ob_start();
			?>
<div class="kleistad kleistad-profiel">
	<p>
		<strong>Welkom <?php echo esc_html( $user->display_name ); ?></strong>
		<button id="kleistad-betaalinfo" class="kleistad-betaalinfo" style="background-color:<?php echo esc_attr( $infokleur[ $betaalstatus ] ); ?>">&euro;</button>
	</p>
			<?php if ( count( $betaallinks ) ) : ?>
	<div class="kleistad-openstaand" style="display:none">
		<strong>Openstaande bestellingen</strong><br/>
				<?php
				foreach ( $betaallinks as $betaallink ) :
					echo $betaallink . '<br/>'; // phpcs:ignore
				endforeach;
			endif
			?>
	</div>
</div>
			<?php
			$profiel = ob_get_clean();
			set_transient( "kleistad_profiel_$user->ID", $profiel, 900 );
		}
		return $profiel;
	}

}
