<?php
/**
 * Toon abonnee overzicht page
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2>Abonnees</h2>
	<?php if ( '' !== $message ) : ?>
	<div class="updated below-h2" id="message"><p>
		<?php echo esc_html( $message ); ?>
	</p></div>
	<?php endif; ?>
	<form id="abonnees-table" method="GET">
		<input type="hidden" name="page" value="<?php echo filter_input( INPUT_GET, 'page' ); ?>"/>
		<?php
			$table->prepare_items();
			$table->search_box( 'zoek abonnee', 'search' );
			$table->display();
		?>
	</form>
</div>
