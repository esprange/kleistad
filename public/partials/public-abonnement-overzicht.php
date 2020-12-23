<?php
/**
 * Toon het abonnement overzicht
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.6
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

namespace Kleistad;

?>
<div id="kleistad_abonnees_info">
	<table class="kleistad_datatable display compact nowrap" data-order='[[ 0, "asc" ]]'>
		<thead>
			<tr>
				<th>Naam</th>
				<th>E-mail</th>
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
				<td><?php echo esc_html( $abonnee_info['soort'] ); ?></td>
				<td><?php echo $abonnee_info['extras']; // phpcs:ignore ?></td>
				<td><?php echo esc_html( $abonnee_info['status'] ); ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
	<button type="button" class="kleistad_download_link" data-actie="abonnementen" >Download</button>
</div>
