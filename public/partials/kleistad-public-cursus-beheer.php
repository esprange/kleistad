<?php
/**
 * Toon het cursus beheer overzicht en formulieren.
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

<div id="kleistad_cursus">
	<div id="kleistad_cursus_tabs">
		<ul>
			<li><a href="#kleistad_cursus_gegevens">Cursus informatie</a></li>
			<li><a href="#kleistad_cursus_indeling">Cursisten</a></li>
		</ul>
		<div id="kleistad_cursus_gegevens" >
			<form method="POST" id="kleistad_cursus_beheer_form" >
				<?php wp_nonce_field( 'kleistad_cursus_beheer' ); ?>
				<input type="hidden" name="cursus_id" value="0"/>
				<input type="hidden" name="tab" value="info"/>
				<table class="kleistad_form" >
					<tr>
						<th>Naam</th>
						<td colspan="3"><input type="text" name="naam" id="kleistad_cursus_naam" placeholder="Bijv. cursus draaitechnieken" required /></td>
					</tr>
					<tr>
						<th>Docent</th>
						<td colspan="3">
						<datalist id="kleistad_docenten">
						<?php foreach ( $data['docenten'] as $docent ) : ?>
							<option value="<?php echo esc_attr( $docent->display_name ); ?>">
						<?php endforeach ?>
						</datalist>
						<input type=text list="kleistad_docenten" name="docent" id="kleistad_docent" ></td>
					</tr>
					<tr>
						<th>Start</th>
						<td><input type="text" name="start_datum" id="kleistad_cursus_start_datum" class="kleistad_datum" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>" autocomplete="off" /></td>
						<th>Eind</th>
						<td><input type="text" name="eind_datum" id="kleistad_cursus_eind_datum" class="kleistad_datum" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  autocomplete="off" /></td>
					</tr>
					<tr>
						<th>Begintijd</th>
						<td><input type="text" name="start_tijd" id="kleistad_cursus_start_tijd" placeholder="00:00" class="kleistad_tijd" value="09:30" /></td>
						<th>Eindtijd</th>
						<td><input type="text" name="eind_tijd" id="kleistad_cursus_eind_tijd" placeholder="00:00" class="kleistad_tijd" value="12:00" /></td>
					</tr>
					<tr>
						<th>Technieken</th>
						<td><input type="checkbox" name="technieken[]" id="kleistad_draaien" value="Draaien">Draaien</td>
						<td><input type="checkbox" name="technieken[]" id="kleistad_handvormen" value="Handvormen">Handvormen</td>
						<td><input type="checkbox" name="technieken[]" id="kleistad_boetseren" value="Boetseren">Boetseren</td></tr>
					<tr>
						<th>Inschrijf kosten</th>
						<td><input type="number" lang="nl" step="0.01" name="inschrijfkosten" id="kleistad_inschrijfkosten" value="<?php echo esc_attr( $this->options['cursusinschrijfprijs'] ); ?>" min="0" required ></td>
						<th>Cursus kosten, excl. inschrijf kosten</th>
						<td><input type="number" lang="nl" step="0.01" name="cursuskosten" id="kleistad_cursuskosten" value="<?php echo esc_attr( $this->options['cursusprijs'] ); ?>" min="0" required ></td>
					</tr>
					<tr>
						<th>Cursus vol</th>
						<td><input type="checkbox" name="vol" id="kleistad_vol" ></td>
						<th>Cursus vervallen</th>
						<td><input type="checkbox" name="vervallen" id="kleistad_vervallen" ></td>
					</tr>
					<tr>
						<th>Maximum cursisten</th>
						<td><input type="number" step="1" name="maximum" id="kleistad_maximum" min="1" max="99" value="<?php echo esc_attr( $this->options['cursusmaximum'] ); ?>" required></td>
						<th>Inschrijven meerdere cursisten mogelijk</th>
						<td><input type="checkbox" name="meer" id="kleistad_meer" ></td>
					</tr>
					<tr>
						<th>Publiceer de cursus</th>
						<td><input type="checkbox" name="tonen" id="kleistad_tonen" ></td>
						<td colspan="2"></td>
					</tr>
					<tr>
						<th>Inschrijf email</th>
						<td colspan="3"><input type="text" name="inschrijfslug" id="kleistad_inschrijfslug" value="kleistad_email_cursus_aanvraag" required /></td>
					</tr>
					<tr>
						<th>Indeling email</th>
						<td colspan="3"><input type="text" name="indelingslug" id="kleistad_indelingslug" value="kleistad_email_cursus_ingedeeld" required /></td>
					</tr>
				</table>
				<button type="submit" name="kleistad_submit_cursus_beheer">Opslaan</button>
			</form>
		</div>

		<div id="kleistad_cursus_indeling" >
			<input type="hidden" name="tab" value="indeling"/>
			<table class="kleistad_form" id="kleistad_indeling" >
			</table>
			<form method="POST" style="display: none;" id="kleistad_restant_email" >
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'kleistad_cursus_beheer' ) ); ?>" />
					<?php wp_referer_field(); ?>
				<input type="hidden" name="cursus_id" value="0"/>
				<input type="hidden" name="tab" value="email"/>
				<p>Verstuur de email met betaalinstructie eenmalig naar cursisten die het restant cursusgeld nog niet betaald hebben.</p>
				<button type="submit" name="kleistad_submit_cursus_beheer" >Email versturen</button>
			</form>
		</div>

	</div>
</div>
<table id="kleistad_cursussen" class="kleistad_datatable display" data-page-length="10" data-order='[[ 0, "desc" ]]' >
	<thead>
		<tr>
			<th>Code</th>
			<th>Naam</th>
			<th>Docent</th>
			<th>Periode</th>
			<th>Tijd</th>
			<th>Technieken</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $data['rows'] as $row ) :
			$json_cursus = wp_json_encode( $row['cursus'] );
			?>
			<tr <?php echo $row['cursus']['vol'] ? 'style="background-color:lightblue"' : ( $row['cursus']['vervallen'] ? 'style="background-color:lightgray"' : '' ); ?>
				data-cursus='<?php echo $row['json_cursus'];  //phpcs:ignore ?>'
				data-ingedeeld='<?php echo $row['json_ingedeeld']; //phpcs:ignore ?>' >
				<td data-sort="<?php echo esc_attr( $row['cursus']['id'] ); ?>">C<?php echo esc_html( $row['cursus']['id'] ); ?></td>
				<td><?php echo esc_html( $row['cursus']['naam'] ); ?></td>
				<td><?php echo esc_html( $row['cursus']['docent'] ); ?></td>
				<td><?php echo esc_html( $row['cursus']['start_datum'] ); ?><br/><?php echo esc_html( $row['cursus']['eind_datum'] ); ?></td>
				<td><?php echo esc_html( $row['cursus']['start_tijd'] ); ?><br/><?php echo esc_html( $row['cursus']['eind_tijd'] ); ?></td>
				<td><?php echo implode( '<br/>' , $row['cursus']['technieken'] ); // phpcs:ignore ?></td>
				<td><?php echo esc_html( $row['cursus']['status'] ); ?></td>
			</tr>
	<?php endforeach ?>
	</tbody>
</table>
<button id="kleistad_cursus_toevoegen" >Toevoegen</button>
<?php endif ?>
