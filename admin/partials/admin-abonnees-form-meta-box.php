<?php
/**
 * Tooe abonnee meta box
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<table style="width: 100%; border-spacing:2px; padding:5px" class="form-table">
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label>Naam</label>
			</th>
			<td>
				<?php echo esc_html( $item['naam'] ); ?> (<?php echo esc_html( $item['code'] ); ?>)
				<input type="hidden" name="naam" value="<?php echo esc_attr( $item['naam'] ); ?>" >
				<input type="hidden" name="code" value="<?php echo esc_attr( $item['code'] ); ?>" >
				<input type="hidden" name="gestart" value="<?php echo esc_attr( $item['gestart'] ); ?>" >
				<input type="hidden" name="gepauzeerd" value="<?php echo esc_attr( $item['gepauzeerd'] ); ?>" >
				<input type="hidden" name="geannuleerd" value="<?php echo esc_attr( $item['geannuleerd'] ); ?>" >
				<input type="hidden" name="actie" value="<?php echo esc_attr( $actie ); ?>" >
			</td>
		</tr>
<?php
if ( 'soort' === $actie ) :
	?>
		<tr class="form-field">
			<th scope="row">
				<label for="soort">Soort</label>
			</th>
			<td>
				<select id="kleistad-soort" name="soort" required class="code">
					<option value="">Selecteer een abonnement soort</option>
					<option value="onbeperkt" <?php selected( $item['soort'], 'onbeperkt' ); ?> >Onbeperkt</option>
					<option value="beperkt" <?php selected( $item['soort'], 'beperkt' ); ?> >Beperkt</option>
				</select>
				Let op: bij wijzigen soort wordt een eventuele automatische incasso gestopt!
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="dag">Dag</label>
			</th>
			<td>
				<select id="kleistad-dag" name="dag" <?php echo ( 'beperkt' === $item['soort'] ? 'required' : '' ); ?> class="code" >
					<option value="">Selecteer een dag</option>
					<option value="maandag" <?php selected( $item['dag'], 'maandag' ); ?>>Maandag</option>
					<option value="dinsdag" <?php selected( $item['dag'], 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $item['dag'], 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $item['dag'], 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $item['dag'], 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<?php submit_button(); ?>
			</td>
		</tr>
	<?php
elseif ( 'extras' === $actie ) :
	$options = \Kleistad\Kleistad::get_options();
	$i       = 1;
	foreach ( $options['extra'] as $extra ) :
		if ( 0 < $extra['prijs'] ) :
			?>
	<tr class="form-field">
			<th scope="row">
				<label for="extra_<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $extra['naam'] ); ?></label>
			</th>
			<td>
				<input type="checkbox" id="extra_<?php echo esc_attr( $i ); ?>" name="extras[]" class="code" <?php checked( false !== array_search( $extra['naam'], $item['extras'], true ) ); ?>
					value="<?php echo esc_attr( $extra['naam'] ); ?>" >
			</td>
		</tr>
			<?php
		endif;
	endforeach;
	?>
		<tr>
			<td>
				<?php submit_button(); ?>
			</td>
		</tr>
	<?php
	elseif ( 'status' === $actie ) :
		if ( ! $item['geannuleerd'] && ! $item['gestart'] ) :
			?>
		<tr class="form-field">
			<td>
				<?php submit_button( 'Starten', 'primary', 'submit', true, [ 'id' => 'starten' ] ); ?>
			</td>
			<td>
				Let op: bij starten wordt de uitgebreide welkomst email verstuurd!
			</td>
		</tr>
			<?php
		endif;
		if ( ! $item['geannuleerd'] ) :
			?>
		<tr class="form-field">
			<td>
				<?php submit_button( 'Stoppen', 'primary', 'submit', true, [ 'id' => 'stoppen' ] ); ?>
			</td>
		</tr>
			<?php
		endif;
		if ( ! $item['geannuleerd'] && $item['gestart'] && ! $item['gepauzeerd'] ) :
			?>
		<tr class="form-field">
			<td>
				<?php submit_button( 'Pauzeren', 'primary', 'submit', true, [ 'id' => 'pauzeren' ] ); ?>
			</td>
		</tr>
		<?php endif ?>
		<tr>
			<th scope="row">
				&nbsp;
			</th>
			<td><table>
				<tr>
					<th>Inschrijving</th><th>Start</th><th>Pauze</th><th>Herstart</th><th>Eind</th>
				</tr>
				<tr>
					<td>
						<input type="text" name="inschrijf_datum" value="<?php echo esc_attr( $item['inschrijf_datum'] ); ?>"
							readonly >
					</td>
					<td>
						<input type="text" name="start_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['start_datum'] ); ?>" autocomplete="off"
							<?php readonly( $item['geannuleerd'] || $item['gestart'] ); ?> >
					</td>
					<td>
						<input type="text" name="pauze_datum" class="kleistad_datum maand" value="<?php echo esc_attr( $item['pauze_datum'] ); ?>" autocomplete="off"
							<?php readonly( $item['geannuleerd'] || ! $item['gestart'] || $item['gepauzeerd'] ); ?> >
					</td>
					<td>
						<input type="text" name="herstart_datum" class="kleistad_datum maand" value="<?php echo esc_attr( $item['herstart_datum'] ); ?>" autocomplete="off"
							<?php readonly( $item['geannuleerd'] || ! $item['gestart'] ); ?> >
					</td>
					<td>
						<input type="text" name="eind_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['eind_datum'] ); ?>" autocomplete="off"
							<?php readonly( $item['geannuleerd'] ); ?> >
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<?php
		elseif ( 'mollie' === $actie ) :
			?>
		<tr class="form-field">
			<td>
				<?php submit_button( 'verwijder mandaat' ); ?>
			</td>
		</tr>
		<tr>
			<td colspan="2">Let op: bij verwijderen mandaat wordt een eventuele automatische incasso gestopt!</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php echo esc_html( $item['mollie_info'] ?? '' ); ?>
			</td>
		</tr>
			<?php
endif
		?>
	</tbody>
</table>
