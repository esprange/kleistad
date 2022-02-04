<?php
/**
 * De class voor de rendering van recepttermen functies van de plugin.
 *
 * @link https://www.kleistad.nl
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * Admin display class
 */
class Admin_Recepttermen_Display extends Admin_Display {

	/**
	 * Toon de metabox
	 *
	 * @param array $item Het weer te geven object in de meta box.
	 * @param array $metabox De metabox argumenten.
	 * @return void
	 *
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function form_meta_box( array $item, array $metabox ) : void {
		$hoofdterm = (array) get_term( filter_input( INPUT_GET, 'hoofdterm_id', FILTER_SANITIZE_NUMBER_INT ) );
		?>
		<h2><?php echo esc_html( $hoofdterm['description'] ); ?></h2>
		<input name="hoofdterm_id" type="hidden" value="<?php echo esc_attr( $hoofdterm['term_id'] ); ?>" >
		<table class="form-table">
			<tbody>
				<tr class="form-field">
					<th  scope="row">
						<label for="naam">Naam</label>
					</th>
					<td>
						<input id="naam" name="naam" type="text" style="width: 95%" value="<?php echo esc_attr( $item['naam'] ); ?>"
							size="50" class="code" placeholder="De recept term naam" required>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Toon de pagina
	 *
	 * @return void
	 */
	public function page() : void {
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
			<form id="ovens-table" method="GET">
				<input type="hidden" name="page" value="<?php esc_attr( filter_input( INPUT_GET, 'page' ) ); ?>"/>
				<!--suppress HtmlFormInputWithoutLabel -->
				<select name="hoofdterm_id" id="hoofdterm_id" >
					<?php foreach ( Recept::hoofdtermen() as $hoofdterm ) : ?>
						<option value="<?php echo esc_attr( $hoofdterm->term_id ); ?>" <?php selected( $hoofdterm->term_id, $hoofdterm_id ); ?> ><?php echo esc_html( $hoofdterm->description ); ?></option>
					<?php endforeach ?>
				</select>
				<?php
					$table->prepare_items();
					$table->display();
				?>
			</form>
		</div>
		<?php
	}

}
