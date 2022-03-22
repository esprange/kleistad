<?php
/**
 * Toon het betaling formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de betaal formulier.
 */
class Public_Betaling_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() {
		$this->form();
	}

	/**
	 * Maak de formulier inhoud
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function form_content() {
		$this->bestelling();
		if ( 0 < $this->data['openstaand'] ) {
			$this->betalen( $this->data['annuleerbaar'] );
		} elseif ( 0 > $this->data['openstaand'] ) {
			$this->terugstorten();
		} else {
			$this->geen_actie();
		}
		?>
		<?php
	}

	/**
	 * Render het overzicht van de bestelling
	 */
	private function bestelling() {
		?>
		<input type="hidden" name="order_id" value="<?php echo esc_attr( $this->data['order_id'] ); ?>" />
		<input type="hidden" name="betaal" value="ideal" />
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<h2>Overzicht betaling <?php echo esc_html( $this->data['betreft'] ); ?></h2>
			</div>
			<div class="kleistad-col-5" style="text-align: right">
				<button class="kleistad-button kleistad-download-link" type="button" data-actie="url_factuur" >factuur <?php echo esc_html( $this->data['factuur'] ); ?></button>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<p>Voor <?php echo esc_html( $this->data['klant'] ); ?></p>
			</div>
		</div>
		<table class="kleistad-datatable  display compact nowrap" data-paging="false" data-searching="false" data-ordering="false" data-info="false" >
			<thead>
				<tr><th>Aantal</th><th>Omschrijving</th><th>Stuksprijs</th><th>Prijs</th>
			</thead>
			<tbody>
			<?php foreach ( $this->data['orderregels']  as $orderregel ) : ?>
				<tr>
					<td style="text-align:right" ><?php echo esc_html( $orderregel->aantal ); ?></td>
					<td><?php echo esc_html( $orderregel->artikel ); ?></td>
					<td style="text-align:right" >&euro; <?php echo esc_html( number_format_i18n( $orderregel->prijs + $orderregel->btw, 2 ) ); ?></td>
					<td style="text-align:right" >&euro; <?php echo esc_html( number_format_i18n( $orderregel->aantal * ( $orderregel->prijs + $orderregel->btw ), 2 ) ); ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="3" style="text-align:right">Reeds betaald</td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $this->data['reeds_betaald'], 2 ) ); ?></td>
				</tr>
				<tr>
					<td colspan="3" style="text-align:right">Totale kosten</td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $this->data['reeds_betaald'] + $this->data['openstaand'], 2 ) ); ?></td>
				</tr>
				<tr>
					<td colspan="3" style="text-align:right">Saldo</td>
					<td style="text-align:right"><strong>&euro; <?php echo esc_html( number_format_i18n( $this->data['openstaand'], 2 ) ); ?></strong></td>
				</tr>
			</tfoot>
		</table>
		<?php
	}

	/**
	 * Render het betalen
	 *
	 * @param bool $annuleerbaar Of er een annuleer knop getoond kan worden.
	 */
	private function betalen( bool $annuleerbaar ) {
		?>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<?php $this->ideal(); ?>
			</div>
		</div>
		<div class="kleistad-row" style="padding-top: 20px;">
			<div class="kleistad-col-3" >
				<button class="kleistad-button" type="submit" name="kleistad_submit_betaling" id="kleistad_submit" value="betalen" >Betalen</button><br />
			</div>
			<?php if ( $annuleerbaar ) : ?>
			<div class="kleistad-col-5" style="text-align: right;">
				Het is nog mogelijk om deze bestelling te annuleren.
			</div>
			<div class="kleistad-col-2" style="text-align: right;" >
				<button class="kleistad-button" type="submit" name="kleistad_submit_betaling" value="annuleren"
					data-confirm="<?php echo esc_attr( $this->data['betreft'] ); ?>|Weet je zeker dat je deze bestelling wilt annuleren" id="kleistad_annuleren">Annuleren</button><br />
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render het terugstorten
	 */
	private function terugstorten() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10" style="padding-top: 20px;">
				Het nog openstaande bedrag zal zo spoedig mogelijk teruggestort worden
				<?php $this->home(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render geen actie
	 */
	private function geen_actie() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10" style="padding-top: 20px;">
				Er is geen verdere actie nodig
				<?php $this->home(); ?>
			</div>
		</div>
		<?php
	}

}
