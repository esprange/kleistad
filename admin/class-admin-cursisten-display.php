<?php
/**
 * De basis class voor de rendering van cursisten functies van de plugin.
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
class Admin_Cursisten_Display extends Admin_Display {

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
				<th scope="row"><label >Naam</label></th>
				<td>
					<?php echo esc_html( $item['naam'] ); ?>
					<input type="hidden" name="naam" value="<?php echo esc_attr( $item['naam'] ); ?>" >
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="cursus_id">Cursus</label></th>
				<td><select name="cursus_id" id="cursus_id" required >
				<?php
				$cursussen = new Cursussen();
				$vandaag   = strtotime( 'today' );
				foreach ( $cursussen as $cursus ) :
					if ( $vandaag > $cursus->eind_datum ) :
						continue;
					endif
					?>
						<option value="<?php echo esc_attr( $cursus->id ); ?>" <?php selected( $item['cursus_id'], $cursus->id ); ?>>
							<?php echo esc_html( "$cursus->code $cursus->naam" ); ?>
						</option>
					<?php
				endforeach;
				?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aantal">Aantal</label></th>
				<td>
					<input name="aantal" id="aantal" min="1" type="number" size="2" required value="<?php echo esc_attr( $item['aantal'] ); ?>">
				</td>
			</tr>
			<?php if ( count( $item['extra_cursisten'] ) ) : ?>
				<?php foreach ( $item['extra_cursisten'] as $extra_cursist ) : ?>
				<tr>
					<th scope="row"><label for="extra_cursist_<?php echo esc_attr( $extra_cursist ); ?>">Medecursist</label></th>
					<td>
						<input name="extra_cursisten[]" type="checkbox" id="extra_cursist_<?php echo esc_attr( $extra_cursist ); ?>"
							value="<?php echo esc_attr( $extra_cursist ); ?>"  checked >
							<?php echo esc_html( get_user_by( 'id', $extra_cursist )->display_name ); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
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
		<?php
	}

}
