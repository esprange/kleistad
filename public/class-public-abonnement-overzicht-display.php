<?php
/**
 * Toon het abonnement overzicht formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de abonnement overzicht.
 */
class Public_Abonnement_Overzicht_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		?>
		<table class="kleistad-datatable display compact nowrap" data-order='[[ 0, "asc" ]]'>
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
			<?php foreach ( $this->data['abonnee_info'] as $abonnee_info ) : ?>
				<tr>
					<td><?php echo esc_html( $abonnee_info['naam'] ); ?></td>
					<td><?php echo esc_html( $abonnee_info['email'] ); ?></td>
					<td><?php echo esc_html( $abonnee_info['soort'] ); ?></td>
					<td><?php echo $abonnee_info['extras']; // phpcs:ignore ?></td>
					<td><?php echo esc_html( $abonnee_info['status'] ); ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button class="kleistad-button kleistad-download-link" type="button" data-actie="abonnementen" >Download</button>
		<?php
	}
}
