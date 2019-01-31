<?php
/**
 * Toon het cursus overzicht
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.4
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

if ( ! Kleistad_Roles::override() ) :
	?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

<div id="kleistad_cursisten_info">
	<form id="kleistad_download_cursisten" action="#" method="post" >
		<?php wp_nonce_field( 'kleistad_cursus_overzicht' ); ?>
		<input type="hidden" name="kleistad_submit_cursus_overzicht" >
		<input type="hidden" name="cursus_id" id="kleistad_cursus_id" >
		<input type="hidden" id="kleistad_email_lijst" value="">
		<table class="kleistad_form" id="kleistad_cursisten_lijst" >
		</table>
	</form>
</div>

<table class="kleistad_rapport" id="kleistad_cursus_lijst">
	<thead>
		<tr>
			<th>Id</th>
			<th>Start_dt</th>
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
		<tr class="kleistad_cursus_info"
			data-naam='<?php echo esc_attr( $cursus_info['naam'] ); ?>'
			data-lijst='<?php echo $json_cursus_info; // phpcs:ignore ?>'
			data-id='<?php echo esc_attr( $cursus_id ); ?>' >
			<td><?php echo esc_html( $cursus_id ); ?></td>
			<td><?php echo esc_html( $cursus_info['start_dt'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['code'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['naam'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['docent'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['start_datum'] ); ?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>

<?php endif; ?>
