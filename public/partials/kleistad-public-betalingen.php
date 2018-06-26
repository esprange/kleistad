<?php
/**
 * Toon het betalingen overzicht
 *
 * @link https://www.kleistad.nl
 * @since4.0.87
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! Kleistad_Roles::override() ) :
	?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>
<form action="#" method="post" >
		<?php wp_nonce_field( 'kleistad_betalingen' ); ?>
	<table class="kleistad_rapport" >
		<thead>
			<tr>
				<th>Datum<br/>inschrijving</th>
				<th>datum sorted (1)</th>
				<th>Code</th>
				<th>Naam</th>
				<th>Inschrijfgeld<br/>betaald</th>
				<th>igeld sorted (5)</th>
				<th>Cursusgeld<br/>betaald</th>
				<th>cgeld sorted (7)</th>
				<th>Geannuleerd</th>
				<th>annul sorted (9)</th>
			</tr>
		</thead>
		<tbody>
				<?php foreach ( $data['rows'] as $row ) : ?>
				<tr style="<?php echo esc_attr( $row['geannuleerd'] ? 'color:grey' : '' ); ?>" >
					<td><?php echo esc_html( date( 'd-m-y', $row['datum'] ) ); ?></td>
					<td><?php echo esc_html( date( 'ymd', $row['datum'] ) ); ?></td>
					<td><?php echo esc_html( $row['code'] ); ?></td>
					<td><?php echo esc_html( $row['naam'] ); ?></td>
					<td><?php if ( $row['i_betaald'] ) : ?>
						<span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						<input type="checkbox" name="i_betaald[]" value="<?php echo esc_attr( $row['value'] ); ?>" >
						<?php endif ?>
					</td>
					<td><?php echo esc_html( $row['i_betaald'] ? 1 : 0 ); ?></td>
					<td><?php if ( $row['c_betaald'] ) : ?>
						<span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						<input type="checkbox" name="c_betaald[]" value="<?php echo esc_attr( $row['value'] ); ?>" >
						<?php endif ?>
					</td>
					<td><?php echo esc_html( $row['c_betaald'] ? 1 : 0 ); ?></td>
					<td><?php if ( $row['geannuleerd'] ) : ?>
						<span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						<input type="checkbox" name="geannuleerd[]" value="<?php echo esc_attr( $row['value'] ); ?>" >
						<?php endif ?>
					</td>
					<td><?php echo esc_html( $row['geannuleerd'] ? 1 : 0 ); ?></td>
				</tr>
				<?php endforeach ?>
		</tbody>
	</table>
	<button type="submit" name="kleistad_submit_betalingen" >Opslaan</button>
</form>

<?php endif ?>
