<?php
/**
 * Toon het stooksaldo overzicht pagina
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

$table = new \Kleistad\Admin_Stooksaldo();
?>
<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2>Stooksaldo</h2>
	<form id="stooksaldo-table" method="GET">
		<input type="hidden" name="page" value="<?php echo filter_input( INPUT_GET, 'page' ); ?>"/>
		<?php
		$table->prepare_items();
		$table->search_box( 'zoek abonnee', 'search' );
		$table->display();
		?>
	</form>
</div>
