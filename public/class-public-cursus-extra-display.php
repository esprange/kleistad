<?php
/**
 * Toon het cursus extra formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het cursus extra formulier.
 */
class Public_Cursus_Extra_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		$this->form()->extra_cursisten()->form_end();
	}

	/**
	 * Render het formulier
	 *
	 * @return Public_Cursus_Extra_Display
	 */
	private function extra_cursisten() : Public_Cursus_Extra_Display {
		?>
		<h2>Opgave gegevens extra cursisten voor <?php echo esc_html( $this->data['cursus_naam'] ); ?></h2>
		<p>ingeschreven door <?php echo esc_html( $this->data['cursist_naam'] ); ?></p>
		<input type="hidden" name="code" value=<?php echo esc_attr( $this->data['cursist_code'] ); ?> >

		<?php
		foreach ( $this->data['input']['extra'] as $index => $extra_cursist ) :
			$readonly = 0 < $extra_cursist['id'] ? 'readonly' : '';
			?>
		<h2>Medecursist <?php echo esc_html( $index ); ?></h2>
		<div class="medecursist">
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_voornaam">Voornaam</label>
				</div>
				<div class="kleistad-col-4">
					<input class="kleistad-input" name="extra_cursist[<?php echo esc_attr( $index ); ?>][first_name]" id="kleistad_voornaam" type="text"
					maxlength="25" placeholder="voornaam" title="Vul s.v.p. de voornaam in"
					value="<?php echo esc_attr( $extra_cursist['first_name'] ); ?>" <?php echo esc_attr( $readonly ); ?> autocomplete="off" />
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_achternaam">Achternaam</label>
				</div>
				<div class="kleistad-col-4">
					<input class="kleistad-input" name="extra_cursist[<?php echo esc_attr( $index ); ?>][last_name]" id="kleistad_achternaam" type="text"
					maxlength="25" placeholder="achternaam" title="Vul s.v.p. de achternaam in"
					value="<?php echo esc_attr( $extra_cursist['last_name'] ); ?>" <?php echo esc_attr( $readonly ); ?> autocomplete="off" />
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_emailadres">Email adres</label>
				</div>
				<div class="kleistad-col-4">
					<input class="kleistad-input" name="extra_cursist[<?php echo esc_attr( $index ); ?>][user_email]" id="kleistad_emailadres" type="email"
					placeholder="mijnemailadres@voorbeeld.nl" pattern="^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$"
					title="Vul s.v.p. een geldig email adres in"
					value="<?php echo esc_attr( $extra_cursist['user_email'] ); ?>" <?php echo esc_attr( $readonly ); ?> autocomplete="off" />
					<input type="hidden" name="extra_cursist[<?php echo esc_attr( $index ); ?>][id]" value="<?php esc_attr( $extra_cursist['id'] ); ?>" />
				</div>
			</div>
		</div>	
		<?php endforeach ?>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-10">
				<button class="kleistad-button" name="kleistad_submit_cursus_extra" id="kleistad_submit" type="submit" >Opslaan</button>
			</div>
		</div>
		<?php
		return $this;
	}

}
