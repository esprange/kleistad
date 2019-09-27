<?php
/**
 * Toon het workshops overzicht
 *
 * @link https://www.kleistad.nl
 * @since4.0.87
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! \Kleistad\Roles::override() ) :
	?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	global $wp;
	if ( false !== strpos( 'toevoegen, wijzigen, inplannen', (string) $data['actie'] ) ) :
		$voltooid     = strtotime( $data['workshop']['datum'] ) < strtotime( 'today' );
		$alleen_lezen = $data['workshop']['betaald'] || $data['workshop']['vervallen'] || $voltooid;
		$this->form();
		?>

		<input type="hidden" name="workshop_id" value="<?php echo esc_attr( $data['workshop']['workshop_id'] ); ?>"/>
		<input type="hidden" name="aanvraag_id" value="<?php echo esc_attr( $data['workshop']['aanvraag_id'] ); ?>"/>
		<input type="hidden" name="vervallen" value="<?php echo $data['workshop']['vervallen'] ? 1 : 0; ?>" >
		<table class="kleistad_form" >
			<tr>
				<th>Soort workshop</th>
				<td colspan="3">
					<select name="naam" required id="kleistad_naam" <?php readonly( $alleen_lezen ); ?> >
						<option value="kinderfeest" <?php selected( $data['workshop']['naam'], 'kinderfeest' ); ?> >kinderfeest</option>
						<option value="workshop"  <?php selected( $data['workshop']['naam'], 'workshop' ); ?>>workshop</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Naam contact</th>
				<td colspan="3"><input type="text" name="contact" required value="<?php echo esc_attr( $data['workshop']['contact'] ); ?>" <?php readonly( $alleen_lezen ); ?> /></td>
			</tr>
			<tr>
				<th>Email contact</th>
				<td colspan="3"><input type="email" name="email" required value="<?php echo esc_attr( $data['workshop']['email'] ); ?>" <?php readonly( $alleen_lezen ); ?> /></td>
			</tr>
			<tr>
				<th>Telefoon contact</th>
				<td colspan="3"><input type="text" name="telnr" value="<?php echo esc_attr( $data['workshop']['telnr'] ); ?>" <?php readonly( $alleen_lezen ); ?> /></td>
			</tr>
			<tr>
				<th>Organisatie</th>
				<td colspan="3"><input type="text" name="organisatie" value="<?php echo esc_attr( $data['workshop']['organisatie'] ); ?>" <?php readonly( $alleen_lezen ); ?> /></td>
			</tr>
			<tr>
				<th>Aantal deelnemers</th>
				<td><input type="number" name="aantal" id="kleistad_aantal" min="1" value="<?php echo esc_attr( $data['workshop']['aantal'] ); ?>" <?php readonly( $alleen_lezen ); ?> /></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<th>Datum</th>
				<td><input type="text" name="datum" id="kleistad_datum" class="kleistad_datum" required value="<?php echo esc_attr( $data['workshop']['datum'] ); ?>" readonly="readonly" /></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<th>Begintijd</th>
				<td><input type="text" name="start_tijd" id="kleistad_start_tijd" placeholder="00:00" value="<?php echo esc_attr( $data['workshop']['start_tijd'] ); ?>" class="kleistad_tijd" required <?php readonly( $alleen_lezen ); ?> /></td>
				<th>Eindtijd</th>
				<td><input type="text" name="eind_tijd" id="kleistad_eind_tijd" placeholder="00:00" value="<?php echo esc_attr( $data['workshop']['eind_tijd'] ); ?>" class="kleistad_tijd" required <?php readonly( $alleen_lezen ); ?> /></td>
			</tr>
			<tr>
				<th>Docent</th>
				<td colspan="3">
				<datalist id="kleistad_docenten">
				<?php foreach ( $data['docenten'] as $docent ) : ?>
					<option value="<?php echo esc_attr( $docent->display_name ); ?>">
				<?php endforeach ?>
				</datalist>
				<input type=text list="kleistad_docenten" name="docent" value="<?php echo esc_attr( $data['workshop']['docent'] ); ?>" <?php readonly( $alleen_lezen ); ?> ></td>
			</tr>
			<tr>
				<th>Technieken</th>
				<td><input type="checkbox" name="technieken[]" value="Draaien" <?php checked( in_array( 'Draaien', $data['workshop']['technieken'], true ) ); ?> <?php readonly( $alleen_lezen ); ?> >Draaien</td>
				<td><input type="checkbox" name="technieken[]" value="Handvormen" <?php checked( in_array( 'Handvormen', $data['workshop']['technieken'], true ) ); ?> <?php readonly( $alleen_lezen ); ?> >Handvormen</td>
				<td><input type="checkbox" name="technieken[]" value="Boetseren" <?php checked( in_array( 'Boetseren', $data['workshop']['technieken'], true ) ); ?> <?php readonly( $alleen_lezen ); ?> >Boetseren</td>
			</tr>
			<tr>
				<th>Programma</th>
				<td colspan="3"><textarea name="programma" id="kleistad_programma" rows="2" maxlength="500" <?php readonly( $alleen_lezen ); ?> ><?php echo esc_textarea( $data['workshop']['programma'] ); ?></textarea>
			</tr>
			<tr>
				<th>Kosten</th>
				<td><input type="number" lang="nl" step="0.01" name="kosten" id="kleistad_kosten" min="0" value="<?php echo esc_attr( $data['workshop']['kosten'] ); ?>" <?php readonly( $alleen_lezen ); ?> > incl. BTW</td>
				<td><input type="number" lang="nl" step="0.01" id="kleistad_kosten_ex_btw" min="0" value="<?php echo esc_attr( number_format( $data['workshop']['kosten'] / 1.21, 2 ) ); ?>" <?php readonly( $alleen_lezen ); ?> > excl. BTW</td>
				<td colspan="1"></td>
			</tr>
			<tr>
				<th>Afspraak definitief</th>
				<td><input type="hidden" name="definitief" value="<?php echo $data['workshop']['definitief'] ? 1 : 0; ?>" ><?php echo $data['workshop']['definitief'] ? '&#10004;' : '&#10060;'; ?></td>
				<th>Betaald</th>
				<td><input type="hidden" name="betaald" value="<?php echo $data['workshop']['betaald'] ? 1 : 0; ?>" ><?php echo $data['workshop']['betaald'] ? '&#10004;' : '&#10060;'; ?></td>
			</tr>
		</table>
		<button type="submit" name="kleistad_submit_workshop_beheer" value="bewaren" <?php disabled( $alleen_lezen || $data['workshop']['definitief'] ); ?> >Opslaan</button>
		<button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_bevestigen" value="bevestigen"
			data-confirm="Workshop beheer|weet je zeker dat je nu de bevesting wilt versturen" >Bevestigen</button>
		<button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_afzeggen" value="afzeggen" <?php disabled( $voltooid || ! $data['workshop']['definitief'] || 'toevoegen' === $data['actie'] ); ?>
			data-confirm="Workshop beheer|weet je zeker dat je de workshop wilt afzeggen" >Afzeggen</button>
		<button type="submit" name="kleistad_submit_workshop_beheer" value="verwijderen" <?php disabled( $data['workshop']['definitief'] || 'toevoegen' === $data['actie'] ); ?> >Verwijderen</button>
		<button type="button" style="position:absolute;right:0px;" class="kleistad_terug_link">Terug</button>
	</form>
		<?php
	elseif ( false !== strpos( 'tonen', (string) $data['actie'] ) ) :
		$this->form();
		?>
		<input type="hidden" name="casus_id" value="<?php echo esc_attr( $data['casus']['casus_id'] ); ?>"/>
		<table class="kleistad_form" >
			<tr>
				<th>Soort workshop</th>
				<td><?php echo esc_html( $data['casus']['naam'] ); ?></td>
			</tr>
			<tr>
				<th>Contact</th>
				<td><?php echo esc_html( $data['casus']['contact'] ); ?></td>
			</tr>
			<tr>
				<td><?php echo esc_html( $data['casus']['email'] ); ?></td>
				<td><?php echo esc_html( $data['casus']['telnr'] ); ?></td>
			</tr>
			<tr>
				<th>Omvang</th>
				<td><?php echo esc_html( $data['casus']['omvang'] ); ?></td>
			</tr>
			<tr>
				<th>Periode</th>
				<td><?php echo esc_html( $data['casus']['periode'] ); ?></td>
			</tr>
			<tr>
				<td colspan="2" ><label for="kleistad_reactie">Reactie</label></td>
			</tr>
			<tr>
				<td colspan="2" ><textarea id="kleistad_reactie" name="reactie" rows="10" required ></textarea></td>
			</tr>
		</table>
		<button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_reageren" value="reageren" >Reageren</button>
		<button type="button" style="position:absolute;right:0px;" class="kleistad_terug_link">Terug</button>
	</form>
	<div>
		<?php foreach ( $data['casus']['correspondentie'] as $correspondentie ) : ?>
		<div class="kleistad_workshop_correspondentie  \Kleistad\Workshop_<?php echo esc_attr( $correspondentie['type'] ); ?>  \Kleistad\Workshop_compact" >
			<strong><?php echo esc_html( ucfirst( $correspondentie['type'] ) . ' van ' . $correspondentie['from'] . ' op ' . $correspondentie['tijd'] ); ?></strong>
			<p><?php echo esc_html( $correspondentie['subject'] ); ?></p>
			<?php echo nl2br( $correspondentie['tekst'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p style="text-align:center;">
			<span class="kleistad_workshop_unfold">
				<a class="kleistad_workshop_unfold" href="#">Uitklappen</a>
			</span>
			<span class="kleistad_workshop_fold">
				<a class="kleistad_workshop_fold" href="#">Inklappen</a>
			</span>
			</p>
		</div>
	<?php endforeach ?>
	</div>
	<?php else : ?>
	<strong>Vraag en Antwoord</strong>
	<table id="kleistad_aanvragen" class="kleistad_datatable display compact nowrap" data-page-length="10" data-order='[[ 0, "desc" ]]' >
		<thead>
			<tr>
				<th>Datum</th>
				<th>Beschrijving</th>
				<th>Status</th>
				<th data-orderable="false"></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $data['aanvragen'] as $aanvraag ) :
				?>
			<tr>
				<td data-sort="<?php echo esc_attr( $aanvraag['datum_ux'] ); ?>"><?php echo esc_html( $aanvraag['datum'] ); ?></td>
				<td><?php echo esc_html( $aanvraag['titel'] ); ?></td>
				<td><?php echo esc_html( $aanvraag['status'] ); ?></td>
				<td>
					<a href="<?php echo esc_url( wp_nonce_url( '', 'kleistad_toon_aanvraag_' . $aanvraag['id'] ) . '&actie=tonen&id=' . $aanvraag['id'] ); ?>"
						title="toon_aanvraag" class="kleistad_edit_link" style="text-decoration:none !important;color:green;padding:.4em .8em;" >
						&nbsp;
					</a>&nbsp;&nbsp;
					<a href="<?php echo esc_url( wp_nonce_url( '', 'kleistad_plan_workshop_' . $aanvraag['id'] ) . '&actie=inplannen&id=' . $aanvraag['id'] ); ?>"
						title="plan_workshop" class="kleistad_schedule_link" style="text-decoration:none !important;color:blue;padding:.4em .8em;" >
						&nbsp;
					</a>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<br/>
	<strong>Plannen</strong>
	<table id="kleistad_workshops" class="kleistad_datatable display compact nowrap" data-page-length="10" data-order='[[ 1, "desc" ]]' >
		<thead>
			<tr>
				<th>Code</th>
				<th>Datum</th>
				<th>Titel</th>
				<th>Docent</th>
				<th>Aantal</th>
				<th>Tijd</th>
				<th>Status</th>
				<th data-orderable="false"></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $data['workshops'] as $workshop ) :
				?>
			<tr>
				<td><?php echo esc_html( $workshop['code'] ); ?></td>
				<td data-sort="<?php echo esc_attr( $workshop['datum_ux'] ); ?>"><?php echo esc_html( $workshop['datum'] ); ?></td>
				<td><?php echo esc_html( $workshop['naam'] ); ?></td>
				<td><?php echo esc_html( $workshop['docent'] ); ?></td>
				<td><?php echo esc_html( $workshop['aantal'] ); ?></td>
				<td><?php echo esc_html( $workshop['start_tijd'] ); ?><br/><?php echo esc_html( $workshop['eind_tijd'] ); ?></td>
				<td><?php echo esc_html( $workshop['status'] ); ?></td>
				<td>
					<a href="<?php echo esc_url( wp_nonce_url( '', 'kleistad_wijzig_workshop_' . $workshop['id'] ) . '&actie=wijzigen&id=' . $workshop['id'] ); ?>"
						title="wijzig workshop" class="kleistad_edit_link" style="text-decoration:none !important;color:green;padding:.4em .8em;" >
						&nbsp;
					</a>
				</td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
		<?php $this->form(); ?>
		<button type="button" class="kleistad_edit_link" data-id="0" data-actie="toevoegen" >Toevoegen</button>
		<button type="submit" name="kleistad_submit_workshop_beheer" value="download_workshops" >Download</button>
	</form>
		<?php
	endif;
endif
?>
