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

if ( ! Kleistad_Roles::override() ) :
	?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

<div id="kleistad_cursisten_info">
	<?php $this->form( 'id="kleistad_cursisten_info_form"' ); ?>
		<input type="hidden" id="kleistad_submit_cursus_overzicht" name="kleistad_submit_cursus_overzicht" value="download_cursisten" >
		<input type="hidden" name="cursus_id" id="kleistad_cursus_id" >
		<input type="hidden" id="kleistad_email_lijst" value="">
		<table class="kleistad_form" id="kleistad_cursisten_lijst" >
		</table>
	</form>
</div>

<table id="kleistad_cursussen" class="kleistad_datatable display" data-order='[[ 0, "desc" ]]'>
	<thead>
		<tr>
			<th>Code</th>
			<th>Naam</th>
			<th>Docent</th>
			<th>Start</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $data['cursus_info'] as $cursus_id => $cursus_info ) :
		$json_cursus_info = wp_json_encode( $cursus_info['lijst'] );
		if ( false === $json_cursus_info ) :
			continue;
		endif;
		?>
		<tr data-naam='<?php echo esc_attr( $cursus_info['naam'] ); ?>'
			data-lijst='<?php echo htmlspecialchars( $json_cursus_info, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore ?>'
			data-id='<?php echo esc_attr( $cursus_id ); ?>' >
			<td data-sort="<?php echo esc_attr( $cursus_id ); ?>"><?php echo esc_html( $cursus_info['code'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['naam'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['docent'] ); ?></td>
			<td data-sort="<?php echo esc_attr( $cursus_info['start_dt'] ); ?>"><?php echo esc_html( $cursus_info['start_datum'] ); ?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>

<?php endif; ?>
