<?php
/**
 * Toon de omzetrapportage
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de omzetrapportage.
 */
class Public_Registratie_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		if ( 'wachtwoord' === $this->data['actie'] ) {
			$this->wachtwoord();
			return;
		}
		$this->form()->details()->form_end();
	}

	/**
	 * Render de details
	 *
	 * @return Public_Registratie_Display
	 */
	private function details() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_voornaam">Naam</label>
			</div>
			<div class="kleistad-col-3">
				<input class="kleistad-input" name="first_name" id="kleistad_voornaam" type="text" required maxlength="25" placeholder="voornaam" value="<?php echo esc_attr( $this->data['input']['first_name'] ); ?>" />
			</div>
			<div class="kleistad-col-4">
				<<!--suppress HtmlFormInputWithoutLabel -->input class="kleistad-input" name="last_name" id="kleistad_achternaam" type="text" required maxlength="25" placeholder="achternaam" value="<?php echo esc_attr( $this->data['input']['last_name'] ); ?>" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_telnr">Telefoon</label>
			</div>
			<div class="kleistad-col-7">
				<input class="kleistad-input" name="telnr" id="kleistad_telnr" type="text" maxlength="15" placeholder="0123456789" value="<?php echo esc_attr( $this->data['input']['telnr'] ); ?>" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_straat">Straat, nr</label>
			</div>
			<div class="kleistad-col-5">
				<input class="kleistad-input" name="straat" id="kleistad_straat" type="text" required placeholder="straat" maxlength="50" value="<?php echo esc_attr( $this->data['input']['straat'] ); ?>" />
			</div>
			<div class="kleistad-col-2">
				<<!--suppress HtmlFormInputWithoutLabel -->input class="kleistad-input" name="huisnr" id="kleistad_huisnr" type="text" maxlength="10" required placeholder="nr" value="<?php echo esc_attr( $this->data['input']['huisnr'] ); ?>" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_pcode">Postcode, Plaats</label>
			</div>
			<div class="kleistad-col-2">
				<input class="kleistad-input" name="pcode" id="kleistad_pcode" type="text" maxlength="10" placeholder="1234AB" pattern="^[1-9][0-9]{3} ?[a-zA-Z]{2}$" title="1234AB" value="<?php echo esc_attr( $this->data['input']['pcode'] ); ?>" />
			</div>
			<div class="kleistad-col-5">
				<<!--suppress HtmlFormInputWithoutLabel -->input class="kleistad-input" name="plaats" id="kleistad_plaats" value="<?php echo esc_attr( $this->data['input']['plaats'] ); ?>"
					type="text" required maxlength="50" placeholder="MijnWoonplaats" pattern="^[a-zA-Z-'\s]+$"
					oninvalid="setCustomValidity('Een geldige plaatsnaam wordt verwacht')" oninput="setCustomValidity('')" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_email">E-mail adres</label>
			</div>
			<div class="kleistad-col-7">
				<input class="kleistad-input" name="user_email" id="kleistad_email" type="email" required value="<?php echo esc_attr( $this->data['input']['user_email'] ); ?>" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<button class="kleistad-button" name="kleistad_submit_registratie" value="wijzigen" type="submit" id="kleistad_submit">Opslaan</button>
				<button class="kleistad-button kleistad-edit-link" data-actie="wachtwoord" type="button" >Wachtwoord wijzigen</button>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de details
	 *
	 * @return Public_Registratie_Display
	 */
	private function wachtwoord() {
		?>
		<div id="kleistad_wachtwoord_succes" style="display:none" >
			<?php echo melding( 1, 'Het wachtwoord is gewijzigd' ); // phpcs:ignore ?>
			<?php $this->home(); // phpcs:ignore ?>
		</div>
		<div id="kleistad_wachtwoord_fout" style="display:none" >
			<?php echo melding( 0, 'Er is iets fout gegaan, probeer het opnieuw' ); // phpcs:ignore ?>
		</div>
		<div id="kleistad_wachtwoord_form">
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="nieuw_wachtwoord">Nieuw wachtwoord</label>
				</div>
				<div class="kleistad-col-3">
					<input id="nieuw_wachtwoord" type="password" name="nieuw_wachtwoord" placeholder="" required>
				</div>
			</div>
			<div class="kleistad-row wp-pwd">
				<div class="kleistad-col-3 kleistad-label">
					<label for="bevestig_nieuw_wachtwoord">Bevestig nieuw wachtwoord</label>
				</div>
				<div class="kleistad-col-3">
					<input id="bevestig_nieuw_wachtwoord" type="password" name="bevestig_wachtwoord" placeholder="" required>
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3">
				</div>
				<div id="wachtwoord_sterkte" class="kleistad-col-3 kleistad-pwd-meter">
					&nbsp;
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3">
				</div>
				<div class="kleistad-col-6" style="font-size:13px;" >
					<?php echo esc_html( apply_filters( 'password_hint', '' ) ); ?>
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-10">
					<button class="kleistad-button" type="button" disabled id="kleistad_wachtwoord" >Wijzigen</button>
					<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

}
