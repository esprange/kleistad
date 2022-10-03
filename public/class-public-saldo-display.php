<?php
/**
 * Toon het saldo bijstorten formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de saldo formulier.
 */
class Public_Saldo_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		$this->form();
	}

	/**
	 * Maak de formulier inhoud aan
	 */
	protected function form_content() {
		$this->bijstorten()->betaal_info();
		$this->submit();
	}

	/**
	 * Render de dagdelenkaart info
	 *
	 * @return Public_Saldo_Display
	 */
	private function bijstorten() : Public_Saldo_Display {
		?>
		<p>Je huidige saldo is <strong>&euro; <?php echo esc_html( $this->data['saldo'] ); ?></strong></p>
		<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( $this->data['gebruiker_id'] ); ?>" />
		<div class="kleistad-row">
			<div class="kleistad-col-2">
				<label class="kleistad-label">Bedrag</label>
			</div>
			<div class="kleistad-col-2">
				<input class="kleistad-radio" type="radio" name="bedrag" id="kleistad_b15" value="15" />
				<label for="kleistad_b15">&euro; 15</label>
			</div>
			<div class="kleistad-col-2">
				<input class="kleistad-radio" type="radio" name="bedrag" id="kleistad_b30" value="30" checked="checked" />
				<label for="kleistad_b30">&euro; 30</label>
			</div>
			<div class="kleistad-col-4">
				<input class="kleistad-radio" type="radio" name="bedrag" id="kleistad_ander" value="0" />
				<label for="kleistad_ander">anders &euro;&nbsp;
					<input name="ander" type="number" min="15" max="100" maxlength="3" style="width:4em;" title="minimum 15, maximum 100 euro" >
				</label>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de betaal sectie
	 *
	 * @return void
	 */
	protected function betaal_info() : void {
		?>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input class="kleistad-radio" type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" checked />
				<label for="kleistad_betaal_ideal"></label>
			</div>
		</div>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<?php $this->ideal(); ?>
			</div>
		</div>
		<?php if ( setup()['stort'] ) : ?>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input class="kleistad-radio" type="radio" name="betaal" id="kleistad_betaal_stort" required value="stort" />
				<label for="kleistad_betaal_stort"></label>
			</div>
		</div>
		<?php endif; ?>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input class="kleistad-radio" type="radio" name="betaal" id="kleistad_betaal_terugboeking" required value="terugboeking"
				<?php disabled( $this->data['saldo'] <= opties()['administratiekosten'] ); ?> />
				<label for="kleistad_betaal_terugboeking"></label>
			</div>
		</div>
		<?php
	}

	/**
	 * Render de afronding van het formulier
	 */
	private function submit() {
		?>
		<div class="kleistad-row" style="padding-top: 20px;">
			<div class="kleistad-col-5" >
				<button class="kleistad-button" type="submit" name="kleistad_submit_saldo" value="storten" id="kleistad_submit" >Betalen</button><br />
			</div>
		</div>
		<?php
	}
}
