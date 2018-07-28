<?php
/**
 * Tooe cursist meta box
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

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
			$cursussen = Kleistad_Cursus::all( true );
			foreach ( $cursussen as $cursus_id => $cursus ) :
				?>
					<option value="<?php echo esc_attr( $cursus_id ); ?>" <?php selected( $item['cursus_id'], $cursus_id ); ?>>
						<?php echo esc_html( $cursus->naam ); ?>
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
				<input name="aantal" id="aantal" class="code" value="<?php echo esc_attr( $item['aantal'] ); ?>" type="number" step="1" min="1" />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="i_betaald">Inschrijfgeld betaald</label>
			</th>
			<td>
				<input name="i_betaald" id="i_betaald" class="code" value="1" type="checkbox" <?php checked( $item['i_betaald'] ); ?> />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="c_betaald">Cursusgeld betaald</label>
			</th>
			<td>
				<input name="c_betaald" id="c_betaald" class="code" value="1" type="checkbox" <?php checked( $item['c_betaald'] ); ?> />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="geannuleerd">Geannuleerd</label>
			</th>
			<td>
				<input name="geannuleerd" id="geannuleerd" class="code" value="1" type="checkbox" <?php checked( $item['geannuleerd'] ); ?> />
			</td>
		</tr>
	</tbody>
</table>

