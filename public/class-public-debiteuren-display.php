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

use DateTime;
use DateTimeZone;

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
	 */
	protected function blokkade() {
		$this->form( 'form_blokkade' );
	}

	/**
	 * Maak het blokkade formulier aan
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function form_blokkade() {
		?>
		<div class="kleistad-row">
			<p>Alle orders voorafgaand <?php echo esc_html( date( 'd-m-Y', $this->data['huidige_blokkade'] ) ); ?> zijn nu niet meer te wijzigen.
			Dat betekent dat er geen correcties of kortingen op deze orders kunnen worden gedaan omdat dit dan invloed heeft op bijvoorbeeld
			de BTW aangifte (de factuur wordt gewijzigd) of op de jaarrekening. Een order kan natuurlijk wel nog geannuleerd worden.</p>
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

	/**
	 * Render het debiteur formulier
	 */
	protected function debiteur() {
		$this->form( 'form_debiteur' );
	}

	/**
	 * Maak het debiteut form aan.
	 */
	protected function form_debiteur() {
		$factuur      = new Factuur();
		$factuur_urls = $factuur->overzicht( $this->data['debiteur']['factuur'] );
		?>
		<p><?php echo esc_html( ucfirst( $this->data['debiteur']['betreft'] ) . ', ' . ( ! $this->data['debiteur']['gesloten'] ? 'openstaand voor ' : 'besteld door ' ) . $this->data['debiteur']['naam'] ); ?></p>
		<table class="kleistad-form">
			<tr><th>referentie</th><td><?php echo esc_html( $this->data['debiteur']['referentie'] . ' geboekt op ' . date( 'd-m-Y', $this->data['debiteur']['sinds'] ) ); ?></td><th>historie</th></tr>
			<tr><th>factuur</th><td>
				<?php foreach ( $factuur_urls as $factuur_url ) : ?>
					<a href="<?php echo esc_url( $factuur_url ); ?>" target="_blank"><?php echo esc_html( basename( $factuur_url ) ); ?></a><br/>
				<?php endforeach ?>
				</td><td rowspan="3">
				<?php foreach ( $this->data['debiteur']['historie'] as $historie ) : ?>
					<?php echo esc_html( $historie ); ?><br/>
				<?php endforeach ?>
				</td>
			</tr>
			<tr><th>betaald</th><td>&euro; <?php echo esc_html( number_format_i18n( $this->data['debiteur']['betaald'], 2 ) ); ?></td></tr>
			<tr><th>openstaand</th><td>&euro; <?php echo esc_html( number_format_i18n( $this->data['debiteur']['openstaand'], 2 ) ); ?></td></tr>
			<tr><th colspan="2"><?php echo $this->data['debiteur']['terugstorting'] ? 'Een stornering is ingediend' : ''; ?></th></tr>
		</table>
		<input type="hidden" name="id" value="<?php echo esc_attr( $this->data['debiteur']['id'] ); ?>"/>
		<?php
		$this->bankbetaling()->annulering()->afboeking()->korting()->debiteur_end();
	}

	/**
	 * Toon het overzicht van cursussen
	 */
	protected function overzicht() {
		$datum = new Datetime();
		$datum->setTimezone( new DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' ) );
		if ( $this->data['terugstorten'] ) {
			echo melding( -1, 'Er staan nog per bank terug te storten bedragen open' ); // phpcs:ignore
		}
		?>
		<p><strong>Totaal openstaand:</strong> &euro; <?php echo esc_html( number_format_i18n( $this->data['openstaand'], 2 ) ); ?></p>
		<table class="kleistad-datatable display compact nowrap" data-page-length="10" data-order='[[ 3, "desc" ], [ 5, "asc" ]]' >
			<thead>
			<tr>
				<th>Code</th>
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

			foreach ( $this->data['debiteuren'] as $debiteur ) :
				$datum->setTimestamp( $debiteur['sinds'] );
				?>
				<tr style="<?php echo $debiteur['verval_datum'] <= strtotime( 'today' ) && ! $debiteur['gesloten'] ? 'color:#b30000' : ''; ?>" >
					<td><?php echo esc_html( $debiteur['referentie'] . ( $debiteur['credit'] ? '(C)' : '' ) ); ?></td>
					<td><?php echo esc_html( $debiteur['naam'] ); ?></td>
					<td><?php echo esc_html( $debiteur['betreft'] ); ?></td>
					<td style="text-align:right;" data-sort="<?php echo esc_attr( $debiteur['openstaand'] ); ?>">&euro; <?php echo esc_html( number_format_i18n( $debiteur['openstaand'], 2 ) ); ?></td>
					<td data-sort="<?php echo esc_attr( $debiteur['sinds'] ); ?>"><?php echo esc_html( $datum->format( 'd-m-Y H:i' ) ); ?></td>
					<td data-sort="<?php echo esc_attr( $debiteur['verval_datum'] ); ?>"><?php echo esc_html( date( 'd-m-Y', $debiteur['verval_datum'] ) ); ?></td>
					<td>
						<a href="#" title="wijzig order" class="kleistad-edit kleistad-edit-link" data-id="<?php echo esc_attr( $debiteur['id'] ); ?>" data-actie="debiteur" >
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
				<input type="radio" name="debiteur_actie" id="kleistad_deb_bankbetaling"
					value="<?php echo ( 0 < $this->data['debiteur']['openstaand'] ) ? 'bankbetaling' : 'bankstorting'; ?>" >
				<label for="kleistad_deb_bankbetaling">Bankbetaling invoeren</label>
			</div>
		</div>
		<div class="kleistad_deb_bankbetaling kleistad_deb_veld" style="display:none" >
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
				<input type="radio" name="debiteur_actie" id="kleistad_deb_annulering" value="annulering" >
				<label for="kleistad_deb_annulering">Annuleren</label>
			</div>
		</div>
		<div class="kleistad_deb_annulering kleistad_deb_veld" style="display:none" >
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
				<input type="radio" name="debiteur_actie" id="kleistad_deb_afboeken" value="afboeken" >
				<label for="kleistad_deb_afboeken">Afboeken (dubieuze debiteur)</label>
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
		if ( $this->data['debiteur']['geblokkeerd'] || $this->data['debiteur']['credit'] ) {
			return $this;
		}
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-6">
				<input type="radio" name="debiteur_actie" id="kleistad_deb_korting" value="korting" >
				<label for="kleistad_deb_korting">Korting verstrekken</label>
			</div>
		</div>
		<div class="kleistad_deb_korting kleistad_deb_veld" style="display:none" >
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
				<button class="kleistad-button" name="kleistad_submit_debiteuren" type="submit" value="factuur" >Herzend factuur</button>
			</div>
			<div class="kleistad-col-1">
			</div>
			<div class="kleistad-col-3">
				<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
			</div>
		</div>
		<span style="font-size:75%" >facturen aangemaakt voor <?php echo esc_html( date( 'd-m-Y', $this->data['huidige_blokkade'] ) ); ?> zijn niet meer te wijzigen</span>
		<?php
	}

}
