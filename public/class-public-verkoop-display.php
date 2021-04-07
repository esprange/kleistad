<?php
/**
 * Toon het losse verkoop formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het losse verkoop formulier.
 */
class Public_Verkoop_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		$this->form()->verkoop()->form_end();
	}

	/**
	 * Render de dagdelenkaart info
	 *
	 * @return Public_Verkoop_Display
	 */
	private function verkoop() {
		?>
		<input type="hidden" name="klant_type" id="kleistad_klant_type" value="nieuw">
		<div id="kleistad_tabs" class="ui-tabs ui-widget">
			<ul class="ui-tabs-nav">
				<li><a href="#tab_nieuwe_klant">Losse verkoop</a></li>
				<li><a href="#tab_vaste_klant">Bestaande klant</a></li>
			</ul>
			<div id="tab_nieuwe_klant">
				<div class="kleistad-row">
					<div class="kleistad-col-3 kleistad-label">
						<label for="kleistad_klant">Klant naam</label>
					</div>
					<div class="kleistad-col-7">
						<input class="kleistad-input" id="kleistad_klant" type="text" size="20" name="klant" required
							value="<?php echo esc_attr( $this->data['input']['klant'] ); ?>"/>
					</div>
				</div>
				<div class="kleistad-row">
					<div class="kleistad-col-3 kleistad-label">
						<label for="kleistad_email">Email adres</label>
					</div>
					<div class="kleistad-col-7">
						<input class="kleistad-input" id="kleistad_email" type="email" name="email" required
							value="<?php echo esc_attr( $this->data['input']['email'] ); ?>"/>
					</div>
				</div>
			</div>
			<div id="tab_vaste_klant" style="display:none;">
				<div class="kleistad-row">
					<div class="kleistad-col-3 kleistad-label">
						<label for="kleistad_klant_id">Klant naam</label>
					</div>
					<div class="kleistad-col-7">
						<select name="klant_id" id="kleistad_klant_id" required >
							<?php foreach ( $this->data['gebruikers'] as $gebruiker ) : ?>
								<option value="<?php echo esc_attr( $gebruiker->id ); ?>" <?php selected( $gebruiker->id, $this->data['input']['klant_id'] ); ?> ><?php echo esc_html( $gebruiker->display_name ); ?></option>
							<?php endforeach ?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-6 kleistad-label">
				<label>Omschrijving</label>
			</div>
			<div class="kleistad-col-2 kleistad-label">
				<label>Stuksprijs</label>
			</div>
			<div class="kleistad-col-2 kleistad-label">
				<label>Aantal</label>
			</div>
		</div>
			<?php
				$index = 0;
				$count = count( $this->data['input']['omschrijving'] );
			do {
				?>
		<div class="kleistad-row">
			<div class="kleistad-col-6">
				<input class="kleistad-input" type="text" name="omschrijving[]" required value="<?php echo esc_attr( $this->data['input']['omschrijving'][ $index ] ); ?>" >
			</div>
			<div class="kleistad-col-2">
				<input class="kleistad-input" type="number" step="0.01" name="prijs[]" required value="<?php echo esc_attr( $this->data['input']['prijs'][ $index ] ); ?>" >
			</div>
			<div class="kleistad-col-2">
				<input class="kleistad-input" type="number" step="0.01" name="aantal[]" required value="<?php echo esc_attr( $this->data['input']['aantal'][ $index ] ); ?>" >
			</div>
		</div>
				<?php
				$index++;
			} while ( $index < $count );
			?>
		<div class="kleistad-row">
			<div class="kleistad-col-2">
				<button class="extra_regel ui-button ui-widget ui-corner-all" ><span class="dashicons dashicons-plus"></span></button><br/>&nbsp;
			</div>
		</div>
		<div class="kleistad-row">
			<button type="submit" name="kleistad_submit_verkoop" id="kleistad_submit_verkoop" value="verzenden">Verzenden</button>
		</div>
		<?php
		return $this;
	}

}
