<?php
/**
 * De basis class voor de rendering van admin-specifieke functies van de plugin.
 *
 * @link https://www.kleistad.nl
 * @since 4.0.87
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * Admin display class
 */
abstract class Admin_Display {

	/**
	 * Toon de metabox
	 *
	 * @param array $item  Het weer te geven object in de meta box.
	 * @param array $metabox De metabox argumenten.
	 * @return void
	 */
	abstract public function form_meta_box( array $item, array $metabox ) : void;

	/**
	 * Toon de pagina
	 *
	 * @return void
	 */
	abstract public function page() : void;

	/**
	 * Toon het overzicht
	 *
	 * @param array  $item         Het item.
	 * @param string $single       De benaming van een enkel item.
	 * @param string $multiple     De benaming van het meervoud van items.
	 * @param string $notice       Een eventuele foutmelding.
	 * @param string $message      Een eventuele succes melding.
	 * @param bool   $display_only Alleen weergeven vlag.
	 * @return void
	 */
	public function form_page( array $item, string $single, string $multiple, string $notice, string $message, bool $display_only ) : void {
		?>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h2><?php echo esc_html( ucfirst( $single ) ); ?><a class="add-new-h2"
						href="<?php echo esc_url( get_admin_url( get_current_blog_id(), "admin.php?page=$multiple" ) ); ?>">terug naar lijst</a>
			</h2>
			<?php if ( ! empty( $notice ) ) : ?>
			<div id="notice" class="error"><p><?php echo $notice; // phpcs:ignore ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $message ) ) : ?>
			<div id="message" class="updated"><p><?php echo esc_html( $message ); ?></p></div>
			<?php endif; ?>
			<form id="form" method="POST">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( "kleistad_$single" ) ); ?>"/>
				<?php if ( isset( $item['id'] ) ) : ?>
					<input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ); ?>"/>
				<?php endif ?>
				<div class="metabox-holder" id="poststuff">
					<div id="post-body">
						<div id="post-body-content">
							<?php do_meta_boxes( $single, 'normal', $item ); ?>
							<?php
							if ( ! $display_only ) :
								submit_button(); endif
							?>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

}
