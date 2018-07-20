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

