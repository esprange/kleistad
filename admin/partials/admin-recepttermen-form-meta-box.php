<?php
/**
 * Toon de recept termen meta box
 *
 * @link       https://www.kleistad.nl
 * @since      6.4.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

namespace Kleistad;

$hoofdterm = (array) get_term( intval( filter_input( INPUT_GET, 'hoofdterm_id', FILTER_SANITIZE_NUMBER_INT ) ) );

?>
<h2><?php echo esc_html( $hoofdterm['description'] ); ?></h2>
<input name="hoofdterm_id" type="hidden" value="<?php echo esc_attr( $hoofdterm['term_id'] ); ?>" >
<table class="form-table">
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label for="naam">Naam</label>
			</th>
			<td>
				<input id="naam" name="naam" type="text" style="width: 95%" value="<?php echo esc_attr( $item['naam'] ); ?>"
					size="50" class="code" placeholder="De recept term naam" required>
			</td>
		</tr>
	</tbody>
</table>

