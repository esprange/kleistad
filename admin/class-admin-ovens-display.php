<?php
/**
 * De class voor de rendering van ovens functies van de plugin.
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
class Admin_Ovens_Display extends Admin_Display {

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
		?>
		<table class="form-table">
			<tbody>
				<tr class="form-field">
					<th  scope="row"><label for="naam">Naam</label></th>
					<td>
						<input id="naam" name="naam" type="text" style="width: 95%" value="<?php echo esc_attr( $item['naam'] ); ?>"
							size="50" class="code" placeholder="De oven naam" required>
					</td>
				</tr>
				<tr class="form-field">
					<th  scope="row"><label for="kosten_laag">Laag tarief</label></th>
					<td>
						<input id="kosten_laag" name="kosten_laag" type="number" value="<?php echo esc_attr( $item['kosten_laag'] ); ?>"
							step="0.01" class="code" placeholder="99.99" required />
					</td>
				</tr>
				<tr class="form-field">
					<th  scope="row"><label for="kosten_midden">Midden tarief</label></th>
					<td>
						<input id="kosten_midden" name="kosten_midden" type="number" value="<?php echo esc_attr( $item['kosten_midden'] ); ?>"
							step="0.01" class="code" placeholder="99.99" required>
					</td>
				</tr>
				<tr class="form-field">
					<th  scope="row"><label for="kosten_hoog">Hoog tarief</label></th>
					<td>
						<input id="kosten_hoog" name="kosten_hoog" type="number" value="<?php echo esc_attr( $item['kosten_hoog'] ); ?>"
							step="0.01" class="code" placeholder="99.99" required>
					</td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label>Beschikbaarheid</label></th>
					<td>
						<?php
						for ( $dagnummer = 1; $dagnummer <= 7; $dagnummer++ ) :
							$dagnaam = strftime( '%A', mktime( 0, 0, 0, 1, $dagnummer, 2018 ) );
							?>
						<input name="beschikbaarheid[]" value="<?php echo esc_attr( $dagnaam ); ?>" type="checkbox" <?php checked( array_search( $dagnaam, $item['beschikbaarheid'], true ) !== false ); ?> />
							<?php
							echo esc_html( ucfirst( $dagnaam ) ) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							endfor
						?>
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
		$table = new Admin_Ovens();
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
		<?php
	}
}
