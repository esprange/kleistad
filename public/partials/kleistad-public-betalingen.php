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
	$this->form();
	if ( isset( $data['inschrijvingen'] ) ) :
	?>
	<h2>Inschrijvingen</h2>
	<table class="kleistad_datatable display nowrap">
		<thead>
			<tr>
				<th>Datum<br/>inschrijving</th>
				<th>Code</th>
				<th>Naam</th>
				<th data-class-name="dt-body-center">Inschrijving<br/>betaald</th>
				<th data-class-name="dt-body-center">Cursus<br/>betaald</th>
				<th data-class-name="dt-body-center">Geannuleerd</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $data['inschrijvingen'] as $inschrijving ) : ?>
				<tr style="<?php echo esc_attr( $inschrijving['geannuleerd'] ? 'color:grey' : '' ); ?>" >
					<td data-sort="<?php echo esc_attr( $inschrijving['datum'] ); ?>"><?php echo esc_html( date( 'd-m-y', $inschrijving['datum'] ) ); ?></td>
					<td><?php echo esc_html( $inschrijving['code'] ); ?></td>
					<td><?php echo esc_html( $inschrijving['naam'] ); ?></td>
					<td data-sort="<?php echo $inschrijving['i_betaald'] ? 1 : 0; ?>"><?php if ( $inschrijving['i_betaald'] ) : ?>
						<span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						<input type="checkbox" name="<?php echo "cursist[{$inschrijving['cursist_id']}][i_betaald][{$inschrijving['cursus_id']}]"; //phpcs:ignore ?>" value="1" >
						<?php endif ?>
					</td>
					<td data-sort="<?php echo $inschrijving['c_betaald'] ? 1 : 0; ?>"><?php if ( $inschrijving['c_betaald'] ) : ?>
						<span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						<input type="checkbox" name="<?php echo "cursist[{$inschrijving['cursist_id']}][c_betaald][{$inschrijving['cursus_id']}]"; //phpcs:ignore ?>" value="1" >
						<?php endif ?>
					</td>
					<td data-sort="<?php echo $inschrijving['geannuleerd'] ? 1 : 0; ?>"><?php if ( $inschrijving['geannuleerd'] ) : ?>
						<span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						<input type="checkbox" name="<?php echo "cursist[{$inschrijving['cursist_id']}][geannuleerd][{$inschrijving['cursus_id']}]"; //phpcs:ignore ?>" value="1" >
						<?php endif ?>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<?php endif ?>
	<?php if ( isset( $data['workshops'] ) ) : ?>
	<h2>workshops</h2>
	<table class="kleistad_datatable display nowrap">
		<thead>
			<tr>
				<th>Datum</th>
				<th>Code</th>
				<th>Contact</th>
				<th>Organisatie</th>
				<th data-class-name="dt-body-right">Bedrag</th>
				<th data-class-name="dt-body-center">Betaald</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $data['workshops'] as $workshop ) : ?>
			<tr >
				<td data-sort="<?php echo esc_attr( $workshop['datum'] ); ?>"><?php echo esc_html( date( 'd-m-y', $workshop['datum'] ) ); ?></td>
				<td data-sort="<?php echo esc_attr( $workshop['id'] ); ?>"><?php echo esc_html( $workshop['code'] ); ?></td>
				<td><?php echo esc_html( $workshop['contact'] ); ?></td>
				<td><?php echo esc_html( $workshop['organisatie'] ); ?></td>
				<td><?php echo esc_html( $workshop['kosten'] ); ?></td>
				<td data-sort="<?php echo $workshop['betaald'] ? 1 : 0; ?>"><?php if ( $workshop['betaald'] ) : ?>
					<span class="genericon genericon-checkmark"></span>
					<?php else : ?>
					<input type="checkbox" name="<?php echo "workshop[{$workshop['id']}]"; //phpcs:ignore ?>" value="1" >
					<?php endif ?>
				</td>
			<?php endforeach ?>
		</tbody>
	</table>
	<?php endif ?>
	<button type="submit" name="kleistad_submit_betalingen" >Opslaan</button>
</form>

<?php endif ?>
