<?php
/**
 * Toon het email formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de email formulier.
 */
class Public_Email_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		$this->form()->aan()->onderwerp()->aanhef()->inhoud()->verzender()->form_end();
	}

	/**
	 * Render de geadresseerden info
	 *
	 * @return Public_Email_Display
	 */
	private function aan() {
		?>
		<input id="kleistad_gebruikerids" name="gebruikerids" type="hidden">
		<div class="kleistad-row">
			<div class="kleistad-label">
				<label>Selecteer de groep(en) waarvoor de email verzonden moet worden</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div id="kleistad_gebruikers" class="kleistad-col-10" style="display:none" >
				<ul>
				<?php foreach ( $this->data['input']['tree'] as $groep ) : ?>
					<li>
						<?php echo esc_html( $groep['naam'] ); ?>
						<ul>
						<?php foreach ( $groep['leden'] as $gebruiker_id => $gebruiker ) : ?>
							<li gebruikerid="<?php echo esc_attr( $gebruiker_id ); ?>">
							<?php echo esc_html( $gebruiker ); ?>
							</li>
						<?php endforeach ?>
						</ul>
					</li>
				<?php endforeach ?>
				</ul>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het onderwerp
	 *
	 * @return Public_Email_Display
	 */
	private function onderwerp() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-label">
				<label for="kleistad_onderwerp" >Wat is het onderwerp van de email</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<input type="text" name="onderwerp" id="kleistad_onderwerp" required value="<?php echo esc_attr( $this->data['input']['onderwerp'] ); ?>" >
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de aanhef
	 *
	 * @return Public_Email_Display
	 */
	private function aanhef() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-label">
				<label for="kleistad_aanhef" >Aan wie is de email gericht</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<input type="text" name="aanhef" id="kleistad_aanhef" required value="<?php echo esc_attr( $this->data['input']['aanhef'] ); ?>" >
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de inhoud
	 *
	 * @return Public_Email_Display
	 */
	private function inhoud() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-label">
				<label for="kleistad_email" >Voer de tekst in van de email</label>
			</div>
		</div>
		<div class="kleistad-row" style="padding:15px;" >
		<?php
			wp_editor(
				$this->data['input']['email_content'],
				'kleistad_email',
				[
					'textarea_name'    => 'email_content',
					'textarea_rows'    => 6,
					'quicktags'        => false,
					'media_buttons'    => false,
					'drag_drop_upload' => true,
				]
			);
		?>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de verzender
	 *
	 * @return Public_Email_Display
	 */
	private function verzender() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-label">
				<label for="kleistad_namens" >Wie verstuurt de email</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<input type="text" name="namens" id="kleistad_namens" required value="<?php echo esc_attr( $this->data['input']['namens'] ); ?>" >,
			</div>
		</div>
		<div class="kleistad-row" >
			<button class="kleistad-button" type="submit" name="kleistad_submit_email" id="kleistad_submit_verzenden" value="verzenden" >Verzenden</button>
			<button class="kleistad-button" type="submit" name="kleistad_submit_email" id="kleistad_submit_testen" value="test_email" >Test Email verzenden</button>
		</div>
		<?php
		return $this;
	}

}
