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
	 */
	protected function toevoegen() {
		$this->form();
	}

	/**
	 * Render het formulier
	 */
	protected function wijzigen() {
		$this->form();
	}

	/**
	 * Render het formulier
	 */
	protected function inplannen() {
		$this->form();
	}

	/**
	 * Render het formulier
	 */
	protected function tonen() {
		$this->form( 'form_communicatie' );
	}

	/**
	 * Toon het overzicht van workshops
	 */
	protected function overzicht() {
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
					<td data-sort="<?php echo esc_attr( $aanvraag['datum'] ); ?>"><?php echo esc_html( strftime( '%d-%m-%Y %H:%M', $aanvraag['datum'] ) ); ?></td>
					<td><?php echo esc_html( $aanvraag['titel'] ); ?></td>
					<td><?php echo esc_html( $aanvraag['status'] ); ?></td>
					<td>
						<a href="#" data-id="<?php echo esc_attr( $aanvraag['id'] ); ?>" data-actie="tonen" title="toon_aanvraag" class="kleistad-edit kleistad-edit-link" >
							&nbsp;
						</a>&nbsp;&nbsp;
						<a href="#" data-id="<?php echo esc_attr( $aanvraag['id'] ); ?>" data-actie="inplannen" title="plan_workshop" class="kleistad-schedule kleistad-edit-link" >
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
				<th>Contact</th>
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
					<td data-sort="<?php echo esc_attr( substr( $workshop['code'], 1 ) ); ?>"><?php echo esc_html( $workshop['code'] ); ?></td>
					<td data-sort="<?php echo esc_attr( $workshop['datum_ux'] ); ?>"><?php echo esc_html( $workshop['datum'] ); ?></td>
					<td><?php echo esc_html( $workshop['contact'] ); ?></td>
					<td><?php echo esc_html( $workshop['docent'] ); ?></td>
					<td><?php echo esc_html( $workshop['aantal'] ); ?></td>
					<td><?php echo esc_html( $workshop['start_tijd'] ); ?><br/><?php echo esc_html( $workshop['eind_tijd'] ); ?></td>
					<td><?php echo esc_html( $workshop['status'] ); ?></td>
					<td>
						<a href="#" data-id="<?php echo esc_attr( $workshop['id'] ); ?>" data-actie="wijzigen" title="wijzig workshop" class="kleistad-edit kleistad-edit-link" >
							&nbsp;
						</a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button class="kleistad-button kleistad-edit-link" type="button" data-id="0" data-actie="toevoegen" >Toevoegen</button>
		<button class="kleistad-button kleistad-download-link" type="button" data-actie="workshops" >Download</button>
		<?php
	}

	/**
	 * Render het formulier
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function form_content() {
		$voltooid = strtotime( $this->data['workshop']['datum'] ) < strtotime( 'today' );
		$readonly = $this->data['workshop']['betaald'] || $this->data['workshop']['vervallen'] || $voltooid;
		?>
		<input type="hidden" name="workshop_id" value="<?php echo esc_attr( $this->data['workshop']['workshop_id'] ); ?>"/>
		<input type="hidden" name="aanvraag_id" value="<?php echo esc_attr( $this->data['workshop']['aanvraag_id'] ); ?>"/>
		<input type="hidden" name="vervallen" value="<?php echo (int) $this->data['workshop']['vervallen']; ?>" >
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_naam">Soort workshop</label></div>
			<div class="kleistad-col-3">
				<select name="naam" required id="kleistad_naam" <?php wp_readonly( $readonly ); ?> style="width:100%" >
					<?php foreach ( opties()['activiteit'] as $activiteit ) : ?>
					<option value="<?php echo esc_attr( sanitize_title( $activiteit['naam'] ) ); ?>" <?php selected( $this->data['workshop']['naam'], $activiteit['naam'] ); ?> >
						<?php echo esc_html( ucfirst( $activiteit['naam'] ) ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_contact">Naam contact</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="contact" id="kleistad_contact" required value="<?php echo esc_attr( $this->data['workshop']['contact'] ); ?>" <?php wp_readonly( $readonly ); ?> />
			</div>
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_organisatie">Organisatie</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="organisatie" id="kleistad_organisatie" value="<?php echo esc_attr( $this->data['workshop']['organisatie'] ); ?>" >
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_email">Email contact</label></div>
			<div class="kleistad-col-3">
				<input type="email" name="email" id="kleistad_email" required value="<?php echo esc_attr( $this->data['workshop']['email'] ); ?>" <?php wp_readonly( $readonly ); ?> />
			</div>
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_organisatie_email">Organisatie email</label></div>
			<div class="kleistad-col-3">
				<input type="email" name="organisatie_email" id="kleistad_organisatie_email" value="<?php echo esc_attr( $this->data['workshop']['organisatie_email'] ); ?>" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_telnr">Telefoon contact</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="telnr" id="kleistad_telnr" value="<?php echo esc_attr( $this->data['workshop']['telnr'] ); ?>" <?php wp_readonly( $readonly ); ?> />
			</div>
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_organisatie_adres">Organisatie adres</label></div>
			<div class="kleistad-col-3">
				<textarea name="organisatie_adres" id="kleistad_organisatie_adres" rows="2" maxlength="100" ><?php echo esc_textarea( $this->data['workshop']['organisatie_adres'] ); ?></textarea>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_aantal">Aantal deelnemers</label></div>
			<div class="kleistad-col-3">
				<input type="number" name="aantal" id="kleistad_aantal" min="1" value="<?php echo esc_attr( $this->data['workshop']['aantal'] ); ?>" <?php wp_readonly( $readonly ); ?> />
			</div>
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_docent">Docent</label></div>
			<div class="kleistad-col-3">
				<?php if ( $readonly ) : ?>
					<span id="kleistad_docent"><?php echo esc_html( $this->data['workshop']['docent_naam'] ); ?></span>
				<?php else : ?>
					<select style="width:100%" name="docent[]" id="kleistad_docent" multiple required >
						<?php foreach ( $this->data['docenten'] as $docent ) : ?>
							<option value="<?php echo esc_attr( $docent->ID ); ?>" <?php selected( in_array( $docent->ID, $this->data['workshop']['docent'], true ) ); ?> ><?php echo esc_html( $docent->display_name ); ?></option>
						<?php endforeach ?>
					</select>
				<?php endif ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_datum">Datum</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="datum" id="kleistad_datum" class="kleistad-datum" required value="<?php echo esc_attr( $this->data['workshop']['datum'] ); ?>" readonly="readonly" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_start_tijd">Begintijd</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="start_tijd" id="kleistad_start_tijd" placeholder="00:00" value="<?php echo esc_attr( $this->data['workshop']['start_tijd'] ); ?>" class="kleistad-tijd" required <?php wp_readonly( $readonly ); ?> />
			</div>
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_eind_tijd">Eindtijd</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="eind_tijd" id="kleistad_eind_tijd" placeholder="00:00" value="<?php echo esc_attr( $this->data['workshop']['eind_tijd'] ); ?>" class="kleistad-tijd" required <?php wp_readonly( $readonly ); ?> />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label>Technieken</label></div>
			<?php foreach ( [ 'Draaien', 'Handvormen' ] as $techniek ) : ?>
			<div class="kleistad-col-3" >
				<span>
					<input type="checkbox" id="kleistad_<?php echo esc_attr( strtolower( $techniek ) ); ?>" name="technieken[]" value="<?php echo esc_attr( $techniek ); ?>" <?php checked( in_array( $techniek, $this->data['workshop']['technieken'], true ) ); ?> <?php disabled( $readonly ); ?> >
					<label for="kleistad_<?php echo esc_attr( strtolower( $techniek ) ); ?>" style="padding-right:2em"><?php echo esc_html( $techniek ); ?></label>
				</span>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_programma">Programma</label></div>
			<div class="kleistad-col-8">
				<textarea name="programma" id="kleistad_programma" rows="5" maxlength="500" <?php wp_readonly( $readonly ); ?> ><?php echo esc_textarea( $this->data['workshop']['programma'] ); ?></textarea>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_kosten">Kosten</label></div>
			<div class="kleistad-col-2">
				<input type="number" lang="nl" step="0.01" name="kosten" id="kleistad_kosten" min="0" value="<?php echo esc_attr( $this->data['workshop']['kosten'] ); ?>" <?php wp_readonly( $readonly ); ?> >
			</div>
			<div class="kleistad-col-2">incl. BTW</div>
			<div class="kleistad-col-2">
				<!--suppress HtmlFormInputWithoutLabel --><input type="number" lang="nl" step="0.01" id="kleistad_kosten_ex_btw" min="0" value="<?php echo esc_attr( number_format( $this->data['workshop']['kosten'] / 1.21, 2 ) ); ?>" <?php wp_readonly( $readonly ); ?> >
			</div>
			<div class="kleistad-col-2">excl. BTW</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<input type="hidden" name="definitief" value="<?php echo (int) $this->data['workshop']['definitief']; ?>" >
				<?php if ( $this->data['workshop']['vervallen'] ) : ?>
					<span style="color:red" >Afspraak is vervallen</span>
				<?php else : ?>
					Afspraak definitief &nbsp;&nbsp;<?php echo $this->data['workshop']['definitief'] ? '&#10004;' : '&#10060;'; ?>
				<?php endif ?>
			</div>
			<div class="kleistad-col-3">
				Gefactureerd &nbsp;&nbsp;<?php echo $this->data['workshop']['gefactureerd'] ? '&#10004;' : '&#10060;'; ?>
			</div>
			<div class="kleistad-col-3">
				Betaald &nbsp;&nbsp;<input type="hidden" name="betaald" value="<?php echo (int) $this->data['workshop']['betaald']; ?>" ><?php echo $this->data['workshop']['betaald'] ? '&#10004;' : '&#10060;'; ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-7">
				<button class="kleistad-button" type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_bewaren" value="bewaren" <?php disabled( $readonly || $this->data['workshop']['definitief'] ); ?> >Opslaan</button>
				<button class="kleistad-button" type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_bevestigen" value="bevestigen" <?php disabled( $this->data['workshop']['vervallen'] ); ?>
					data-confirm="Workshop beheer|weet je zeker dat je nu de bevesting wilt versturen" >Bevestigen</button>
				<button class="kleistad-button" type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_afzeggen" value="afzeggen" <?php disabled( $readonly || 'toevoegen' === $this->display_actie || $this->data['workshop']['gefactureerd'] ); ?>
					data-confirm="Workshop beheer|weet je zeker dat je de workshop wilt afzeggen" >Afzeggen</button>
			</div>
			<div class="kleistad-col-3">
				<button class="kleistad-button kleistad-terug-link" type="button" style="float:right;">Terug</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Toon het overzicht van de communicatie rondom de workshop
	 */
	protected function form_communicatie() {
		?>
		<input type="hidden" name="casus_id" value="<?php echo esc_attr( $this->data['casus']['casus_id'] ); ?>"/>
		<table class="kleistad-formtable" >
			<tr>
				<th>Soort activiteit</th>
				<td><?php echo esc_html( ucfirst( $this->data['casus']['naam'] ) ); ?></td>
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
				<?php if ( $this->data['casus']['periode'] ) : ?>
				<th>Periode</th>
				<td><?php echo esc_html( $this->data['casus']['periode'] ); ?></td>
				<?php else : ?>
				<th>Planning</th>
				<td><?php echo esc_html( date( 'd-m-Y', $this->data['casus']['plandatum'] ) . " {$this->data['casus']['dagdeel']}" ); ?></td>
				<?php endif; ?>
			</tr>
			<?php if ( count( $this->data['casus']['technieken'] ) ) : ?>
			<tr>
				<th>Technieken</th>
				<td><?php echo esc_html( implode( ', ', $this->data['casus']['technieken'] ) ); ?></td>
			</tr>
			<?php endif; ?>
			<tr>
				<td colspan="2" ><label for="kleistad_reactie">Reactie</label></td>
			</tr>
			<tr>
				<td colspan="2" ><textarea id="kleistad_reactie" name="reactie" maxlength="1000" rows="10" required ></textarea></td>
			</tr>
		</table>
		<button class="kleistad-button" type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_reageren" value="reageren" >Reageren</button>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right">Terug</button>
		<div>
		<?php foreach ( $this->data['casus']['correspondentie'] as $correspondentie ) : ?>
			<div class="kleistad-workshop-correspondentie kleistad-workshop-correspondentie-folded kleistad-workshop-<?php echo esc_attr( $correspondentie['type'] ); ?>" >
				<strong><?php echo esc_html( ucfirst( $correspondentie['type'] ) . ' van ' . $correspondentie['from'] . ' op ' . $correspondentie['tijd'] ); ?></strong>
				<p><?php echo esc_html( $correspondentie['subject'] ); ?></p>
				<?php echo nl2br( $correspondentie['tekst'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<br/>
			</div>
			<div style="text-align:center;">
				<button class="kleistad-button kleistad-workshop-unfold" >Uitklappen</button>
				<button class="kleistad-button kleistad-workshop-fold" style="display:none;" >Inklappen</button>
			</div>
		<?php endforeach ?>
		<?php
	}

}
