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
class Public_Betaling_Display extends ShortcodeDisplay {

	/**
	 * Render het formulier
	 *
	 * @return void
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function html() {
		if ( 'betalen' === $this->data['actie'] ) {
			$this->form()->overzicht();
			if ( 0 < $this->data['openstaand'] ) {
				$this->betalen();
			} elseif ( 0 > $this->data['openstaand'] ) {
				$this->terugstorten();
			} else {
				$this->geen_actie();
			}
			$this->form_end();
		}
	}

	/**
	 * Render het overzicht van de bestelling
	 *
	 * @return Public_Betaling_Display
	 */
	private function overzicht() : Public_Betaling_Display {
		?>
		<input type="hidden" name="order_id" value="<?php echo esc_attr( $this->data['order_id'] ); ?>" />
		<input type="hidden" name="artikel_type" value="<?php echo esc_attr( $this->data['artikel_type'] ); ?>" />
		<input type="hidden" name="betaal" value="ideal" />
		<h2>Overzicht betaling <?php echo esc_html( $this->data['betreft'] ); ?> </h2>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<p>Voor</p>
			</div>
			<div class="kleistad-col-7">
				<p><?php echo esc_html( $this->data['klant'] ); ?></p>
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
		return $this;
	}

	/**
	 * Render het betalen
	 *
	 * @return Public_Betaling_Display
	 */
	private function betalen() : Public_Betaling_Display {
		?>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<?php $this->ideal(); ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10" style="padding-top: 20px;">
				<button type="submit" name="kleistad_submit_betaling" id="kleistad_submit">Betalen</button><br />
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het terugstorten
	 *
	 * @return Public_Betaling_Display
	 */
	private function terugstorten() : Public_Betaling_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10" style="padding-top: 20px;">
				Het nog openstaande bedrag zal zo spoedig mogelijk teruggestort worden
				<?php echo $this->goto_home(); // phpcs:ignore ?>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render geen actie
	 *
	 * @return Public_Betaling_Display
	 */
	private function geen_actie() : Public_Betaling_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10" style="padding-top: 20px;">
				Er is geen verdere actie nodig
				<?php echo $this->goto_home(); // phpcs:ignore ?>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het einde van het formulier
	 *
	 * @return Public_Betaling_Display
	 */
	private function form_end() : Public_Betaling_Display {
		?>
		</form>
		<?php
		return $this;
	}
}
