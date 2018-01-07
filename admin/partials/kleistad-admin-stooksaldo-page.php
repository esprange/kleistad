<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2>Stooksaldo</h2>
<?php echo $message; ?>

	<form id="stooksaldo-table" method="GET">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>"/>
		<?php
		$table->prepare_items();
		$table->search_box( 'zoek abonnee', 'search' );
		$table->display();
		?>
	</form>
</div>    
