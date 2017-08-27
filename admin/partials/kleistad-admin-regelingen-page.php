<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */
?>
<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2>Regelingen<a class="add-new-h2"
				href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=regelingen_form' ); ?>">Toevoegen</a>
	</h2>
<?php echo $message; ?>

	<form id="regelingen-table" method="GET">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>"/>
		<?php $table->display(); ?>
	</form>
</div>    
