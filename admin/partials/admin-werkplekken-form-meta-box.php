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

/**
 * Maak een lijst van mogelijke werkplaats meesters. Dit zijn bestuursleden, docenten of abonnees.
 *
 * @param string $name        Het name van de select box.
 * @param int    $id_selected Het id als er een gebruiker geselecteerd is.
 */
function meester_selectie( string $name, int $id_selected ) : string {
	static $meesters = null;
	if ( is_null( $meesters ) ) {
		$meesters = get_users(
			[
				'fields'   => [ 'display_name', 'ID' ],
				'orderby'  => 'display_name',
				'role__in' => [ LID, DOCENT, BESTUUR ],
			]
		);
	}
	$select = "<select name=\"$name\" style=\"width:100%;\" ><option value=\"0\" ></option>";
	foreach ( $meesters as $meester ) {
		$selected = selected( intval( $meester->ID ), $id_selected, false );
		$select  .= "<option value=\"$meester->ID\" $selected >$meester->display_name</option>";
	}
	$select .= '<\select>';
	return $select;
}

?>
<table style="width: 100%;border-spacing: 2px; padding: 5px" > <!--class="form-table"-->
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label for="start_datum">Start datum</label>
			</th>
			<td colspan="2">
				<input type="text" id="kleistad_start_datum" name="start_datum" class="kleistad_datum" required value="<?php echo esc_attr( $item['start_datum'] ); ?>" autocomplete="off" >
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="eind_datum">Eind datum</label>
			</th>
			<td colspan="2">
				<input type="text" id="kleistad_eind_datum" name="eind_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['eind_datum'] ); ?>" <?php disabled( $item['config_eind'] ); ?> autocomplete="off" >
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
		<tr>
			<td>Meester</td>
			<?php foreach ( array_keys( $item['config'] ) as $atelierdag ) : ?>
			<td><?php echo meester_selectie( "meesters[$atelierdag][$dagdeel]", $item['meesters'][ $atelierdag ][ $dagdeel ] ?? 0 );  //phpcs:ignore ?></td>
			<?php endforeach ?>
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

