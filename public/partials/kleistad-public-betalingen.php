<?php
/**
 * Toon het betalingen overzicht
 *
 * @link https://www.kleistad.nl
 * @since4.0.87
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

if ( ! Kleistad_Roles::override() ) :
	?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>
<form action="#" method="post" >
	<?php wp_nonce_field( 'kleistad_betalingen' ); ?>
	<?php if ( isset( $data['inschrijvingen'] ) ) : ?>
	<h2>Inschrijvingen</h2>
	<table class="kleistad_rapport" id="kleistad_inschrijvingen" >
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
			<?php foreach ( $data['inschrijvingen'] as $inschrijving ) : ?>
				<tr style="<?php echo esc_attr( $inschrijving['geannuleerd'] ? 'color:grey' : '' ); ?>" >
					<td><?php echo esc_html( date( 'd-m-y', $inschrijving['datum'] ) ); ?></td>
					<td><?php echo esc_html( date( 'ymd', $inschrijving['datum'] ) ); ?></td>
					<td><?php echo esc_html( $inschrijving['code'] ); ?></td>
					<td><?php echo esc_html( $inschrijving['naam'] ); ?></td>
					<td><?php if ( $inschrijving['i_betaald'] ) : ?>
						<span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						<input type="checkbox" name="i_betaald[]" value="<?php echo esc_attr( $inschrijving['value'] ); ?>" >
						<?php endif ?>
					</td>
					<td><?php echo esc_html( $inschrijving['i_betaald'] ? '1' : '0' ); ?></td>
					<td><?php if ( $inschrijving['c_betaald'] ) : ?>
						<span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						<input type="checkbox" name="c_betaald[]" value="<?php echo esc_attr( $inschrijving['value'] ); ?>" >
						<?php endif ?>
					</td>
					<td><?php echo esc_html( $inschrijving['c_betaald'] ? '1' : '0' ); ?></td>
					<td><?php if ( $inschrijving['geannuleerd'] ) : ?>
						<span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						<input type="checkbox" name="geannuleerd[]" value="<?php echo esc_attr( $inschrijving['value'] ); ?>" >
						<?php endif ?>
					</td>
					<td><?php echo esc_html( $inschrijving['geannuleerd'] ? '1' : '0' ); ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<?php endif ?>
	<?php if ( isset( $data['workshops'] ) ) : ?>
	<h2>workshops</h2>
	<table class="kleistad_rapport"  id="kleistad_workshops" >
		<thead>
			<tr>
				<th>Datum</th>
				<th>datum sorted (1)</th>
				<th>Code</th>
				<th>Contact</th>
				<th>Organisatie</th>
				<th>Bedrag</th>
				<th>Betaald</th>
			</tr>
		</thead>
		<body>
		<?php foreach ( $data['workshops'] as $workshop ) : ?>
			<tr >
				<td><?php echo esc_html( date( 'd-m-y', $workshop['datum'] ) ); ?></td>
				<td><?php echo esc_html( date( 'ymd', $workshop['datum'] ) ); ?></td>
				<td><?php echo esc_html( $workshop['code'] ); ?></td>
				<td><?php echo esc_html( $workshop['contact'] ); ?></td>
				<td><?php echo esc_html( $workshop['organisatie'] ); ?></td>
				<td><?php echo esc_html( $workshop['kosten'] ); ?></td>
				<td><?php if ( $workshop['betaald'] ) : ?>
					<span class="genericon genericon-checkmark"></span>
					<?php else : ?>
					<input type="checkbox" name="e_betaald[]" value="<?php echo esc_attr( $workshop['id'] ); ?>" >
					<?php endif ?>
				</td>
			<?php endforeach ?>
		</tbody>
	</table>
	<?php endif ?>
	<button type="submit" name="kleistad_submit_betalingen" >Opslaan</button>
</form>

<?php endif ?>
