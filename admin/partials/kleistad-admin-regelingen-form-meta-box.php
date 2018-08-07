<?php
/**
 * Toon de regelingen meta box
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

?>
<table style="width: 100%;border-spacing: 2px; padding: 5px" class="form-table">
	<tbody>
		<tr class="form-field">
				<?php if ( 0 === $item['gebruiker_id'] ) : ?>
			<th  scope="row">
				<label for="gebruiker_id">Naam gebruiker</label>
			</th>
			<td>
				<select name="gebruiker_id" id="gebruiker_id" style="width: 95%" required>
					<?php
					foreach ( $gebruikers as $gebruiker ) :
						if ( Kleistad_Roles::reserveer( $gebruiker->ID ) ) {
							?>
						<option value="<?php echo esc_attr( $gebruiker->ID ); ?>" <?php selected( $item['gebruiker_id'], $gebruiker->ID ); ?> ><?php echo esc_html( $gebruiker->display_name ); ?></option>
						<?php } endforeach ?>
				</select>
				<?php else : ?>
			<th  scope="row">
				<label>Naam gebruiker</label>
			</th>
			<td>
				<input type ="hidden" id="gebruiker_id" name="gebruiker_id" value="<?php echo esc_attr( $item['gebruiker_id'] ); ?>" >
				<input type ="hidden" id="gebruiker_naam" name="gebruiker_naam" value="<?php echo esc_attr( $item['gebruiker_naam'] ); ?>" >
				<?php echo esc_html( $item['gebruiker_naam'] ); ?>
				<?php endif ?>
			</td>
		</tr>
		<tr class="form-field">
		<?php if ( 0 === $item['oven_id'] ) : ?>
			<th  scope="row">
				<label for="oven_id">Naam oven</label>
			</th>
			<td>
				<select name="oven_id" id="oven_id" style="width: 95%" required>
					<?php
					foreach ( $ovens as $oven ) :
						$selected = ( $item['oven_id'] == $oven->id ) ? 'selected' : ''; // WPCS: loose comparison ok.
						?>
					<option value="<?php echo esc_attr( $oven->id ); ?>" <?php echo esc_attr( $selected ); ?> ><?php echo esc_html( $oven->naam ); ?></option>
					<?php endforeach ?>
				</select>
				<?php else : ?>
			<th  scope="row">
				<label>Naam oven</label>
			</th>
			<td>
				<input type ="hidden" id="oven_id" name="oven_id" value="<?php echo esc_attr( $item['oven_id'] ); ?>" >
				<input type ="hidden" id="oven_naam" name="oven_naam" value="<?php echo esc_attr( $item['oven_naam'] ); ?>" >
				<?php echo esc_html( $item['oven_naam'] ); ?>
				<?php endif ?>
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="kosten">Tarief</label>
			</th>
			<td>
				<input id="kosten" name="kosten" type="number" style="width: 95%" value="<?php echo esc_attr( sprintf( '%.2f', $item['kosten'] ) ); ?>"
					size="10" step="0.01" class="code" placeholder="99.99" required>
			</td>
		</tr>
	</tbody>
</table>

