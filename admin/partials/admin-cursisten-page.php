<?php
/**
 * Toon cursist overzicht page
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

namespace Kleistad;

$table = new Admin_Cursisten();
?>
<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2>Cursisten</h2>
	<form id="cursisten-table" method="GET">
		<input type="hidden" name="page" value="<?php echo filter_input( INPUT_GET, 'page' ); ?>"/>
		<?php
			$table->prepare_items();
			$table->search_box( 'zoek cursist', 'search' );
			$table->display();
		?>
	</form>
</div>
