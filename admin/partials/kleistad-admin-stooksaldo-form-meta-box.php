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
				<label for="naam">Naam gebruiker</label>
			</th>
			<td>
				<?php echo $item['naam']; ?>
				<input name="naam" type="hidden" value="<?php echo $item['naam']; ?>" >
				<input name="id" type="hidden" value="<?php echo $item['id']; ?>" >
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="saldo">Saldo</label>
			</th>
			<td>
				<input id="saldo" name="saldo" type="number" style="width: 95%" value="<?php echo esc_attr( $item['saldo'] ); ?>"
					   size="10" step="0.01" class="code" placeholder="99.99" required>
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

