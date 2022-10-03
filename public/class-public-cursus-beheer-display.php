<?php
/**
 * Toon het cursus beheer formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de cursus beheer formulier.
 */
class Public_Cursus_Beheer_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function toevoegen() {
		$this->form();
	}

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function wijzigen() {
		$this->form();
	}

	/**
	 * Toon het overzicht van cursussen
	 */
	protected function overzicht() {
		?>
		<table class="kleistad-datatable display compact nowrap responsive" id="kleistad_cursussen" data-page-length="10" data-order='[[ 0, "desc" ]]' >
			<thead>
			<tr>
				<th>Code</th>
				<th data-priority="2">Naam</th>
				<th>Docent</th>
				<th class="wrap" data-priority="3">Periode</th>
				<th class="wrap">Tijd</th>
				<th>Status</th>
				<th data-orderable="false" data-priority="1"></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['cursussen'] as $cursus ) : ?>
				<tr <?php echo $cursus['vervallen'] ? 'style="background-color:lightgray"' : ''; ?> >
					<td data-sort="<?php echo esc_attr( $cursus['id'] ); ?>">C<?php echo esc_html( $cursus['id'] ); ?></td>
					<td><?php echo esc_html( $cursus['naam'] ); ?></td>
					<td><?php echo esc_html( $cursus['docent'] ); ?></td>
					<td><?php echo esc_html( $cursus['start_datum'] ); ?> <?php echo esc_html( $cursus['eind_datum'] ); ?></td>
					<td><?php echo esc_html( $cursus['start_tijd'] ); ?> <?php echo esc_html( $cursus['eind_tijd'] ); ?></td>
					<td><?php echo esc_html( $cursus['status'] ); ?></td>
					<td>
						<a href="#" title="wijzig cursus" class="kleistad-edit kleistad-edit-link" style="padding:.4em .8em;"
							data-id="<?php echo esc_attr( $cursus['id'] ); ?>" data-actie="wijzigen" >
							&nbsp;
						</a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button type="button" class="kleistad-button kleistad-edit-link" data-id="0" data-actie="toevoegen" >Toevoegen</button>
		<?php
	}

	/**
	 * Render het formulier
	 */
	protected function form_content() {
		$readonly = $this->data['cursus']['eind_datum'] < strtotime( 'today' );
		?>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $this->data['cursus']['id'] ); ?>"/>
		<input type="hidden" name="lesdatums" id="kleistad_lesdatums" value="<?php echo esc_attr( $this->data['cursus']['lesdatums'] ); ?>" >
		<?php if ( ! empty( $this->data['cursus']['code'] ) ) : ?>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label>Cursuscode</label></div>
			<div class="kleistad-col-3"><?php echo esc_html( $this->data['cursus']['code'] ); ?></div>
		</div>
			<?php
		endif;
		$this->algemeen( $readonly )->planning( $readonly )->technieken( $readonly )->parameters( $readonly );
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<button class="kleistad-button" type="submit" id="kleistad_submit_cursus_bewaren" name="kleistad_submit_cursus_beheer" value="bewaren" <?php disabled( $readonly ); ?> >Bewaren</button>
				<button class="kleistad-button" type="submit" id="kleistad_submit_cursus_verwijderen" name="kleistad_submit_cursus_beheer" value="verwijderen" <?php disabled( 'toevoegen' === $this->display_actie ); ?> >Verwijderen</button>
			</div>
			<div class="kleistad-col-5">
				<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
			</div>
		</div>
		<?php
	}

	/**
	 * De algemene aspecten van de cursus
	 *
	 * @param bool $readonly Of het alleen lezen betreft.
	 *
	 * @return Public_Cursus_Beheer_Display
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function algemeen( bool $readonly ) : Public_Cursus_Beheer_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_cursus_naam">Naam</label></div>
			<div class="kleistad-col-8">
				<input type="text" name="naam" <?php wp_readonly( $readonly ); ?> id="kleistad_cursus_naam" maxlength="40" placeholder="Bijv. cursus draaitechnieken" value="<?php echo esc_attr( $this->data['cursus']['naam'] ); ?>" required >
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_docent">Docent</label></div>
			<div class="kleistad-col-3">
			<?php if ( $readonly ) : ?>
				<span id="kleistad_docent"><?php echo esc_html( is_numeric( $this->data['cursus']['docent'] ) ? get_user_by( 'id', $this->data['cursus']['docent'] )->display_name : $this->data['cursus']['docent'] ); ?></span>
			<?php else : ?>
				<select class="kleistad-select" name="docent" id="kleistad_docent" required >
				<?php foreach ( $this->data['docenten'] as $docent ) : ?>
					<option value="<?php echo esc_attr( $docent->ID ); ?>" <?php selected( $docent->ID, $this->data['cursus']['docent'] ); ?> ><?php echo esc_html( $docent->display_name ); ?></option>
				<?php endforeach ?>
				</select>
			<?php endif ?>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * De planning aspecten van de cursus
	 *
	 * @param bool $readonly Of het alleen lezen betreft.
	 *
	 * @return Public_Cursus_Beheer_Display
	 */
	private function planning( bool $readonly ) : Public_Cursus_Beheer_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_start_datum">Start</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="start_datum" id="kleistad_start_datum" class="kleistad-datum" required
				value="<?php echo esc_attr( date( 'd-m-Y', $this->data['cursus']['start_datum'] ) ); ?>" readonly="readonly" <?php disabled( $readonly ); ?> />
			</div>
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_eind_datum">Eind</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="eind_datum" id="kleistad_eind_datum" class="kleistad-datum" required
				value="<?php echo esc_attr( date( 'd-m-Y', $this->data['cursus']['eind_datum'] ) ); ?>" readonly="readonly" <?php disabled( $readonly ); ?> />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2"><input type="hidden" id="kleistad_lesdatum" class="kleistad-datum" <?php disabled( $readonly ); ?> /></div>
			<div class="kleistad-col-8">
				<ul style="list-style-type:none;column-count:4" id="kleistad_lesdatums_lijst"></ul>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_start_tijd">Begintijd</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="start_tijd" id="kleistad_start_tijd" placeholder="00:00" class="kleistad-tijd"
				value="<?php echo esc_attr( date( 'H:i', $this->data['cursus']['start_tijd'] ) ); ?>" <?php wp_readonly( $readonly ); ?> />
			</div>
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_eind_tijd">Eindtijd</label></div>
			<div class="kleistad-col-3">
				<input type="text" name="eind_tijd" id="kleistad_eind_tijd" placeholder="00:00" class="kleistad-tijd"
				value="<?php echo esc_attr( date( 'H:i', $this->data['cursus']['eind_tijd'] ) ); ?>" <?php wp_readonly( $readonly ); ?> />
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * De techniek aspecten van de cursus
	 *
	 * @param bool $readonly Of het alleen lezen betreft.
	 *
	 * @return Public_Cursus_Beheer_Display
	 */
	private function technieken( bool $readonly ) : Public_Cursus_Beheer_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label>Technieken</label></div>
			<div class="kleistad-col-8" style="display:flex;justify-content: space-between;">
				<?php foreach ( [ 'Draaien', 'Handvormen', 'Boetseren' ] as $techniek ) : ?>
					<span>
				<input type="checkbox" id="kleistad_<?php echo esc_attr( strtolower( $techniek ) ); ?>" class="kleistad-checkbox" name="technieken[]" value="<?php echo esc_attr( $techniek ); ?>"
					<?php checked( in_array( 'Draaien', $this->data['cursus']['technieken'], true ) ); ?> <?php disabled( $readonly ); ?> >
				<label for="kleistad_<?php echo esc_attr( strtolower( $techniek ) ); ?>" style="padding-right:2em"><?php echo esc_html( $techniek ); ?></label>
				</span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * De instellingen van de cursus
	 *
	 * @param bool $readonly Of het alleen lezen betreft.
	 */
	private function parameters( bool $readonly ) {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_inschrijfkosten">Inschrijf kosten</label></div>
			<div class="kleistad-col-3">
				<input type="number" lang="nl" step="0.01" name="inschrijfkosten" id="kleistad_inschrijfkosten" <?php wp_readonly( $readonly ); ?> value="<?php echo esc_attr( $this->data['cursus']['inschrijfkosten'] ); ?>" min="0" required >
			</div>
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_cursuskosten">Cursus kosten, excl. inschrijf kosten</label></div>
			<div class="kleistad-col-3">
				<input type="number" lang="nl" step="0.01" name="cursuskosten" id="kleistad_cursuskosten" <?php wp_readonly( $readonly ); ?> value="<?php echo esc_attr( $this->data['cursus']['cursuskosten'] ); ?>" min="0" required >
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_maximum">Maximum cursisten</label></div>
			<div class="kleistad-col-3">
				<input type="number" step="1" name="maximum" <?php wp_readonly( $readonly ); ?> id="kleistad_maximum" min="1" max="99" value="<?php echo esc_attr( $this->data['cursus']['maximum'] ); ?>" required>
			</div>
			<div class="kleistad-col-3 kleistad-label"><label for="kleistad_meer">Inschrijven meerdere cursisten mogelijk</label></div>
			<div class="kleistad-col-2">
				<input type="checkbox" class="kleistad-checkbox" name="meer" <?php disabled( $readonly ); ?> id="kleistad_meer" <?php checked( $this->data['cursus']['meer'] ); ?> >
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_tonen">Publiceer de cursus</label></div>
			<div class="kleistad-col-3">
				<input type="checkbox" class="kleistad-checkbox" name="tonen" <?php disabled( $readonly ); ?> id="kleistad_tonen" <?php checked( $this->data['cursus']['tonen'] ); ?> >
			</div>
			<div class="kleistad-col-3 kleistad-label"><label for="kleistad_vervallen">Cursus vervallen</label></div>
			<div class="kleistad-col-2">
				<input type="checkbox" class="kleistad-checkbox" name="vervallen" <?php disabled( $readonly ); ?> id="kleistad_vervallen" <?php checked( $this->data['cursus']['vervallen'] ); ?> >
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_inschrijfslug">Inschrijf email</label></div>
			<div class="kleistad-col-8">
				<input type="text" name="inschrijfslug" <?php wp_readonly( $readonly ); ?> id="kleistad_inschrijfslug" value="<?php echo esc_attr( $this->data['cursus']['inschrijfslug'] ); ?>" required >
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2 kleistad-label"><label for="kleistad_indelingslug">Indeling email</label></div>
			<div class="kleistad-col-8">
				<input type="text" name="indelingslug" <?php wp_readonly( $readonly ); ?> id="kleistad_indelingslug" value="<?php echo esc_attr( $this->data['cursus']['indelingslug'] ); ?>" required >
			</div>
		</div>
		<?php
	}
}
