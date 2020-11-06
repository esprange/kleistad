<?php
/**
 * Toon de oven meta box
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<table class="form-table">
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
				<label for="kosten_laag">Laag tarief</label>
			</th>
			<td>
				<input id="kosten_laag" name="kosten_laag" type="number" value="<?php echo esc_attr( $item['kosten_laag'] ); ?>"
					step="0.01" class="code" placeholder="99.99" required />
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="kosten_midden">Midden tarief</label>
			</th>
			<td>
				<input id="kosten_midden" name="kosten_midden" type="number" value="<?php echo esc_attr( $item['kosten_midden'] ); ?>"
					step="0.01" class="code" placeholder="99.99" required>
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="kosten_hoog">Hoog tarief</label>
			</th>
			<td>
				<input id="kosten_hoog" name="kosten_hoog" type="number" value="<?php echo esc_attr( $item['kosten_hoog'] ); ?>"
					step="0.01" class="code" placeholder="99.99" required>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label>Beschikbaarheid</label>
			</th>
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

