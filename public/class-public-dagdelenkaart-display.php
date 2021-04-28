<?php
/**
 * Toon het dagdelenkaart inschrijving formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de dagdelenkaart inschrijving formulier.
 */
class Public_Dagdelenkaart_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function html() {
		$this->form();
		if ( is_user_logged_in() ) {
			$this->dagdelenkaart_info()->gebruiker_logged_in()->opmerking()->verklaring()->nieuwsbrief();
		} else {
			$this->dagdelenkaart_info()->gebruiker()->opmerking()->verklaring()->nieuwsbrief();
		}
		$this->betaal_info()->form_end();
	}

	/**
	 * Render de dagdelenkaart info
	 *
	 * @return Public_Dagdelenkaart_Display
	 */
	private function dagdelenkaart_info() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_start_datum">Start per</label>
			</div>
			<div class="kleistad-col-3 kleistad-input">
				<input class="kleistad-datum kleistad-input" name="start_datum" id="kleistad_start_datum" type="text" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de betaal sectie
	 *
	 * @return Public_Dagdelenkaart_Display
	 */
	private function betaal_info() : Public_Dagdelenkaart_Display {
		?>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" checked />
				<label for="kleistad_betaal_ideal">Ik betaal € <?php echo esc_html( ( number_format_i18n( opties()['dagdelenkaart'], 2 ) ) ); ?></label>
			</div>
		</div>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<?php $this->ideal(); ?>
			</div>
		</div>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input type="radio" name="betaal" id="kleistad_betaal_stort" required value="stort" />
				<label for="kleistad_betaal_stort">Ik betaal door storting van € <?php echo esc_html( ( number_format_i18n( opties()['dagdelenkaart'], 2 ) ) ); ?> volgens de betaalinstructie, zoals aangegeven in de te ontvangen bevestigingsemail.</label>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de afronding van het formulier
	 *
	 * @return Public_Dagdelenkaart_Display
	 */
	protected function form_end() : Public_Dagdelenkaart_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10" style="padding-top: 20px;">
				<button class="kleistad-button" name="kleistad_submit_dagdelenkaart" id="kleistad_submit" type="submit" <?php disabled( ! is_super_admin() && '' !== $this->data['verklaring'] ); ?>>Betalen</button><br />
			</div>
		</div>
		</form>
		<?php
		return $this;
	}
}
