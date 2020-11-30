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

namespace Kleistad;

?>

<div style="position:relative;top:0;">
<?php if ( count( $item['historie'] ) ) : ?>
	<div class="card" style="width:50%;position:absolute;top:0px;right:0px" >
	<ul style="list-style-type:square">
	<?php foreach ( $item['historie'] as $historie ) : ?>
		<li><?php echo esc_html( $historie ); ?></li>
	<?php endforeach ?>
	</ul>
	</div>
<?php endif ?>
<table style="border-spacing:2px; padding:5px;" class="form-table">
	<tbody>
		<tr class="form-field">
			<th  scope="row">
				<label>Naam</label>
			</th>
			<td>
				<?php echo esc_html( $item['naam'] ); ?> (<?php echo esc_html( $item['code'] ); ?>)
				<input type="hidden" name="naam" value="<?php echo esc_attr( $item['naam'] ); ?>" >
				<input type="hidden" name="code" value="<?php echo esc_attr( $item['code'] ); ?>" >
				<input type="hidden" name="gepauzeerd" value="<?php echo esc_attr( $item['gepauzeerd'] ); ?>" >
				<input type="hidden" name="geannuleerd" value="<?php echo esc_attr( $item['geannuleerd'] ); ?>" >
				<input type="hidden" name="actie" value="<?php echo esc_attr( $actie ); ?>" >
			</td>
		</tr>
		<?php if ( 'status' === $actie ) : ?>
		<tr class="form-field">
			<th scope="row">
				<label for="kleistad_soort">Soort</label>
			</th>
			<td>
				<select id="kleistad_soort" name="soort" class="code">
					<option value="">Selecteer een abonnement soort</option>
					<option value="onbeperkt" <?php selected( $item['soort'], 'onbeperkt' ); ?> >Onbeperkt</option>
					<option value="beperkt" <?php selected( $item['soort'], 'beperkt' ); ?> >Beperkt</option>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="kleistad_dag">Dag</label>
			</th>
			<td>
				<select id="kleistad_dag" name="dag" class="code" >
					<option value="">Selecteer een dag</option>
					<option value="maandag" <?php selected( $item['dag'], 'maandag' ); ?>>Maandag</option>
					<option value="dinsdag" <?php selected( $item['dag'], 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $item['dag'], 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $item['dag'], 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $item['dag'], 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</td>
		</tr>
			<?php
			$options = Kleistad::get_options();
			$i       = 0;
			foreach ( $options['extra'] as $extra ) :
				$i++;
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
			<th scope="row">
				<label for="kleistad_inschrijf_datum">Inschrijving per</label>
			</th>
			<td>
				<input type="text" id="kleistad_inschrijf_datum" name="inschrijf_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['inschrijf_datum'] ); ?>" readonly >
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="kleistad_start_datum">Startperiode</label>
			</th>
			<td>
				<input type="text" id="kleistad_start_datum" name="start_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['start_datum'] ); ?>" autocomplete="off"
					<?php readonly( $item['geannuleerd'] ); ?> >
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="kleistad_start_eind_datum">Einde startperiode</label>
			</th>
			<td>
				<input type="text" id="kleistad_start_eind_datum" name="start_eind_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['start_eind_datum'] ); ?>" autocomplete="off"
					<?php readonly( $item['geannuleerd'] ); ?> >
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="kleistad_pauze_datum">Pauze per</label>
			</th>
			<td>
				<input type="text" id="kleistad_pauze_datum" name="pauze_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['pauze_datum'] ); ?>" autocomplete="off"
					<?php readonly( $item['geannuleerd'] ); ?> >
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="kleistad_herstart_datum">Herstart per</label>
			</th>
			<td>
				<input type="text" id="kleistad_herstart_datum" name="herstart_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['herstart_datum'] ); ?>" autocomplete="off"
					<?php readonly( $item['geannuleerd'] ); ?> >
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="kleistad_eind_datum">Beëindiging per</label>
			</th>
			<td>
				<input type="text" id="kleistad_eind_datum" name="eind_datum" class="kleistad_datum" value="<?php echo esc_attr( $item['eind_datum'] ); ?>" autocomplete="off"
					<?php readonly( $item['geannuleerd'] ); ?> >
			</td>
		</tr>
			<?php
		elseif ( 'mollie' === $actie ) :
			?>
		<tr class="form-field">
			<td>
				<?php submit_button( 'Verwijder mandaat', 'primary', 'submit', true, [ 'id' => 'mollie' ] ); ?>
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
</div>
