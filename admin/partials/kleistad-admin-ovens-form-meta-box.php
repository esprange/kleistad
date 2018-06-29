<?php
/**
 * Toon de oven meta box
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

?>
<table style="width: 100%;border-spacing: 2px;padding: 5px" class="form-table">
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label for="naam">Naam</label>
			</th>
			<td>
				<input id="naam" name="naam" type="text" style="width: 95%" value="<?php echo esc_attr( $item['naam'] ); ?>"
					size="50" class="code" placeholder="De oven naam" required>
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
		<tr class="form-field">
			<th scope="row">
				<label>Beschikbaarheid></label>
			</th>
			<td>
				<input name="beschikbaarheid[]" value="zondag" type="checkbox" <?php echo array_search( 'zondag', $item['beschikbaarheid'], true ) !== false ? 'checked' : ''; ?> />Zondag
				<input name="beschikbaarheid[]" value="maandag" type="checkbox" <?php echo array_search( 'maandag', $item['beschikbaarheid'], true ) !== false ? 'checked' : ''; ?> />Maandag
				<input name="beschikbaarheid[]" value="dinsdag" type="checkbox" <?php echo array_search( 'dinsdag', $item['beschikbaarheid'], true ) !== false ? 'checked' : ''; ?> />Dinsdag
				<input name="beschikbaarheid[]" value="woensdag" type="checkbox" <?php echo array_search( 'woensdag', $item['beschikbaarheid'], true ) !== false ? 'checked' : ''; ?> />Woensdag
				<input name="beschikbaarheid[]" value="donderdag" type="checkbox" <?php echo array_search( 'donderdag', $item['beschikbaarheid'], true ) !== false ? 'checked' : ''; ?> />Donderdag
				<input name="beschikbaarheid[]" value="vrijdag" type="checkbox" <?php echo array_search( 'vrijdag', $item['beschikbaarheid'], true ) !== false ? 'checked' : ''; ?> />Vrijdag
				<input name="beschikbaarheid[]" value="zaterdag" type="checkbox" <?php echo array_search( 'zaterdag', $item['beschikbaarheid'], true ) !== false ? 'checked' : ''; ?> />Zaterdag
			</td>
		</tr>

	</tbody>
</table>

