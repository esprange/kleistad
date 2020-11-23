<?php
/**
 * Toon het recept termen overzicht pagina
 *
 * @link       https://www.kleistad.nl
 * @since      6.4.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

namespace Kleistad;

$hoofdterm_id = filter_input(
	INPUT_GET,
	'hoofdterm_id',
	FILTER_SANITIZE_NUMBER_INT,
	[
		'options' => [ 'default' => Recept::hoofdtermen()[ Recept::GLAZUUR ]->term_id ],
	]
);
$table        = new Admin_Recepttermen( $hoofdterm_id );
?>
<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2>Recept termen <a class="add-new-h2" id="kleistad_toevoegen"
				href="<?php echo esc_url( get_admin_url( get_current_blog_id(), "admin.php?page=recepttermen_form&hoofdterm_id=$hoofdterm_id" ) ); ?>">Toevoegen</a>
	</h2>

	<script language="javascript" type="text/javascript">
	function hoofdtermSwitch( hoofdterm_id ) {
		var href = new URL( document.location );
		href.searchParams.set( 'hoofdterm_id', hoofdterm_id );
		document.location = href.toString();
	}
	</script>
	<select name="hoofdterm_id" onChange="hoofdtermSwitch(this.value);" >
		<?php foreach ( Recept::hoofdtermen() as $hoofdterm ) : ?>
			<option value="<?php echo esc_attr( $hoofdterm->term_id ); ?>" <?php selected( $hoofdterm->term_id, $hoofdterm_id ); ?> ><?php echo esc_html( $hoofdterm->description ); ?></option>
		<?php endforeach ?>
	</select>

	<form id="ovens-table" method="GET">
		<input type="hidden" name="page" value="<?php esc_attr( filter_input( INPUT_GET, 'page' ) ); ?>"/>
		<?php
			$table->prepare_items();
			$table->display();
		?>
	</form>
</div>
