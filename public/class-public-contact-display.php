<?php
/**
 * Toon het contact formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het contact formulier.
 */
class Public_Contact_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		$this->form(
			function() {
				?>
		<div class="kleistad-row">
			<div class="kleistad-col-4 kleistad-label">
				<label for="kleistad_naam">Je naam (verplicht)</label>
			</div>
			<div class="kleistad-col-6">
				<input class="kleistad-input" name="naam" id="kleistad_naam" type="text"
				required maxlength="25" placeholder="naam" title="Vul s.v.p. je naam in"
				value="<?php echo esc_attr( $this->data['input']['naam'] ); ?>" autocomplete="given-name" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-4 kleistad-label">
				<label for="kleistad_emailadres">Je email adres (verplicht)</label>
			</div>
			<div class="kleistad-col-6">
				<input class="kleistad-input" name="email" id="kleistad_emailadres" type="email"
				required placeholder="mijnemailadres@voorbeeld.nl" pattern="^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$"
				title="Vul s.v.p. een geldig email adres in"
				value="<?php echo esc_attr( $this->data['input']['email'] ); ?>" autocomplete="email" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-4 kleistad-label">
				<label for="kleistad_telefoon">Telefoon</label>
			</div>
			<div class="kleistad-col-6">
				<input class="kleistad-input" name="telnr" id="kleistad_telefoon" type="text"
				maxlength="15" placeholder="0123456789" title="Vul s.v.p. een geldig telefoonnummer in"
				value="<?php echo esc_attr( $this->data['input']['telnr'] ); ?>" autocomplete="tel" />
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-10">
				<label class="kleistad-label">Onderwerp</label>
			</div>
		</div>
				<?php foreach ( [ 'cursus', 'abonnement', 'stook', 'overig' ] as $onderwerp ) : ?>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-9 kleistad-label" >
				<input class="kleistad-radio" name="onderwerp" id="kleistad_<?php echo esc_attr( $onderwerp ); ?>" type="radio" required
				value="<?php echo esc_attr( $onderwerp ); ?>" <?php checked( $this->data['input']['onderwerp'], $onderwerp ); ?> >
				<label for="kleistad_<?php echo esc_attr( $onderwerp ); ?>" ><?php echo esc_html( ucfirst( $onderwerp ) ); ?></label>
			</div>
		</div>
		<?php endforeach; ?>
		<div class ="kleistad-row" >
			<div class="kleistad-col-4 kleistad-label">
				<label for="kleistad_vraag">Je vraag</label>
			</div>
		</div>
		<div class ="kleistad-row" title="Geef aan wat je vraag is of wat je ons wilt mededelen" >
			<div class="kleistad-col-10 kleistad-input">
				<textarea class="kleistad-input" name="vraag" id="kleistad_vraag" maxlength="1000" rows="5" cols="50" required ><?php echo esc_textarea( $this->data['input']['vraag'] ); ?></textarea>
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-10">
				<button class="kleistad-button" name="kleistad_submit_contact" id="kleistad_submit" type="submit" >Verzenden</button>
			</div>
		</div>
				<?php
			}
		);
	}
}
