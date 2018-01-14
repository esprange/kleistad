<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label for="gebruiker_naam">Naam gebruiker</label>
			</th>
			<td>
				<?php if ( 0 === $item['gebruiker_id'] ) : ?>
				<select name="gebruiker_id" id="gebruiker_id" style="width: 95%" required>
					<?php
					foreach ( $gebruikers as $gebruiker ) :
						if ( Kleistad_Roles::reserveer( $gebruiker->id ) ) {
							$selected = ( $item['gebruiker_id'] == $gebruiker->id ) ? 'selected' : '';  // WPCS: loose comparison ok.
							?>
						<option value="<?php echo esc_attr( $gebruiker->id ); ?>" <?php echo esc_attr( $selected ); ?> ><?php echo esc_html( $gebruiker->display_name ); ?></option>
						<?php } endforeach ?>
				</select>
				<?php else : ?>
					<input type ="hidden" id="gebruiker_id" name="gebruiker_id" value="<?php echo esc_attr( $item['gebruiker_id'] ); ?>" >
					<input type ="hidden" id="gebruiker_naam" name="gebruiker_naam" value="<?php echo esc_attr( $item['gebruiker_naam'] ); ?>" >
					<?php echo esc_html( $item['gebruiker_naam'] ); ?>
				<?php endif ?>
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="oven_id">Naam oven</label>
			</th>
			<td>
				<?php if ( 0 === $item['oven_id'] ) : ?>
				<select name="oven_id" id="oven_id" style="width: 95%" required>
					<?php
					foreach ( $ovens as $oven ) :
						$selected = ( $item['oven_id'] == $oven->id ) ? 'selected' : ''; // WPCS: loose comparison ok.
						?>
					  <option value="<?php echo esc_attr( $oven->id ); ?>" <?php echo esc_attr( $selected ); ?> ><?php echo esc_html( $oven->naam ); ?></option>
					<?php endforeach ?>
				</select>
				<?php else : ?>
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
				<input id="kosten" name="kosten" type="number" style="width: 95%" value="<?php echo esc_attr( $item['kosten'] ); ?>"
					   size="10" step="0.01" class="code" placeholder="99.99" required>
			</td>
		</tr>
	</tbody>
</table>

