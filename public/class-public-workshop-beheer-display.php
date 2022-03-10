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
	 * Render het formulier toevoegen
	 */
	protected function toevoegen() {
		$this->form();
	}

	/**
	 * Render het formulier wijzigen
	 */
	protected function wijzigen() {
		$this->form();
	}

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function form_content() {
		?>
		<div id="kleistad_workshopbeheer" style="background-color: gainsboro;">
			<ul>
				<li><a href="#tabs_detail">Details</a></li>
				<li><a href="#tabs_communicatie">Communicatie</a></li>
			</ul>
			<div id="tabs_detail">
				<?php $this->form_details(); ?>
			</div>
			<div id="tabs_communicatie">
				<?php $this->form_communicatie(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Toon het overzicht van workshops
	 */
	protected function overzicht() {
		if ( $this->data['gaat_vervallen'] ) {
			echo melding( -1, 'Morgen gaan een of meer concept workshops vervallen !' ); // phpcs:ignore
		}
		?>
		<table id="kleistad_workshops" class="kleistad-datatable display compact nowrap" data-page-length="10" data-order='[[ 8, "desc" ]]' >
			<thead>
			<tr>
				<th>Code</th>
				<th>Datum</th>
				<th>Contact</th>
				<th>Docent</th>
				<th>Aantal</th>
				<th>Tijd</th>
				<th>Status</th>
				<th>Mail</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $this->data['workshops'] as $workshop ) :
				?>
				<tr>
					<td data-sort="<?php echo esc_attr( $workshop['id'] ); ?>"><?php echo esc_html( $workshop['code'] ); ?></td>
					<td data-sort="<?php echo esc_attr( $workshop['datum_ux'] ); ?>"><?php echo esc_html( $workshop['datum'] ); ?></td>
					<td><?php echo esc_html( $workshop['contact'] ); ?></td>
					<td><?php echo $workshop['docent']; // phpcs:ignore ?></td>
					<td><?php echo esc_html( $workshop['aantal'] ); ?></td>
					<td><?php echo esc_html( $workshop['start_tijd'] ); ?><br/><?php echo esc_html( $workshop['eind_tijd'] ); ?></td>
					<td><?php echo esc_html( $workshop['status'] ); ?></td>
					<td><?php echo esc_html( $workshop['cstatus'] ); ?></td>
					<td data-sort="<?php echo esc_attr( $workshop['update'] ); ?>" >
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
	 * Render de details van het formulier
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function form_details() {
		$voltooid = strtotime( $this->data['workshop']['datum'] ) < strtotime( 'today' );
		$readonly = $this->data['workshop']['betaald'] || $this->data['workshop']['vervallen'] || $voltooid;
		?>
		<input type="hidden" name="workshop_id" value="<?php echo esc_attr( $this->data['workshop']['workshop_id'] ); ?>"/>
		<input type="hidden" name="aanvraag_id" value="<?php echo esc_attr( $this->data['workshop']['aanvraag_id'] ); ?>"/>
		<input type="hidden" name="vervallen" value="<?php echo (int) $this->data['workshop']['vervallen']; ?>" >
		<?php $this->activiteit_details( $readonly )->contact_details( $readonly )->planning_details( $readonly )->kosten_details( $readonly )->status_details(); ?>
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
		<div class="kleistad-row" >
			<div class="kleistad-col-2">
				<label for="kleistad_reactie">Reactie</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-10">
				<textarea id="kleistad_reactie" name="reactie" maxlength="1000" rows="10" ></textarea>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<button class="kleistad-button" type="submit" name="kleistad_submit_workshop_beheer" id="kleistad_workshop_reageren" value="reageren" >Reageren</button>
				<button class="kleistad-button kleistad-terug-link" type="button" style="float:right">Terug</button>
			</div>
		</div>
		<div>
		<?php foreach ( $this->data['workshop']['communicatie'] as $key => $communicatie ) : ?>
			<div class="kleistad-row" id="kleistad_communicatie_<?php echo esc_attr( $key ); ?>" >
				<div class="kleistad-workshop-communicatie kleistad-workshop-communicatie-folded kleistad-workshop-<?php echo esc_attr( $communicatie['type'] ); ?>" >
					<strong><?php echo esc_html( ucfirst( $communicatie['type'] ) . ' van ' . $communicatie['from'] . ' op ' . $communicatie['tijd'] ); ?></strong>
					<p><?php echo esc_html( $communicatie['subject'] ); ?></p>
					<?php echo wp_kses_post( nl2br( $communicatie['tekst'] ) ); ?>
					<br/>
				</div>
				<div style="text-align:center;">
					<button class="kleistad-button kleistad-workshop-unfold" >Meer...</button>
				</div>
			</div>
		<?php endforeach ?>
		<?php
	}

	/**
	 * De activiteit details.
	 *
	 * @param bool $readonly Of de gegevens wijzigbaar zijn.
	 *
	 * @return Public_Workshop_Beheer_Display
	 */
	private function activiteit_details( bool $readonly ) : Public_Workshop_Beheer_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_naam">Soort workshop</label></div>
			<div class="kleistad-col-3">
				<select name="naam" required id="kleistad_naam" <?php wp_readonly( $readonly ); ?> style="width:100%" >
					<?php foreach ( opties()['activiteit'] as $activiteit ) : ?>
					<option value="<?php echo esc_attr( sanitize_title( $activiteit['naam'] ) ); ?>" <?php selected( 0 === strcasecmp( $this->data['workshop']['naam'], $activiteit['naam'] ) ); ?> >
						<?php echo esc_html( ucfirst( $activiteit['naam'] ) ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * De contact details.
	 *
	 * @param bool $readonly Of de gegevens wijzigbaar zijn.
	 *
	 * @return Public_Workshop_Beheer_Display
	 */
	private function contact_details( bool $readonly ) : Public_Workshop_Beheer_Display {
		?>
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
		<?php
		return $this;
	}

	/**
	 * De planning van de activiteit
	 *
	 * @param bool $readonly  Of de gegevens wijzigbaar zijn.
	 *
	 * @return Public_Workshop_Beheer_Display
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function planning_details( bool $readonly ) : Public_Workshop_Beheer_Display {
		?>
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
					<select style="width:100%" name="docent[]" id="kleistad_docent" multiple >
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
					<input type="checkbox" id="kleistad_<?php echo esc_attr( strtolower( $techniek ) ); ?>" class="kleistad-checkbox" name="technieken[]" value="<?php echo esc_attr( $techniek ); ?>" <?php checked( in_array( $techniek, $this->data['workshop']['technieken'], true ) ); ?> <?php disabled( $readonly ); ?> >
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
		<?php
		return $this;
	}

	/**
	 * De kosten details
	 *
	 * @param bool $readonly  Of de gegevens wijzigbaar zijn.
	 *
	 * @return Public_Workshop_Beheer_Display
	 */
	private function kosten_details( bool $readonly ) : Public_Workshop_Beheer_Display {
		?>
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
		<?php
		return $this;
	}

	/**
	 * Geef de status van de workshop weer.
	 *
	 * @return void
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function status_details() {
		?>
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
		<?php
	}
}
