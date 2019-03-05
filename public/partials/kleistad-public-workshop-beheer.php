<?php
/**
 * Toon het workshops overzicht
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

<div id="kleistad_workshop">
	<form method="POST" id="kleistad_workshop_form">
		<?php wp_nonce_field( 'kleistad_workshop_beheer' ); ?>
		<input type="hidden" name="id" id="kleistad_id" value="0"/>
		<table class="kleistad_form" >
			<tr>
				<th>Soort workshop</th>
				<td colspan="3">
					<select name="naam" id="kleistad_naam">
						<option value="kinderfeest">kinderfeest</option>
						<option value="workshop">workshop</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Naam contact</th>
				<td colspan="3"><input type="text" name="contact" required id="kleistad_contact" /></td>
			</tr>
			<tr>
				<th>Email contact</th>
				<td colspan="3"><input type="email" name="email" required id="kleistad_email" /></td>
			</tr>
			<tr>
				<th>Telefoon contact</th>
				<td colspan="3"><input type="text" name="telefoon" id="kleistad_telefoon" /></td>
			</tr>
			<tr>
				<th>Organisatie</th>
				<td colspan="3"><input type="text" name="organisatie" id="kleistad_organisatie" /></td>
			</tr>
			<tr>
				<th>Aantal deelnemers</th>
				<td><input type="number" name="aantal" id="kleistad_aantal" min="1" value="1"></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<th>Datum</th>
				<td><input type="text" name="datum" id="kleistad_datum" class="kleistad_datum" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>" autocomplete="off" /></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<th>Begintijd</th>
				<td><input type="text" name="start_tijd" id="kleistad_start_tijd" placeholder="00:00" value="10:00" class="kleistad_tijd" required /></td>
				<th>Eindtijd</th>
				<td><input type="text" name="eind_tijd" id="kleistad_eind_tijd" placeholder="00:00" value="12:00" class="kleistad_tijd" required /></td>
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
				<th>Technieken</th>
				<td><input type="checkbox" name="technieken[]" id="kleistad_draaien" value="Draaien">Draaien</td>
				<td><input type="checkbox" name="technieken[]" id="kleistad_handvormen" value="Handvormen">Handvormen</td>
				<td><input type="checkbox" name="technieken[]" id="kleistad_boetseren" value="Boetseren">Boetseren</td>
			</tr>
			<tr>
				<th>Programma</th>
				<td colspan="3"><textarea name="programma" id="kleistad_programma" rows="2" maxlength="500" ></textarea>
			</tr>
			<tr>
				<th>Kosten</th>
				<td><input type="number" lang="nl" step="0.01" name="kosten" id="kleistad_kosten" min="0" value=<?php echo esc_attr( $this->options['workshopprijs'] ); ?> ></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<th>Afspraak definitief</th>
				<td><span id="kleistad_definitief"></span></td>
				<th>Betaald</th>
				<td><span id="kleistad_betaald"></span></td>
			</tr>
			<tr>
				<td><button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_opslaan" value="opslaan" >Opslaan</button></td>
				<td><button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_bevestigen" value="bevestigen"
					onclick="return confirm( 'weet je zeker dat je nu de bevesting wilt versturen' )" >Bevestigen</button></td>
				<td><button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_afzeggen" value="afzeggen"
					onclick="return confirm( 'weet je zeker dat je de workshop wilt afzeggen' )" >Afzeggen</button></td>
				<td><button type="button" id="kleistad_sluit">Sluiten</button></td>
			</tr>
		</table>
	</form>
</div>

<table id="kleistad_workshops" class="kleistad_datatable display compact nowrap" data-page-length="10" data-order='[[ 1, "desc" ]]' >
	<thead>
		<tr>
			<th>Code</th>
			<th>Datum</th>
			<th>Titel</th>
			<th>Aantal</th>
			<th>Tijd</th>
			<th>Technieken</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $data['workshop'] as $workshop ) :
			$json_workshop = wp_json_encode( $workshop );
			if ( false === $json_workshop ) :
				continue;
			endif;
			?>
		<tr class="kleistad_workshop_info"
			data-workshop='<?php echo htmlspecialchars( $json_workshop, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore ?>' >
			<td><?php echo esc_html( $workshop['code'] ); ?></td>
			<td data-sort="<?php echo esc_attr( $workshop['datum_ux'] ); ?>"><?php echo esc_html( $workshop['datum'] ); ?></td>
			<td><?php echo esc_html( $workshop['naam'] ); ?></td>
			<td><?php echo esc_html( $workshop['aantal'] ); ?></td>
			<td><?php echo esc_html( $workshop['start_tijd'] . ' - ' . $workshop['eind_tijd'] ); ?></td>
			<td><?php echo esc_html( implode( ', ', $workshop['technieken'] ) ); ?></td>
			<td><?php echo esc_html( $workshop['status'] ); ?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>
<button id="kleistad_workshop_toevoegen" >Toevoegen</button>

<?php endif ?>
