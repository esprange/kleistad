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

if ( false !== strpos( 'toevoegen, wijzigen', (string) $data['actie'] ) ) :
	$this->form();
	?>
	<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $data['cursus']['id'] ); ?>"/>
	<input type="hidden" name="lesdatums" id="kleistad_lesdatums" value="<?php echo esc_attr( $data['cursus']['lesdatums'] ); ?>" >
	<table class="kleistad_form" >
	<?php if ( ! empty( $data['cursus']['code'] ) ) : ?>
		<tr><th>Cursuscode</th><td colspan="3"><?php echo esc_html( $data['cursus']['code'] ); ?></td></tr>
	<?php endif ?>
		<tr>
			<th>Naam</th>
			<td colspan="3">
				<input type="text" name="naam" id="kleistad_cursus_naam" maxlenght="40" placeholder="Bijv. cursus draaitechnieken" value="<?php echo esc_attr( $data['cursus']['naam'] ); ?>" required />
			</td>
		</tr>
		<tr>
			<th>
				Docent
			</th>
			<td colspan="3">
				<datalist id="kleistad_docenten">
			<?php foreach ( $data['docenten'] as $docent ) : ?>
					<option value="<?php echo esc_attr( $docent->display_name ); ?>">
				<?php endforeach ?>
				</datalist>
				<input type=text list="kleistad_docenten" name="docent" id="kleistad_docent" value="<?php echo esc_attr( $data['cursus']['docent'] ); ?>" >
			</td>
		</tr>
		<tr>
			<th>Start</th>
			<td>
				<input type="text" name="start_datum" id="kleistad_start_datum" class="kleistad_datum" required
					value="<?php echo esc_attr( date( 'd-m-Y', $data['cursus']['start_datum'] ) ); ?>"
					readonly="readonly" />
			</td>
			<th></th>
			<th><input type="hidden" id="kleistad_lesdatum" class="kleistad_datum" /></th>
		</tr>
		<tr>
			<th>Eind</th>
			<td>
				<input type="text" name="eind_datum" id="kleistad_eind_datum" class="kleistad_datum" required
					value="<?php echo esc_attr( date( 'd-m-Y', $data['cursus']['eind_datum'] ) ); ?>"
					readonly="readonly" />
			</td>
			<td>
			</td>
			<td rowspan="3">
				<div style="width:100%;height:180px;margin:0;padding:0;overflow:auto;" id="kleistad_lesdatums_lijst" >
				</div>
			</td>
		</tr>
		<tr>
			<th>Begintijd</th>
			<td>
				<input type="text" name="start_tijd" id="kleistad_start_tijd" placeholder="00:00" class="kleistad_tijd"
					value="<?php echo esc_attr( date( 'H:i', $data['cursus']['start_tijd'] ) ); ?>" />
			</td>
			<td>
			</td>
		</tr>
		<tr>
			<th>Eindtijd</th>
			<td><input type="text" name="eind_tijd" id="kleistad_eind_tijd" placeholder="00:00" class="kleistad_tijd"
					value="<?php echo esc_attr( date( 'H:i', $data['cursus']['eind_tijd'] ) ); ?>" /></td>
			<td>
			</td>
		</tr>
		<tr>
			<th>Technieken</th>
			<td><input type="checkbox" name="technieken[]" id="kleistad_draaien" value="Draaien" <?php checked( in_array( 'Draaien', $data['cursus']['technieken'], true ) ); ?> >Draaien</td>
			<td><input type="checkbox" name="technieken[]" id="kleistad_handvormen" value="Handvormen" <?php checked( in_array( 'Handvormen', $data['cursus']['technieken'], true ) ); ?>>Handvormen</td>
			<td><input type="checkbox" name="technieken[]" id="kleistad_boetseren" value="Boetseren" <?php checked( in_array( 'Boetseren', $data['cursus']['technieken'], true ) ); ?> >Boetseren</td></tr>
		<tr>
			<th>Inschrijf kosten</th>
			<td><input type="number" lang="nl" step="0.01" name="inschrijfkosten" id="kleistad_inschrijfkosten" value="<?php echo esc_attr( $data['cursus']['inschrijfkosten'] ); ?>" min="0" required ></td>
			<th>Cursus kosten, excl. inschrijf kosten</th>
			<td><input type="number" lang="nl" step="0.01" name="cursuskosten" id="kleistad_cursuskosten" value="<?php echo esc_attr( $data['cursus']['cursuskosten'] ); ?>" min="0" required ></td>
		</tr>
		<tr>
			<th>Cursus vol</th>
			<td><input type="checkbox" name="vol" id="kleistad_vol" <?php checked( $data['cursus']['vol'] ); ?> ></td>
			<th>Cursus vervallen</th>
			<td><input type="checkbox" name="vervallen" id="kleistad_vervallen" <?php checked( $data['cursus']['vervallen'] ); ?> ></td>
		</tr>
		<tr>
			<th>Maximum cursisten</th>
			<td><input type="number" step="1" name="maximum" id="kleistad_maximum" min="1" max="99" value="<?php echo esc_attr( $data['cursus']['maximum'] ); ?>" required></td>
			<th>Inschrijven meerdere cursisten mogelijk</th>
			<td><input type="checkbox" name="meer" id="kleistad_meer" <?php checked( $data['cursus']['meer'] ); ?> ></td>
		</tr>
		<tr>
			<th>Publiceer de cursus</th>
			<td><input type="checkbox" name="tonen" id="kleistad_tonen" <?php checked( $data['cursus']['tonen'] ); ?> ></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<th>Inschrijf email</th>
			<td colspan="3"><input type="text" name="inschrijfslug" id="kleistad_inschrijfslug" value="<?php echo esc_attr( $data['cursus']['inschrijfslug'] ); ?>" required /></td>
		</tr>
		<tr>
			<th>Indeling email</th>
			<td colspan="3"><input type="text" name="indelingslug" id="kleistad_indelingslug" value="<?php echo esc_attr( $data['cursus']['indelingslug'] ); ?>" required /></td>
		</tr>
	</table>
	<button type="submit" name="kleistad_submit_cursus_beheer" value="bewaren" >Opslaan</button>
	<button type="submit" name="kleistad_submit_cursus_beheer" value="verwijderen" <?php disabled( 'toevoegen' === $data['actie'] ); ?> >Verwijderen</button>
	<button type="button" style="position:absolute;right:0px;" class="kleistad_terug_link">Terug</button>
</form>
<?php else : ?>
<table id="kleistad_cursussen" class="kleistad_datatable display compact nowrap" data-page-length="10" data-order='[[ 0, "desc" ]]' >
	<thead>
		<tr>
			<th>Code</th>
			<th>Naam</th>
			<th>Docent</th>
			<th>Periode</th>
			<th>Tijd</th>
			<th>Status</th>
			<th data-orderable="false"></th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $data['cursussen'] as $cursus ) :
			?>
			<tr <?php echo $cursus['vol'] ? 'style="background-color:lightblue"' : ( $cursus['vervallen'] ? 'style="background-color:lightgray"' : '' ); ?> >
				<td data-sort="<?php echo esc_attr( $cursus['id'] ); ?>">C<?php echo esc_html( $cursus['id'] ); ?></td>
				<td><?php echo esc_html( $cursus['naam'] ); ?></td>
				<td><?php echo esc_html( $cursus['docent'] ); ?></td>
				<td><?php echo esc_html( $cursus['start_datum'] ); ?><br/><?php echo esc_html( $cursus['eind_datum'] ); ?></td>
				<td><?php echo esc_html( $cursus['start_tijd'] ); ?><br/><?php echo esc_html( $cursus['eind_tijd'] ); ?></td>
				<td><?php echo esc_html( $cursus['status'] ); ?></td>
				<td>
					<a href="#" title="wijzig cursus" class="kleistad_edit kleistad_edit_link" style="text-decoration:none !important;color:green;padding:.4em .8em;"
						data-id="<?php echo esc_attr( $cursus['id'] ); ?>" data-actie="wijzigen" >
						&nbsp;
					</a>
				</td>
			</tr>
	<?php endforeach ?>
	</tbody>
</table>
<button class="kleistad_edit kleistad_edit_link" data-id="0" data-actie="toevoegen" >Toevoegen</button>
<?php endif ?>
