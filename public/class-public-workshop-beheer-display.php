<?php
/**
 * Toon het workshop beheer formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de workshop beheer formulier.
 */
class Public_Workshop_Beheer_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		if ( false !== strpos( 'toevoegen, wijzigen, inplannen', (string) $this->data['actie'] ) ) {
			$this->form()->edit()->form_end();
			return;
		} elseif ( false !== strpos( 'tonen', (string) $this->data['actie'] ) ) {
			$this->form()->communicatie()->form_end();
			return;
		}
		$this->overzicht();
	}

	/**
	 * Render het formulier
	 *
	 * @return Public_Workshop_Beheer_Display
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function edit() : Public_Workshop_Beheer_Display {
		$voltooid = strtotime( $this->data['workshop']['datum'] ) < strtotime( 'today' );
		$readonly = $this->data['workshop']['betaald'] || $this->data['workshop']['vervallen'] || $voltooid;
		?>
		<input type="hidden" name="workshop_id" value="<?php echo esc_attr( $this->data['workshop']['workshop_id'] ); ?>"/>
		<input type="hidden" name="aanvraag_id" value="<?php echo esc_attr( $this->data['workshop']['aanvraag_id'] ); ?>"/>
		<input type="hidden" name="vervallen" value="<?php echo (int) $this->data['workshop']['vervallen']; ?>" >
		<table class="kleistad-form" >
			<tr>
				<th>Soort workshop</th>
				<td colspan="3">
					<select name="naam" required id="kleistad_naam" <?php readonly( $readonly ); ?> >
						<option value="kinderfeest" <?php selected( $this->data['workshop']['naam'], 'kinderfeest' ); ?> >kinderfeest</option>
						<option value="workshop"  <?php selected( $this->data['workshop']['naam'], 'workshop' ); ?>>workshop</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Naam contact</th>
				<td colspan="3"><input type="text" name="contact" required value="<?php echo esc_attr( $this->data['workshop']['contact'] ); ?>" <?php readonly( $readonly ); ?> /></td>
			</tr>
			<tr>
				<th>Email contact</th>
				<td colspan="3"><input type="email" name="email" required value="<?php echo esc_attr( $this->data['workshop']['email'] ); ?>" <?php readonly( $readonly ); ?> /></td>
			</tr>
			<tr>
				<th>Telefoon contact</th>
				<td colspan="3"><input type="text" name="telnr" value="<?php echo esc_attr( $this->data['workshop']['telnr'] ); ?>" <?php readonly( $readonly ); ?> /></td>
			</tr>
			<tr>
				<th>Organisatie</th>
				<td colspan="3"><input type="text" name="organisatie" value="<?php echo esc_attr( $this->data['workshop']['organisatie'] ); ?>" </td>
			</tr>
			<tr>
				<th>Organisatie adres</th>
				<td colspan="3"><textarea name="organisatie_adres" rows="2" maxlength="100" ><?php echo esc_textarea( $this->data['workshop']['organisatie_adres'] ); ?></textarea></td>
			</tr>
			<tr>
				<th>Organisatie email</th>
				<td colspan="3"><input type="email" name="organisatie_email" value="<?php echo esc_attr( $this->data['workshop']['organisatie_email'] ); ?>" /></td>
			</tr>
			<tr>
				<th>Aantal deelnemers</th>
				<td><input type="number" name="aantal" id="kleistad_aantal" min="1" value="<?php echo esc_attr( $this->data['workshop']['aantal'] ); ?>" <?php readonly( $readonly ); ?> /></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<th>Datum</th>
				<td><input type="text" name="datum" id="kleistad-datum" class="kleistad-datum" required value="<?php echo esc_attr( $this->data['workshop']['datum'] ); ?>" readonly="readonly" /></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<th>Begintijd</th>
				<td><input type="text" name="start_tijd" id="kleistad_start_tijd" placeholder="00:00" value="<?php echo esc_attr( $this->data['workshop']['start_tijd'] ); ?>" class="kleistad-tijd" required <?php readonly( $readonly ); ?> /></td>
				<th>Eindtijd</th>
				<td><input type="text" name="eind_tijd" id="kleistad_eind_tijd" placeholder="00:00" value="<?php echo esc_attr( $this->data['workshop']['eind_tijd'] ); ?>" class="kleistad-tijd" required <?php readonly( $readonly ); ?> /></td>
			</tr>
			<tr>
				<th>Docent</th>
				<td colspan="3">
				<datalist id="kleistad_docenten">
				<?php foreach ( $this->data['docenten'] as $docent ) : ?>
					<option value="<?php echo esc_attr( $docent->display_name ); ?>">
				<?php endforeach ?>
				</datalist>
				<input type=text list="kleistad_docenten" name="docent" value="<?php echo esc_attr( $this->data['workshop']['docent'] ); ?>" <?php readonly( $readonly ); ?> ></td>
			</tr>
			<tr>
				<th>Technieken</th>
				<td colspan="3">
					<input type="checkbox" id="kleistad_draaien" name="technieken[]" value="Draaien" <?php checked( in_array( 'Draaien', $this->data['workshop']['technieken'], true ) ); ?> <?php disabled( $readonly ); ?> >
					<label for="kleistad_draaien" style="padding-right:2em">Draaien</label>
					<input type="checkbox" id="kleistad_handvormen" name="technieken[]" value="Handvormen" <?php checked( in_array( 'Handvormen', $this->data['workshop']['technieken'], true ) ); ?> <?php disabled( $readonly ); ?> >
					<label for="kleistad_handvormen" style="padding-right:2em">Handvormen</label>
					<input type="checkbox" id="kleistad_boetseren" name="technieken[]" value="Boetseren" <?php checked( in_array( 'Boetseren', $this->data['workshop']['technieken'], true ) ); ?> <?php disabled( $readonly ); ?> >
					<label for="kleistad_boetseren">Boetseren</label>
				</td>
			</tr>
			<tr>
				<th>Programma</th>
				<td colspan="3"><textarea name="programma" id="kleistad_programma" rows="5" maxlength="500" <?php readonly( $readonly ); ?> ><?php echo esc_textarea( $this->data['workshop']['programma'] ); ?></textarea>
			</tr>
			<tr>
				<th>Kosten</th>
				<td><input type="number" lang="nl" step="0.01" name="kosten" id="kleistad_kosten" min="0" value="<?php echo esc_attr( $this->data['workshop']['kosten'] ); ?>" <?php readonly( $readonly ); ?> > incl. BTW</td>
				<td><input type="number" lang="nl" step="0.01" id="kleistad_kosten_ex_btw" min="0" value="<?php echo esc_attr( number_format( $this->data['workshop']['kosten'] / 1.21, 2 ) ); ?>" <?php readonly( $readonly ); ?> > excl. BTW</td>
				<td colspan="1"></td>
			</tr>
			<tr>
				<th>
					<input type="hidden" name="definitief" value="<?php echo (int) $this->data['workshop']['definitief']; ?>" >
					<?php if ( $this->data['workshop']['vervallen'] ) : ?>
						<span style="color:red" >Afspraak is vervallen</span>
					<?php else : ?>
						Afspraak definitief &nbsp;&nbsp;<?php echo $this->data['workshop']['definitief'] ? '&#10004;' : '&#10060;'; ?>
					<?php endif ?>
				</th>
				<th>Gefactureerd &nbsp;&nbsp;<?php echo $this->data['workshop']['gefactureerd'] ? '&#10004;' : '&#10060;'; ?></th>
				<th>Betaald &nbsp;&nbsp;<input type="hidden" name="betaald" value="<?php echo (int) $this->data['workshop']['betaald']; ?>" ><?php echo $this->data['workshop']['betaald'] ? '&#10004;' : '&#10060;'; ?></th>
			</tr>
		</table>
		<button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_bewaren" value="bewaren" <?php disabled( $readonly || $this->data['workshop']['definitief'] ); ?> >Opslaan</button>
		<button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_bevestigen" value="bevestigen" <?php disabled( $this->data['workshop']['vervallen'] ); ?>
			data-confirm="Workshop beheer|weet je zeker dat je nu de bevesting wilt versturen" >Bevestigen</button>
		<button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_afzeggen" value="afzeggen" <?php disabled( $readonly || 'toevoegen' === $this->data['actie'] || $this->data['workshop']['gefactureerd'] ); ?>
			data-confirm="Workshop beheer|weet je zeker dat je de workshop wilt afzeggen" >Afzeggen</button>
		<button type="button" style="float:right;" class="kleistad-terug-link">Terug</button>
		<?php
		return $this;
	}

	/**
	 * Toon het overzicht van workshops
	 *
	 * @return Public_Workshop_Beheer_Display
	 */
	private function overzicht() : Public_Workshop_Beheer_Display {
		?>
		<strong>Vraag en Antwoord</strong>
		<table id="kleistad_aanvragen" class="kleistad-datatable display compact nowrap" data-page-length="10" data-order='[[ 0, "desc" ]]' >
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
				foreach ( $this->data['aanvragen'] as $aanvraag ) :
					?>
				<tr>
					<td data-sort="<?php echo esc_attr( $aanvraag['datum_ux'] ); ?>"><?php echo esc_html( $aanvraag['datum'] ); ?></td>
					<td><?php echo esc_html( $aanvraag['titel'] ); ?></td>
					<td><?php echo esc_html( $aanvraag['status'] ); ?></td>
					<td>
						<a href="#" data-id="<?php echo esc_attr( $aanvraag['id'] ); ?>" data-actie="tonen"
							title="toon_aanvraag" class="kleistad-edit kleistad-edit-link" >
							&nbsp;
						</a>&nbsp;&nbsp;
						<a href="#" data-id="<?php echo esc_attr( $aanvraag['id'] ); ?>" data-actie="inplannen"
							title="plan_workshop" class="kleistad-schedule kleistad-edit-link" >
							&nbsp;
						</a>
					</td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		<br/>
		<strong>Plannen</strong>
		<table id="kleistad_workshops" class="kleistad-datatable display compact nowrap" data-page-length="10" data-order='[[ 1, "desc" ]]' >
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
				foreach ( $this->data['workshops'] as $workshop ) :
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
						<a href="#" data-id="<?php echo esc_attr( $workshop['id'] ); ?>" data-actie="wijzigen"
							title="wijzig workshop" class="kleistad-edit kleistad-edit-link" >
							&nbsp;
						</a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button type="button" class="kleistad-edit kleistad-edit-link" data-id="0" data-actie="toevoegen" >Toevoegen</button>
		<button type="button" class="kleistad-download-link" data-actie="workshops" >Download</button>
		<?php
		return $this;
	}

	/**
	 * Toon het overzicht van de communicatie rondom de workshop
	 *
	 * @return Public_Workshop_Beheer_Display
	 */
	private function communicatie() : Public_Workshop_Beheer_Display {
		?>
		<input type="hidden" name="casus_id" value="<?php echo esc_attr( $this->data['casus']['casus_id'] ); ?>"/>
		<table class="kleistad-form" >
			<tr>
				<th>Soort workshop</th>
				<td><?php echo esc_html( $this->data['casus']['naam'] ); ?></td>
			</tr>
			<tr>
				<th>Contact</th>
				<td><?php echo esc_html( $this->data['casus']['contact'] ); ?></td>
			</tr>
			<tr>
				<td><?php echo esc_html( $this->data['casus']['email'] ); ?></td>
				<td><?php echo esc_html( $this->data['casus']['telnr'] ); ?></td>
			</tr>
			<tr>
				<th>Omvang</th>
				<td><?php echo esc_html( $this->data['casus']['omvang'] ); ?></td>
			</tr>
			<tr>
				<th>Periode</th>
				<td><?php echo esc_html( $this->data['casus']['periode'] ); ?></td>
			</tr>
			<tr>
				<td colspan="2" ><label for="kleistad_reactie">Reactie</label></td>
			</tr>
			<tr>
				<td colspan="2" ><textarea id="kleistad_reactie" name="reactie" maxlength="1000" rows="10" required ></textarea></td>
			</tr>
		</table>
		<button type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_reageren" value="reageren" >Reageren</button>
		<button type="button" style="float:right" class="kleistad-terug-link">Terug</button>
		<div>
		<?php foreach ( $this->data['casus']['correspondentie'] as $correspondentie ) : ?>
			<div class="kleistad-workshop-correspondentie kleistad-workshop-correspondentie-folded kleistad-workshop-<?php echo esc_attr( $correspondentie['type'] ); ?>" >
				<strong><?php echo esc_html( ucfirst( $correspondentie['type'] ) . ' van ' . $correspondentie['from'] . ' op ' . $correspondentie['tijd'] ); ?></strong>
				<p><?php echo esc_html( $correspondentie['subject'] ); ?></p>
				<?php echo nl2br( $correspondentie['tekst'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<br/>
			</div>
			<div style="text-align:center;">
				<button class="kleistad-workshop-unfold" >Uitklappen</button>
				<button class="kleistad-workshop-fold" style="display:none;" >Inklappen</button>
			</div>
		<?php endforeach ?>
		<?php
		return $this;
	}

}
