<?php
/**
 * Toon de regelingen meta box
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

namespace Kleistad;

?>
<table style="width: 100%;border-spacing: 2px; padding: 5px" > <!--class="form-table"-->
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label for="start_datum">Start datum</label>
			</th>
			<td colspan="2">
				<input type="hidden" name="nieuwste_config" value="<?php echo esc_attr( intval( $item['nieuwste_config'] ) ); ?>">
				<input type="text" id="kleistad_start_datum" name="start_datum" class="kleistad_datum" required value="<?php echo esc_attr( $item['start_datum'] ); ?>" >
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="eind_datum">Eind datum</label>
			</th>
			<td colspan="2">
				<input type="text" id="kleistad_eind_datum" name="eind_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['eind_datum'] ); ?>" <?php disabled( $item['nieuwste_config'] ); ?> >
			</td>
		</tr>
		<tr><td></td>
		<?php foreach ( array_keys( $item['config'] ) as $atelierdag ) : ?>
			<th scope="column"><label><?php echo esc_html( $atelierdag ); ?></label></th>
		<?php endforeach ?>
		</tr>
		<?php foreach ( WerkplekConfig::DAGDEEL as $dagdeel ) : ?>
		<tr>
			<th><?php echo esc_html( $dagdeel ); ?></th>
		</tr>
			<?php foreach ( WerkplekConfig::ACTIVITEIT as $activiteit ) : ?>
			<tr>
				<td><?php echo esc_html( $activiteit ); ?></td>
				<?php foreach ( array_keys( $item['config'] ) as $atelierdag ) : ?>
					<td><input type="text" size="4"
						value="<?php echo esc_attr( $item['config'][ $atelierdag ][ $dagdeel ][ $activiteit ] ); ?>" 
						name="<?php echo esc_attr( "config[$atelierdag][$dagdeel][$activiteit]" ); ?>" ></td>
				<?php endforeach ?>
			</tr>
			<?php endforeach ?>
		<?php endforeach ?>
	</tbody>
</table>

