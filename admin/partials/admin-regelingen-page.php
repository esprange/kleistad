<?php
/**
 * Toon de regelingen pagina
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
	<h2>Regelingen<a class="add-new-h2"
				href="<?php echo esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=regelingen_form' ) ); ?>">Toevoegen</a>
	</h2>
	<?php if ( '' !== $message ) : ?>
	<div class="updated below-h2" id="message"><p>
		<?php echo esc_html( $message ); ?>
	</p></div>
	<?php endif; ?>
	<form id="regelingen-table" method="GET">
		<input type="hidden" name="page" value="<?php echo filter_input( INPUT_GET, 'page' ); ?>"/>
		<?php
			$table->prepare_items();
			$table->display();
		?>
	</form>
</div>
