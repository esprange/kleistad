<?php
/**
 * Toon het oven overzicht pagina
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

$table = new Kleistad_Admin_Ovens();
?>
<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2>Ovens<a class="add-new-h2"
				href="<?php echo esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=ovens_form' ) ); ?>">Toevoegen</a>
	</h2>
	<form id="ovens-table" method="GET">
		<input type="hidden" name="page" value="<?php esc_attr( filter_input( INPUT_GET, 'page' ) ); ?>"/>
		<?php
			$table->prepare_items();
			$table->display();
		?>
	</form>
</div>
