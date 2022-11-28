<?php
/**
 * Toon het cursus overzicht formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het cursus overzicht formulier.
 */
class Public_Cursus_Overzicht_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 */
	protected function cursisten() {
		$this->form(
			function() {
				if ( current_user_can( BESTUUR ) ) {
					$this->cursisten_bestuur();
					return;
				}
				$this->cursisten_docent();
			}
		);
	}

	/**
	 * Render het indelen formulier
	 */
	protected function indelen() {
		$this->form(
			function() {
				?>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $this->data['cursus']['id'] ); ?>">
		<input type="hidden" name="cursist_id" value="<?php echo esc_attr( $this->data['cursist']['id'] ); ?>">
		<h2>Indeling op lopende cursus</h2>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label>Cursist</label>
			</div>
			<div class="kleistad-col-5">
				<?php echo esc_html( $this->data['cursist']['naam'] ); ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label>Inschrijfdatum</label>
			</div>
			<div class="kleistad-col-5">
				<?php echo esc_html( wp_date( 'd-m-Y', $this->data['cursist']['datum'] ) ); ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label>Prijs advies</label>
			</div>
			<div class="kleistad-col-5">
				<?php echo esc_html( "totaal {$this->data['cursus']['lessen']} lessen, resterend {$this->data['cursus']['lessen_rest']}" ); ?>
				<br/>
				<strong>advies prijs &euro; <?php echo esc_html( number_format_i18n( $this->data['cursus']['kosten'] * $this->data['cursist']['aantal'], 2 ) ); ?></strong>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label for="kleistad_kosten">Vastgestelde prijs</label>
			</div>
			<div class="kleistad-col-5">
				<input type=number name="kosten" id="kleistad_kosten" step="0.01" min="0" max="<?php echo esc_attr( $this->data['cursus']['max'] * $this->data['cursist']['aantal'] ); ?>"
					value="<?php echo esc_attr( number_format( $this->data['cursus']['kosten'] * $this->data['cursist']['aantal'], 2 ) ); ?>" >
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-3">
				<button class="kleistad-button" name="kleistad_submit_cursus_overzicht" id="kleistad_submit" type="submit" value="indelen_lopend" >Bevestigen</button>
			</div>
			<div class="kleistad-col-4">
			</div>
			<div class="kleistad-col-3">
				<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
			</div>
		</div>
				<?php
			}
		);
	}

	/**
	 * Render het uitschrijven formulier
	 */
	protected function uitschrijven_indelen() {
		$this->form(
			function() {
				?>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $this->data['cursus']['id'] ); ?>">
		<input type="hidden" name="cursist_id" value="<?php echo esc_attr( $this->data['cursist']['id'] ); ?>">
		<h2>Indelen op cursus of uitschrijven van wachtlijst</h2>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label>Cursist</label>
			</div>
			<div class="kleistad-col-5">
				<?php echo esc_html( $this->data['cursist']['naam'] ); ?>
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-3">
				<button class="kleistad-button" name="kleistad_submit_cursus_overzicht" type="submit" id="kleistad_submit" value="uitschrijven" >Uitschrijven</button>
			</div>
			<div class="kleistad-col-4">
				<button class="kleistad-button" name="kleistad_submit_cursus_overzicht" type="submit" id="kleistad_submit" value="indelen" >Indelen</button>
			</div>
			<div class="kleistad-col-3">
				<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
			</div>
		</div>
				<?php
			}
		);
	}

	/**
	 * Render de cursussen
	 */
	protected function overzicht() {
		?>
		<table class="kleistad-datatable display" id="kleistad_cursussen" data-order='[[ 0, "desc" ]]'>
			<thead>
			<tr>
				<th>Code</th>
				<th>Naam</th>
				<th>Docent</th>
				<th>Start</th>
				<th data-orderable="false"></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $this->data['cursus_info'] as $cursus_id => $cursus_info ) :
				?>
				<tr>
					<td data-sort="<?php echo esc_attr( $cursus_id ); ?>"><?php echo esc_html( $cursus_info['code'] ); ?></td>
					<td><?php echo esc_html( $cursus_info['naam'] ); ?></td>
					<td><?php echo esc_html( $cursus_info['docent'] ); ?></td>
					<td data-sort="<?php echo esc_attr( $cursus_info['start_dt'] ); ?>"><?php echo esc_html( $cursus_info['start_datum'] ); ?></td>
					<td>
						<?php if ( $cursus_info['heeft_inschrijvingen'] ) : ?>
							<a href="#" title="toon cursisten" class="kleistad-view kleistad-edit-link"	data-id="<?php echo esc_attr( $cursus_id ); ?>" data-actie="cursisten" >
								&nbsp;
							</a>
						<?php endif ?>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render het formulier
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function cursisten_bestuur() {
		?>
		<strong><?php echo esc_html( $this->data['cursus']['code'] . ' ' . $this->data['cursus']['naam'] ); ?></strong>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $this->data['cursus']['id'] ); ?>">
		<table class="kleistad-datatable display" data-paging="false" data-searching="false">
			<thead>
			<tr>
				<th>Naam</th>
				<th>Technieken</th>
				<th>Betaald</th>
				<th>Herinner Email</th>
				<th>Nog niet ingedeeld</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['cursisten'] as $cursist ) : ?>
				<tr>
					<td><?php echo esc_html( $cursist['naam'] ); ?></td>
					<td><?php echo esc_html( $cursist['technieken'] ); ?></td>
					<?php if ( $cursist['extra'] ) : ?>
						<td><span class="dashicons dashicons-minus"></span></td>
					<?php elseif ( $cursist['betaald'] ) : ?>
						<td><span class="dashicons dashicons-yes"></span></td>
					<?php else : ?>
						<td></td>
					<?php endif ?>
					<?php if ( $cursist['extra'] ) : ?>
						<td><span class="dashicons dashicons-minus"></span></td>
						<td><span class="dashicons dashicons-minus"></span></td>
					<?php else : ?>
						<td><?php echo ( ( $cursist['herinner_email'] ) ? '<span class="dashicons dashicons-yes"></span>' : '' ); ?></td>
						<td>
							<?php
							if ( ! $cursist['ingedeeld'] ) :
								if ( $cursist['wachtlopend'] || $cursist['wachtlijst'] ) :
									?>
									<a href="#" title="<?php echo $cursist['wachtlijst'] ? 'uitschrijven of indelen' : 'indelen'; ?>" class="kleistad-edit-link"
									data-id="<?php echo esc_attr( $cursist['code'] ); ?>"
									data-actie="<?php echo $cursist['wachtlijst'] ? 'uitschrijven_indelen' : 'indelen'; ?>" >
									<?php echo $cursist['wachtlijst'] ? 'wachtlijst' : 'wacht op factuur'; ?> </a>
								<?php else : ?>
									nog niet betaald !
									<?php
								endif;
							endif
							?>
						</td>
					<?php endif ?>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<br/>
		<button class="kleistad-button kleistad-download-link" type="button" name="kleistad_submit_cursus_overzicht" data-actie="cursisten" >Download</button>
		<button class="kleistad-button kleistad-download-link" type="button" name="kleistad_submit_cursus_overzicht" data-actie="presentielijst" >Presentielijst</button>
		<button class="kleistad-button" type="submit" name="kleistad_submit_cursus_overzicht" id="kleistad_herinner" value="herinner_email" data-confirm="Cursisten|weet je zeker dat je nu de herinneringsemail wilt versturen" >Verstuur herinner email</button>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
	}

	/**
	 * Render het formulier
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function cursisten_docent() {
		?>
		<strong><?php echo esc_html( $this->data['cursus']['code'] . ' ' . $this->data['cursus']['naam'] ); ?></strong>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $this->data['cursus']['id'] ); ?>">
		<table class="kleistad-datatable display" data-paging="false" data-searching="false">
			<thead>
			<tr>
				<th>Naam</th>
				<th>Technieken</th>
				<th>Betaald</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['cursisten'] as $cursist ) : ?>
				<tr>
					<td><?php echo esc_html( $cursist['naam'] ); ?></td>
					<td><?php echo esc_html( $cursist['technieken'] ); ?></td>
					<?php if ( $cursist['extra'] ) : ?>
						<td><span class="dashicons dashicons-minus"></span></td>
					<?php elseif ( $cursist['betaald'] ) : ?>
						<td><span class="dashicons dashicons-yes"></span></td>
					<?php else : ?>
						<td></td>
					<?php endif ?>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<br/>
		<button class="kleistad-button kleistad-download-link" type="button" name="kleistad_submit_cursus_overzicht" data-actie="presentielijst" >Presentielijst</button>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
	}

}
