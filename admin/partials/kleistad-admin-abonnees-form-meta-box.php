<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label>Naam</label>
			</th>
			<td>
				<?php echo esc_html( $item['naam'] ); ?> (<?php echo esc_html( $item['code'] ); ?>)
				<input type="hidden" name="naam" value="<?php echo esc_attr( $item['naam'] ); ?>" >
				<input type="hidden" name="code" value="<?php echo esc_attr( $item['code'] ); ?>" >
			</td>
		</tr>
		<tr>
			<th>Uitleg</th>
			<td>Dit formulier toont in de checkboxes de huidige status van de abonnee. 
				Bij het opslaan wordt gecontroleerd of er soms een checkbox gewijzigd is. Alleen dan wordt een actie uitgevoerd!
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="soort">Soort</label>
			</th>
			<td>
				<select id="soort" name="soort" required class="code" >
					<option value="onbeperkt" <?php selected( $item['soort'], 'onbeperkt' ); ?> >Onbeperkt</option>
					<option value="beperkt" <?php selected( $item['soort'], 'beperkt' ); ?> >Beperkt</option>
				</select>
				Let op: bij wijzigen soort wordt een eventuele automatische incasso gestopt!
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="dag">Dag</label>
			</th>
			<td>
				<select id="dag" name="dag" required class="code" >
					<option value="maandag" <?php selected( $item['dag'], 'maandag' ); ?> >Maandag</option>
					<option value="dinsdag" <?php selected( $item['dag'], 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $item['dag'], 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $item['dag'], 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $item['dag'], 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="gestart">Starten</label>
			</th>
			<td>
				<input type="checkbox" id="gestart" name="gestart" class="code" <?php checked( $item['gestart'] ); ?> value="1" 
						<?php disabled( $item['gepauzeerd'] ); ?> >
				Let op: bij starten wordt de uitgebreide welkomst email verstuurd!
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="geannuleerd">Annuleren</label>
			</th>
			<td>
				<input type="checkbox" id="geannuleerd" name="geannuleerd" class="code" <?php checked( $item['geannuleerd'] ); ?> value="1" 
						<?php disabled( $item['gepauzeerd'] ); ?> >
				Let op: bij annuleren wordt een eventuele automatische incasso gestopt!
			</td>
		</tr>
		<tr class="form-field">
			<th  scope="row">
				<label for="gepauzeerd">Pauzeren</label>
			</th>
			<td>
				<input type="checkbox" id="gepauzeerd" name="gepauzeerd" class="code" <?php checked( $item['gepauzeerd'] ); ?> value="1"
						<?php disabled( $item['geannuleerd'] ); ?> >
				Let op: bij pauzeren wordt een eventuele automatische incasso gestopt!
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="mandaat">Mandaat verwijderen</label>
			</th>
			<td>
				<input type="checkbox" id="mandaat" name="mandaat" class="code" <?php checked( $item['mandaat'] ); ?> value="1" 
						<?php disabled( $item['mandaat'], false ); ?> >
				Let op: bij verwijderen mandaat wordt een eventuele automatische incasso gestopt!
			</td>
		</tr>
		<tr>
			<th scope="row">
				&nbsp;
			</th>
			<td><table>
				<tr>
					<th>Inschrijving</th><th>Start</th><th>Pauze</th><th>Herstart</th><th>Eind</th><th>Incasso</th>
				</tr>
				<tr>
					<td>
						<?php echo esc_html( $item['inschrijf_datum'] ); ?>
						<input type="hidden" name="inschrijf_datum" value="<?php echo esc_attr( $item['inschrijf_datum'] ); ?>" >
					</td>
					<td>
						<?php echo esc_html( $item['start_datum'] ); ?>
						<input type="hidden" name="start_datum" value="<?php echo esc_attr( $item['start_datum'] ); ?>" >
					</td>
					<td>
						<?php echo esc_html( $item['pauze_datum'] ); ?>
						<input type="hidden" name="pauze_datum" value="<?php echo esc_attr( $item['pauze_datum'] ); ?>" >
					</td>
					<td>
						<?php echo esc_html( $item['herstart_datum'] ); ?>
						<input type="hidden" name="herstart_datum" value="<?php echo esc_attr( $item['herstart_datum'] ); ?>" >
					</td>
					<td>
						<?php echo esc_html( $item['eind_datum'] ); ?>
						<input type="hidden" name="eind_datum" value="<?php echo esc_attr( $item['eind_datum'] ); ?>" >
					</td>
					<td>
						<?php echo esc_html( $item['incasso_datum'] ); ?>
						<input type="hidden" name="incasso_datum" value="<?php echo esc_attr( $item['incasso_datum'] ); ?>" >
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</tbody>
</table>
