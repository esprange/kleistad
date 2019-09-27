<?php
/**
 * Toon het regelingen formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2>Regeling<a class="add-new-h2"
		href="<?php echo esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=regelingen' ) ); ?>">terug naar lijst</a>
	</h2>

	<?php if ( ! empty( $notice ) ) : ?>
	<div id="notice" class="error"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $message ) ) : ?>
	<div id="message" class="updated"><p><?php echo esc_html( $message ); ?></p></div>
	<?php endif; ?>

	<form id="form" method="POST">
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'kleistad_regeling' ) ); ?>"/>
		<input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ); ?>"/>

		<div class="metabox-holder" id="poststuff">
			<div id="post-body">
				<div id="post-body-content">
					<?php do_meta_boxes( 'regeling', 'normal', $item ); ?>
					<?php submit_button(); ?>
				</div>
			</div>
		</div>
	</form>
</div>
