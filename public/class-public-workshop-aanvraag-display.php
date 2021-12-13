<?php
/**
 * Toon het workshop aanvraag formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het workshop aanvraag formulier.
 */
class Public_Workshop_Aanvraag_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() {
		$this->form();
	}

	/**
	 * Maa de formulier inhoud aan
	 */
	protected function form_content() {
		$this->aanvraag()->contactinfo()->workshopinfo();
	}

	/**
	 * Render het formulier
	 *
	 * @return Public_Workshop_Aanvraag_Display
	 */
	private function aanvraag() : Public_Workshop_Aanvraag_Display {
		?>
		<div class="kleistad-row" >
			<div class="kleistad-col-5">
				<label class="kleistad-label">Wil je een vraag stellen over een</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-3 kleistad-label" >
				<input name="naam" id="kleistad_kinderfeest" type="radio" required value="kinderfeest" <?php checked( $this->data['input']['naam'], 'kinderfeest' ); ?> >
				<label for="kleistad_kinderfeest" >Kinderfeest</label>
			</div>
			<div class="kleistad-col-3 kleistad-label" >
				<input name="naam" id="kleistad_workshop" type="radio" required value="workshop" <?php checked( $this->data['input']['naam'], 'workshop' ); ?> >
				<label for="kleistad_workshop" >Workshop</label>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het formulier
	 *
	 * @return Public_Workshop_Aanvraag_Display
	 */
	private function contactinfo() : Public_Workshop_Aanvraag_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_contact">Naam</label>
			</div>
			<div class="kleistad-col-4">
				<input class="kleistad-input" name="contact" id="kleistad_contact" type="text"
				required maxlength="25" placeholder="naam" title="Vul s.v.p. je naam in"
				value="<?php echo esc_attr( $this->data['input']['contact'] ); ?>" autocomplete="given-name" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_emailadres">Email adres</label>
			</div>
			<div class="kleistad-col-4">
				<input class="kleistad-input" name="email" id="kleistad_emailadres" type="email"
				required placeholder="mijnemailadres@voorbeeld.nl" pattern="^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$"
				title="Vul s.v.p. een geldig email adres in"
				value="<?php echo esc_attr( $this->data['input']['email'] ); ?>" autocomplete="email" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_emailadres_controle">Email adres (controle)</label>
			</div>
			<div class="kleistad-col-4">
				<input class="kleistad-input" name="email_controle" id="kleistad_emailadres_controle" type="email"
				required title="Vul ter controle s.v.p. opnieuw het email adres in"
				value="<?php echo esc_attr( $this->data['input']['email_controle'] ); ?>"
				oninput="validate_email( this, kleistad_emailadres );"/>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_telefoon">Telefoon</label>
			</div>
			<div class="kleistad-col-2">
				<input class="kleistad-input" name="telnr" id="kleistad_telefoon" type="text"
				maxlength="15" placeholder="0123456789" title="Vul s.v.p. een geldig telefoonnummer in"
				value="<?php echo esc_attr( $this->data['input']['telnr'] ); ?>" autocomplete="tel" />
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het formulier
	 */
	private function workshopinfo() {
		?>
		<div class="kleistad-row" >
			<div class="kleistad-col-10">
				<label class="kleistad-label">Hoeveel deelnemers verwacht je ?</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-4 kleistad-label" >
				<input name="omvang" id="kleistad_klein" type="radio" required value="6 of minder" <?php checked( $this->data['input']['omvang'], '6 of minder' ); ?> >
				<label for="kleistad_klein" >6 of minder</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-4 kleistad-label" >
				<input name="omvang" id="kleistad_middel" type="radio" required value="tussen 7 en 12" <?php checked( $this->data['input']['omvang'], 'tussen 7 en 12' ); ?> >
				<label for="kleistad_middel" >tussen 7 en 12</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-4 kleistad-label" >
				<input name="omvang" id="kleistad_groot" type="radio" required value="meer dan 12" <?php checked( $this->data['input']['omvang'], 'meer dan 12' ); ?> >
				<label for="kleistad_groot" >meer dan 12</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-10">
				<label class="kleistad-label">Wanneer verwacht je dat het moet plaatsvinden ?</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-4 kleistad-label" >
				<input name="periode" id="kleistad_kt" type="radio" required value="binnen 1 maand" <?php checked( $this->data['input']['omvang'], '6 of minder' ); ?> >
				<label for="kleistad_kt" >binnen 1 maand</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-4 kleistad-label" >
				<input name="periode" id="kleistad_mt" type="radio" required value="tussen 1 en 2 maanden" <?php checked( $this->data['input']['omvang'], 'tussen 1 en 2 maanden' ); ?> >
				<label for="kleistad_mt" >tussen 1 en 2 maanden</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-4 kleistad-label" >
				<input name="periode" id="kleistad_lt" type="radio" required value="over 3 maanden of later" <?php checked( $this->data['input']['omvang'], 'over 3 maanden of later' ); ?> >
				<label for="kleistad_lt" >over 3 maanden of later</label>
			</div>
		</div>
		<div class ="kleistad-row" title="Heb je nadere vragen, stel ze gerust. Of laat hier opmerkingen achter die van belang zouden kunnen zijn voor Kleistad" >
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_vraag">Wil je iets vragen of wil je iets delen ?</label>
			</div>
			<div class="kleistad-col-7 kleistad-input">
				<textarea class="kleistad-input" name="vraag" id="kleistad_vraag" maxlength="1000" rows="5" cols="50"><?php echo esc_textarea( $this->data['input']['vraag'] ); ?></textarea>
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-10">
				<button class="kleistad-button" name="kleistad_submit_workshop_aanvraag" id="kleistad_submit" type="submit" >Verzenden</button>
			</div>
		</div>
		<?php
	}

}
