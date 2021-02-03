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

namespace Kleistad;

if ( false !== strpos( 'toevoegen, wijzigen', (string) $data['actie'] ) ) :
	$this->form();
	$readonly = $data['cursus']['eind_datum'] < strtotime( 'today' );
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
				<input type="text" name="naam" <?php readonly( $readonly ); ?> id="kleistad_cursus_naam" maxlength="40" placeholder="Bijv. cursus draaitechnieken" value="<?php echo esc_attr( $data['cursus']['naam'] ); ?>" required />
			</td>
		</tr>
		<tr>
			<th>
				Docent
			</th>
			<td colspan="3">
			<?php
			if ( $readonly ) :
				if ( is_numeric( $data['cursus']['docent'] ) ) :
					echo esc_html( get_user_by( 'id', $data['cursus']['docent'] )->display_name );
				else :
					echo esc_html( $data['cursus']['docent'] );
				endif;
			else :
				?>
				<select name="docent" id="kleistad_docent" required >
				<?php foreach ( $data['docenten'] as $docent ) : ?>
					<option value="<?php echo esc_attr( $docent->ID ); ?>" <?php selected( $docent->ID, $data['cursus']['docent'] ); ?> ><?php echo esc_html( $docent->display_name ); ?></option>
				<?php endforeach ?>
				</select>
			<?php endif ?>
			</td>
		</tr>
		<tr>
			<th>Start</th>
			<td>
				<input type="text" name="start_datum" id="kleistad_start_datum" class="kleistad_datum" required
					value="<?php echo esc_attr( date( 'd-m-Y', $data['cursus']['start_datum'] ) ); ?>"
					readonly="readonly" <?php disabled( $readonly ); ?> />
			</td>
			<th colspan="2"><div style="float:right"><input type="hidden" id="kleistad_lesdatum" class="kleistad_datum" <?php disabled( $readonly ); ?> /></div></th>
		</tr>
		<tr>
			<th>Eind</th>
			<td>
				<input type="text" name="eind_datum" id="kleistad_eind_datum" class="kleistad_datum" required
					value="<?php echo esc_attr( date( 'd-m-Y', $data['cursus']['eind_datum'] ) ); ?>"
					readonly="readonly" <?php disabled( $readonly ); ?> />
			</td>
			<td rowspan="3" colspan="2">
				<div style="width:100%;height:180px;margin:0;padding:0;overflow:auto;text-align:right;" id="kleistad_lesdatums_lijst" >
				</div>
			</td>
		</tr>
		<tr>
			<th>Begintijd</th>
			<td>
				<input type="text" name="start_tijd" id="kleistad_start_tijd" placeholder="00:00" class="kleistad_tijd"
					value="<?php echo esc_attr( date( 'H:i', $data['cursus']['start_tijd'] ) ); ?>" <?php readonly( $readonly ); ?> />
			</td>
		</tr>
		<tr>
			<th>Eindtijd</th>
			<td><input type="text" name="eind_tijd" id="kleistad_eind_tijd" placeholder="00:00" class="kleistad_tijd"
					value="<?php echo esc_attr( date( 'H:i', $data['cursus']['eind_tijd'] ) ); ?>" <?php readonly( $readonly ); ?> />
			</td>
		</tr>
		<tr>
			<th>Technieken</th>
			<td colspan="3" >
				<input type="checkbox" id="kleistad_draaien" name="technieken[]" value="Draaien" <?php checked( in_array( 'Draaien', $data['cursus']['technieken'], true ) ); ?> <?php disabled( $readonly ); ?> >
				<label for="kleistad_draaien" style="padding-right:2em">Draaien</label>
				<input type="checkbox" id="kleistad_handvormen" name="technieken[]" value="Handvormen" <?php checked( in_array( 'Handvormen', $data['cursus']['technieken'], true ) ); ?> <?php disabled( $readonly ); ?> >
				<label for="kleistad_handvormen" style="padding-right:2em">Handvormen</label>
				<input type="checkbox" id="kleistad_boetseren" name="technieken[]" value="Boetseren" <?php checked( in_array( 'Boetseren', $data['cursus']['technieken'], true ) ); ?> <?php disabled( $readonly ); ?> >
				<label for="kleistad_boetseren">Boetseren</label>
			</td>
		</tr>
		<tr>
			<th>Inschrijf kosten</th>
			<td><input type="number" lang="nl" step="0.01" name="inschrijfkosten" id="kleistad_inschrijfkosten" <?php readonly( $readonly ); ?> value="<?php echo esc_attr( $data['cursus']['inschrijfkosten'] ); ?>" min="0" required ></td>
			<th>Cursus kosten, excl. inschrijf kosten</th>
			<td><input type="number" lang="nl" step="0.01" name="cursuskosten" id="kleistad_cursuskosten" <?php readonly( $readonly ); ?> value="<?php echo esc_attr( $data['cursus']['cursuskosten'] ); ?>" min="0" required ></td>
		</tr>
		<tr>
			<th>Maximum cursisten</th>
			<td><input type="number" step="1" name="maximum" <?php readonly( $readonly ); ?> id="kleistad_maximum" min="1" max="99" value="<?php echo esc_attr( $data['cursus']['maximum'] ); ?>" required></td>
			<th>Inschrijven meerdere cursisten mogelijk</th>
			<td><input type="checkbox" name="meer" <?php disabled( $readonly ); ?> id="kleistad_meer" <?php checked( $data['cursus']['meer'] ); ?> ></td>
		</tr>
		<tr>
			<th>Publiceer de cursus</th>
			<td><input type="checkbox" name="tonen" <?php disabled( $readonly ); ?> id="kleistad_tonen" <?php checked( $data['cursus']['tonen'] ); ?> ></td>
			<th>Cursus vervallen</th>
			<td><input type="checkbox" name="vervallen" <?php disabled( $readonly ); ?> id="kleistad_vervallen" <?php checked( $data['cursus']['vervallen'] ); ?> ></td>
		</tr>
		<tr>
			<th>Inschrijf email</th>
			<td colspan="3"><input type="text" name="inschrijfslug" <?php readonly( $readonly ); ?> id="kleistad_inschrijfslug" value="<?php echo esc_attr( $data['cursus']['inschrijfslug'] ); ?>" required /></td>
		</tr>
		<tr>
			<th>Indeling email</th>
			<td colspan="3"><input type="text" name="indelingslug" <?php readonly( $readonly ); ?> id="kleistad_indelingslug" value="<?php echo esc_attr( $data['cursus']['indelingslug'] ); ?>" required /></td>
		</tr>
	</table>
	<button type="submit" id="kleistad_submit_cursus_bewaren" name="kleistad_submit_cursus_beheer" value="bewaren" <?php disabled( $readonly ); ?> >Opslaan</button>
	<button type="submit" id="kleistad_submit_cursus_verwijderen" name="kleistad_submit_cursus_beheer" value="verwijderen" <?php disabled( 'toevoegen' === $data['actie'] ); ?> >Verwijderen</button>
	<button type="button" style="float:right" class="kleistad_terug_link">Terug</button>
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
			<tr <?php echo $cursus['vervallen'] ? 'style="background-color:lightgray"' : ''; ?> >
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
<button type="button" class="kleistad_edit kleistad_edit_link" data-id="0" data-actie="toevoegen" >Toevoegen</button>
<?php endif ?>
