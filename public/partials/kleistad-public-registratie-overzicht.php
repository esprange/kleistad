<?php
/**
 * Toon het registratie overzicht
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! Kleistad_Roles::override() ) :
	?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

<div id="kleistad_deelnemer_info">
	<table class="kleistad_form" id="kleistad_deelnemer_tabel" >
	</table>
</div>
<p><label for="kleistad_deelnemer_selectie">Selectie</label>
	<select id="kleistad_deelnemer_selectie" name="selectie" >
		<option value="*" >&nbsp;</option>
		<option value="0" >Leden</option>
			<?php foreach ( $data['cursussen'] as $cursus ) : ?>
			<option value="<?php echo esc_attr( 'C' . $cursus->id ); ?>;">C<?php echo esc_html( $cursus->id . ' ' . $cursus->naam ); ?></option>
			<?php endforeach ?>

	</select>
</p>
<table class="kleistad_datatable display compact nowrap" id="kleistad_deelnemer_lijst">
	<thead>
		<tr>
			<th data-visible="false">Lid</th>
			<th data-visible="false">Cursuslijst</th>
			<th>Achternaam</th>
			<th>Voornaam</th>
			<th>Email</th>
			<th>Telnr</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $data['registraties'] as $registratie ) :
			$json_inschrijvingen = wp_json_encode( $registratie['inschrijvingen'] );
			$json_deelnemer      = wp_json_encode( $registratie['deelnemer_info'] );
			$json_abonnee        = wp_json_encode( $registratie['abonnee_info'] );
			if ( false === $json_inschrijvingen || false === $json_deelnemer || false === $json_abonnee ) :
				continue;
			endif;
			?>
			<tr data-inschrijvingen='<?php echo htmlspecialchars( $json_inschrijvingen, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore ?>'
				data-deelnemer='<?php echo htmlspecialchars( $json_deelnemer, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore ?>'
				data-abonnee='<?php echo htmlspecialchars( $json_abonnee, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore ?>' >
				<td><?php echo esc_html( $registratie['is_lid'] ); ?></td>
				<td><?php echo esc_html( $registratie['cursuslijst'] ); ?></td>
				<td><?php echo esc_html( $registratie['achternaam'] ); ?></td>
				<td><?php echo esc_html( $registratie['voornaam'] ); ?></td>
				<td><?php echo esc_html( $registratie['email'] ); ?></td>
				<td><?php echo esc_html( $registratie['telnr'] ); ?></td>
			</tr>
			<?php endforeach ?>
	</tbody>
</table>
<?php $this->form(); ?>
	<div class="kleistad_row" style="padding-top:20px;" >
		<button type="submit" name="kleistad_submit_registratie_overzicht" value="download_cursisten" >Download Cursisten</button>
		<button type="submit" name="kleistad_submit_registratie_overzicht" value="download_abonnees" >Download Abonnees</button>
	</div>
</form>
<?php endif; ?>
