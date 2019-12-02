<?php
/**
 * Toon het stooksaldo meta box
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<table style="width: 100%; border-spacing:2px; padding:5px" class="form-table">
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label>Naam gebruiker</label>
			</th>
			<td>
				<?php echo esc_html( $item['naam'] ); ?>
				<input name="naam" type="hidden" value="<?php echo esc_attr( $item['naam'] ); ?>" >
				<input name="id" type="hidden" value="<?php echo esc_attr( $item['id'] ); ?>" >
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="saldo">Saldo</label>
			</th>
			<td>
				<input id="saldo" name="saldo" type="number" style="width: 95%" value="<?php echo esc_attr( sprintf( '%.2f', $item['saldo'] ) ); ?>"
					step="0.01" class="code" placeholder="99.99" required>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label>Toevoegen</label>
			</th>
			<td>
				<button type="button" id="add15">15</button>&nbsp;
				<button type="button" id="add30">30</button>
			</td>
		</tr>
	</tbody>
</table>

