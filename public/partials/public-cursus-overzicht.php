<?php
/**
 * Toon het cursus overzicht
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.4
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! \Kleistad\Roles::override() ) :
	?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	global $wp;
	if ( 'cursisten' === $data['actie'] ) :
		?>

<div id="kleistad_cursisten_info">
		<?php $this->form(); ?>
		<strong><?php echo esc_html( $data['cursus']['code'] . ' ' . $data['cursus']['naam'] ); ?></strong>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $data['cursus']['cursus_id'] ); ?>">
		<table class="kleistad_datatable display" data-paging="false" data-searching="false">
			<thead>
			<tr>
				<th>Naam</th>
				<th>Telefoon</th>
				<th>Email</th>
				<th>Technieken</th>
				<th>Betaald</th>
				<th>Restant Email</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $data['cursisten'] as $cursist ) : ?>
				<tr>
					<td><?php echo esc_html( $cursist['naam'] ); ?></td>
					<td><?php echo esc_html( $cursist['telnr'] ); ?></td>
					<td><?php echo esc_html( $cursist['email'] ); ?></td>
					<td><?php echo esc_html( $cursist['technieken'] ); ?></td>
					<td><?php echo ( ( $cursist['c_betaald'] ) ? '<span class="dashicons dashicons-yes"></span>' : '' ); ?></td>
					<td><?php echo ( ( $cursist['restant_email'] ) ? '<span class="dashicons dashicons-yes"></span>' : '' ); ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<br/>
		<button type="submit" name="kleistad_submit_cursus_overzicht" value="download_cursisten" >Download</button>
		<button type="submit" name="kleistad_submit_cursus_overzicht" value="restant_email" >Restant email versturen</button>
		<button type="button" style="position:absolute;right:0px;" class="kleistad_terug_link">Terug</button>
	</form>
</div>
<?php else : ?>
<table id="kleistad_cursussen" class="kleistad_datatable display" data-order='[[ 0, "desc" ]]'>
	<thead>
		<tr>
			<th>Code</th>
			<th>Naam</th>
			<th>Docent</th>
			<th>Start</th>
			<th data-orderable="false"></th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $data['cursus_info'] as $cursus_id => $cursus_info ) :
		?>
		<tr>
			<td data-sort="<?php echo esc_attr( $cursus_id ); ?>"><?php echo esc_html( $cursus_info['code'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['naam'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['docent'] ); ?></td>
			<td data-sort="<?php echo esc_attr( $cursus_info['start_dt'] ); ?>"><?php echo esc_html( $cursus_info['start_datum'] ); ?></td>
			<td>
				<?php if ( $cursus_info['inschrijvingen'] ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( '', 'kleistad_toon_cursisten_' . $cursus_id ) . '&actie=cursisten&id=' . $cursus_id ); ?>"
					title="toon cursisten" class="kleistad_view_link" style="text-decoration:none !important;color:green;padding:.4em .8em;"
					data-id="<?php echo esc_attr( $cursus_id ); ?>" data-actie="cursisten" >
					&nbsp;
				</a>
				<?php endif ?>
			</td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>

<?php endif;
endif; ?>
