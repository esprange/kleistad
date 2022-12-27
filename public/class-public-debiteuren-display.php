<?php
/**
 * Toon het debiteuren beheer formulier
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
class Public_Debiteuren_Display extends Public_Shortcode_Display {

	/**
	 * Render het zoek deel van het formulier
	 */
	protected function zoek() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-2">
				<label for="kleistad_zoek">Zoek naar</label>
			</div>
			<div class="kleistad-col-4"  style="position: relative;">
				<input id="kleistad_zoek" type="text" style="height:40px;" placeholder="zoeken..." />
				<button class="kleistad-button kleistad-edit-link" type="submit" id="kleistad_zoek_icon" data-id="" data-actie="zoek" style="height:40px;position:absolute;right:0;z-index:2;"><span class="dashicons dashicons-search"></span></button>
			</div>
		</div>
		<br/><hr><br/>
		<?php
		$this->overzicht();
	}

	/**
	 * Render het blokkade formulier
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function blokkade() {
		$this->form(
			function() {
				?>
		<div class="kleistad-row">
				<?php if ( $this->data['wijzigbaar'] ) : ?>
				<p>Druk op 'bevestigen' als je het voorgaande kwartaal wilt afsluiten.</p>
		</div>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-3">
				<button class="kleistad-button" name="kleistad_submit_debiteuren" type="submit" id="kleistad_submit" value="blokkade"
					data-confirm="Debiteuren|Weet je zeker dat het voorgaand kwartaal wilt afsluiten" >Bevestigen</button>
			</div>
		</div>
			<?php else : ?>
		<div class="kleistad-row" >
			<p>Het voorgaand kwartaal is reeds afgesloten.</p>
		</div>
			<?php endif ?>
				<?php
			}
		);
	}

	/**
	 * Render het debiteur formulier
	 */
	protected function debiteur() {
		$this->form(
			function() {
				?>
		<p><?php echo esc_html( ucfirst( $this->data['debiteur']['betreft'] ) . ', ' . ( ! $this->data['debiteur']['gesloten'] ? 'openstaand voor ' : 'besteld door ' ) . $this->data['debiteur']['naam'] ); ?></p>
		<div class="kleistad-row">
			<div class="kleistad-col-2">
				<label class="kleistad-label">referentie</label>
			</div>
			<div class="kleistad-col-6">
				<?php echo esc_html( $this->data['debiteur']['referentie'] . ' geboekt op ' . wp_date( 'd-m-Y', $this->data['debiteur']['sinds'] ) ); ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2">
				<label class="kleistad-label">factuur</label>
			</div>
			<div class="kleistad-col-8">
				<a href="<?php echo esc_url( $this->data['debiteur']['factuur'] ); ?>" target="_blank"><?php echo esc_html( basename( $this->data['debiteur']['factuur'] ) ); ?></a><br/>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2">
				<label class="kleistad-label">historie</label>
			</div>
			<div class="kleistad-col-8" >
				<?php foreach ( $this->data['debiteur']['historie'] as $historie ) : ?>
					<?php echo esc_html( $historie ); ?><br/>
				<?php endforeach ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2">
				<label class="kleistad-label">betaald</label>
			</div>
			<div class="kleistad-col-4">
				&euro; <?php echo esc_html( number_format_i18n( $this->data['debiteur']['betaald'], 2 ) ); ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-2">
				<label class="kleistad-label">openstaand</label>
			</div>
			<div class="kleistad-col-4">
				&euro; <?php echo esc_html( number_format_i18n( $this->data['debiteur']['openstaand'], 2 ) ); ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-7">
				<?php echo $this->data['debiteur']['terugstorting'] ? '<strong>Een stornering is ingediend</strong>' : ''; // phpcs:ignore ?>
			</div>
		</div>
		<input type="hidden" name="id" value="<?php echo esc_attr( $this->data['debiteur']['id'] ); ?>"/>
				<?php
				$this->bankbetaling()->annulering()->afboeking()->korting()->debiteur_end();
			}
		);
	}

	/**
	 * Toon het overzicht van cursussen
	 */
	protected function overzicht() {
		if ( $this->data['terugstorten'] ) {
			echo melding( -1, 'Er staan nog per bank terug te storten bedragen open' ); // phpcs:ignore
		}
		?>
		<p><strong>Totaal openstaand:</strong> &euro; <?php echo esc_html( number_format_i18n( $this->data['openstaand'], 2 ) ); ?></p>
		<table class="kleistad-datatable display compact nowrap" data-page-length="10" data-order='[[ 3, "desc" ], [ 5, "asc" ]]' >
			<thead>
			<tr>
				<th>Code</th>
				<th>Factuur</th>
				<th>Naam</th>
				<th>Betreft</th>
				<th>Openstaand</th>
				<th>Sinds</th>
				<th>Vervaldatum</th>
				<th data-orderable="false"></th>
			</tr>
			</thead>
			<tbody>
			<?php

			$artikelregister = new Artikelregister();
			foreach ( $this->data['orders'] as $order ) :
				?>
				<tr style="<?php echo $order->verval_datum <= strtotime( 'today' ) && ! $order->gesloten ? 'color:#b30000' : ''; ?>" >
					<td><?php echo esc_html( $order->referentie . ( $order->credit ? '(C)' : '' ) ); ?></td>
					<td><?php echo esc_html( $order->get_factuurnummer() ); ?></td>
					<td><?php echo esc_html( $order->klant['naam'] ); ?></td>
					<td><?php echo esc_html( $artikelregister->get_naam( $order->referentie ) ); ?></td>
					<td style="text-align:right;" data-sort="<?php echo esc_attr( $order->get_te_betalen() ); ?>">&euro; <?php echo esc_html( number_format_i18n( $order->get_te_betalen(), 2 ) ); ?></td>
					<td data-sort="<?php echo esc_attr( $order->datum ); ?>"><?php echo esc_html( wp_date( 'd-m-Y H:i', $order->datum ) ); ?></td>
					<td data-sort="<?php echo esc_attr( $order->verval_datum ); ?>"><?php echo esc_html( wp_date( 'd-m-Y', $order->verval_datum ) ); ?></td>
					<td>
						<a href="#" title="wijzig order" class="kleistad-edit kleistad-edit-link" data-id="<?php echo esc_attr( $order->id ); ?>" data-actie="debiteur" >
							&nbsp;
						</a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render de bankbetaling sectie
	 *
	 * @return Public_Debiteuren_Display
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function bankbetaling() : Public_Debiteuren_Display {
		if ( $this->data['debiteur']['gesloten'] || $this->data['debiteur']['terugstorting'] ) {
			return $this;
		}
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-6">
				<input class="kleistad-radio" type="radio" name="debiteur_actie" id="kleistad_deb_bankbetaling"
					value="<?php echo ( 0 < $this->data['debiteur']['openstaand'] ) ? 'bankbetaling' : 'bankstorting'; ?>" >
				<label for="kleistad_deb_bankbetaling">Bankbetaling invoeren</label>
			</div>
		</div>
		<div id="kleistad_optie_bankbetaling" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-3" >
				</div>
				<?php if ( 0 < $this->data['debiteur']['openstaand'] ) : ?>
				<div class="kleistad-col-4 kleistad-label">
						<label for="kleistad_ontvangst">Ontvangen bedrag</label>
					</div>
					<div class="kleistad-col-3" >
						<input type="number" step="0.01" id="kleistad_ontvangst" name="bedrag_betaald" min="0.00" max="<?php echo esc_attr( $this->data['debiteur']['openstaand'] ); ?>" value="<?php echo esc_attr( max( 0, $this->data['debiteur']['ontvangst'] ) ); ?>">
					</div>
				<?php else : // Als een credit stand. ?>
					<div class="kleistad-col-4 kleistad-label">
						<label for="kleistad_terugstorting">Teruggestort bedrag</label>
					</div>
					<div class="kleistad-col-3" >
						<input type="number" step="0.01" id="kleistad_terugstorting" name="bedrag_gestort" min="0.00" max="<?php echo esc_attr( - $this->data['debiteur']['openstaand'] ); ?>" value="<?php echo esc_attr( max( 0, $this->data['debiteur']['ontvangst'] ) ); ?>">
					</div>
				<?php endif ?>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de annulering sectie
	 *
	 * @return Public_Debiteuren_Display
	 */
	private function annulering() : Public_Debiteuren_Display {
		if ( $this->data['debiteur']['credit'] || ! $this->data['debiteur']['annuleerbaar'] ) {
			return $this;
		}
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-6">
				<input class="kleistad-radio" type="radio" name="debiteur_actie" id="kleistad_deb_annulering" value="annulering" >
				<label for="kleistad_deb_annulering">Annuleren</label>
			</div>
		</div>
		<div id="kleistad_optie_annulering" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-4 kleistad-label">
					<label for="kleistad_restant">Restant te betalen</label>
				</div>
				<div class="kleistad-col-3" >
					<input type="number" step="0.01" id="kleistad_restant" name="restant" min="0" value="<?php echo esc_attr( $this->data['debiteur']['restant'] ); ?>">
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_opmerking_annulering">Opmerking</label>
				</div>
				<div class="kleistad-col-7" >
					<textarea class="kleistad-input" name="opmerking_annulering" id="kleistad_opmerking_annulering" maxlength="500" rows="5" cols="50"></textarea>
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de afboeking sectie
	 *
	 * @return Public_Debiteuren_Display
	 */
	private function afboeking() : Public_Debiteuren_Display {
		if ( ! $this->data['debiteur']['afboekbaar'] ) {
			return $this;
		}
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-6">
				<input class="kleistad-radio" type="radio" name="debiteur_actie" id="kleistad_deb_afboeken" value="afboeken" >
				<label for="kleistad_deb_afboeken">Afboeken (dubieuze debiteur)</label>
			</div>
		</div>
		<div id="kleistad_optie_afboeken" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-5 kleistad-label">
					<label>Boek de order af (dubieuze debiteur)</label>
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de korting sectie
	 *
	 * @return Public_Debiteuren_Display
	 */
	private function korting() : Public_Debiteuren_Display {
		if ( $this->data['debiteur']['credit'] ) {
			return $this;
		}
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-6">
				<input class="kleistad-radio" type="radio" name="debiteur_actie" id="kleistad_deb_korting" value="korting" >
				<label for="kleistad_deb_korting">Korting verstrekken</label>
			</div>
		</div>
		<div id="kleistad_optie_korting" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-4 kleistad-label">
					<label for="kleistad_korting">Korting</label>
				</div>
				<div class="kleistad-col-3" >
					<input type="number" step="0.01" id="kleistad_korting" name="korting" min="0" value="<?php echo esc_attr( $this->data['debiteur']['korting'] ); ?>">
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_opmerking_korting">Opmerking</label>
				</div>
				<div class="kleistad-col-7" >
					<textarea class="kleistad-input" name="opmerking_korting" id="kleistad_opmerking_korting" maxlength="500" rows="5" cols="50"></textarea>
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het de knoppen van het debiteur einde van het formulier
	 */
	private function debiteur_end() {
		?>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-3">
				<button class="kleistad-button" name="kleistad_submit_debiteuren" type="submit" id="kleistad_submit_debiteuren" disabled >Bevestigen</button>
			</div>
			<div class="kleistad-col-3">
				<button class="kleistad-button" name="kleistad_submit_debiteuren" type="submit" value="zend_factuur" >Herzend factuur</button>
			</div>
			<div class="kleistad-col-1">
			</div>
			<div class="kleistad-col-3">
				<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
			</div>
		</div>
		<?php
	}

}
