<?php
/**
 * Tooe cursist meta box
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

namespace Kleistad;

?>

<table style="width: 100%; border-spacing:2px; padding:5px" class="form-table">
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label>Naam</label>
			</th>
			<td>
				<?php echo esc_html( $item['naam'] ); ?>
				<input type="hidden" name="naam" value="<?php echo esc_attr( $item['naam'] ); ?>" >
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="cursus_id">Cursus</label>
			</th>
			<td><select name="cursus_id" id="cursus_id" required class="code">
			<?php
			$cursussen = Cursus::all( true );
			foreach ( $cursussen as $cursus_id => $cursus ) :
				?>
					<option value="<?php echo esc_attr( $cursus_id ); ?>" <?php selected( $item['cursus_id'], $cursus_id ); ?>>
						<?php echo esc_html( "$cursus->code $cursus->naam" ); ?>
					</option>
				<?php
			endforeach;
			?>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="aantal">Aantal</label>
			</th>
			<td>
				<input name="aantal" id="aantal" type="number" style="width: 95%" required class="code" value="<?php echo esc_attr( $item['aantal'] ); ?>">
			</td>
		</tr>
	</tbody>
</table>

