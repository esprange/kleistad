<?php
/**
 * Toon het abonnement overzicht
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.6
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

<div id="kleistad_abonnees_info">
	<form id="kleistad_download_abonnees" action="#" method="post" >
		<?php wp_nonce_field( 'kleistad_abonnement_overzicht' ); ?>
		<input type="hidden" name="kleistad_submit_abonnement_overzicht" >
		<input type="hidden" id="kleistad_email_lijst" value="<?php echo $data['email_lijst']; // phpcs:ignore ?>">
		<table class="kleistad_rapport" id="kleistad_abonnement_lijst">
			<thead>
				<tr>
					<th>Naam</th>
					<th>E-mail</th>
					<th>Telefoon</th>
					<th>Soort</th>
					<th>Extras</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $data['abonnee_info'] as $abonnee_info ) : ?>
				<tr class="kleistad_abonnee_info" >
					<td><?php echo esc_html( $abonnee_info['naam'] ); ?></td>
					<td><?php echo esc_html( $abonnee_info['email'] ); ?></td>
					<td><?php echo esc_html( $abonnee_info['telnr'] ); ?></td>
					<td><?php echo esc_html( $abonnee_info['soort'] ); ?></td>
					<td><?php echo $abonnee_info['extras']; // phpcs:ignore ?></td>
					<td><?php echo esc_html( $abonnee_info['status'] ); ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button type="button" id="kleistad_klembord">Kopie naar klembord</button>
		<button type="submit" name="kleistad_submit_abonnement_overzicht">Download</button>
	</form>
</div>

<?php endif; ?>
