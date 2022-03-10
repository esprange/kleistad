<?php
/**
 * De class voor de rendering van stooksaldo functies van de plugin.
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
class Admin_Stooksaldo_Display extends Admin_Display {

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
			<tr>
				<th scope="row"><label>Naam gebruiker</label></th>
				<td>
					<?php echo esc_html( $item['naam'] ); ?>
					<input name="naam" type="hidden" value="<?php echo esc_attr( $item['naam'] ); ?>" >
					<input name="id" type="hidden" value="<?php echo esc_attr( $item['id'] ); ?>" >
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="saldo">Saldo</label></th>
				<td>
					<input id="saldo" name="saldo" type="number" value="<?php echo esc_attr( sprintf( '%.2f', $item['saldo'] ) ); ?>"
						step="0.01" size="5" placeholder="99.99" required>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>Toevoegen</label></th>
				<td>
					<button type="button" id="add15">15</button>&nbsp;
					<button type="button" id="add30">30</button>
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
		$table = new Admin_Stooksaldo();
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
		<?php
	}

}
