<?php
/**
 * Toon het registratie formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

$this->form();
if ( 'wachtwoord' === $data['actie'] ) :
	?>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="huidig_wachtwoord">Voer het huidig wachtwoord in</label>
		</div>
		<div class="kleistad_col_3">
			<input id="huidig_wachtwoord" type="password" name="huidig_wachtwoord" placeholder="" required>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="nieuw_wachtwoord">Nieuw wachtwoord</label>
		</div>
		<div class="kleistad_col_3">
			<input id="nieuw_wachtwoord" type="password" name="nieuw_wachtwoord" placeholder="" required>
		</div>
	</div>
	<div class="kleistad_row wp-pwd">
		<div class="kleistad_col_3 kleistad_label">
			<label for="bevestig_nieuw_wachtwoord">Bevestig nieuw wachtwoord</label>
		</div>
		<div class="kleistad_col_3">
			<input id="bevestig_nieuw_wachtwoord" type="password" name="bevestig_wachtwoord" placeholder="" required>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3">
		</div>
		<div id="wachtwoord_sterkte" class="kleistad_col_3 kleistad_pwd_meter">
			&nbsp;
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3">
		</div>
		<div class="kleistad_col_6" style="font-size:13px;" >
			<?php echo esc_html( apply_filters( 'password_hint', '' ) ); ?>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_10">
			<button type="submit" disabled name="kleistad_sumbmit_registratie" value="wachtwoord">Wijzigen</button>
			<button type="button" style="position:absolute;right:0px;" class="kleistad_terug_link">Terug</button>
		</div>
	</div>
	<?php
else :
	?>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_voornaam">Naam</label>
		</div>
		<div class="kleistad_col_3">
			<input class="kleistad_input" name="voornaam" id="kleistad_voornaam" type="text" required maxlength="25" placeholder="voornaam" value="<?php echo esc_attr( $data['input']['voornaam'] ); ?>" />
		</div>
		<div class="kleistad_col_4">
			<input class="kleistad_input" name="achternaam" id="kleistad_achternaam" type="text" required maxlength="25" placeholder="achternaam" value="<?php echo esc_attr( $data['input']['achternaam'] ); ?>" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_telnr">Telefoon</label>
		</div>
		<div class="kleistad_col_7">
			<input class="kleistad_input" name="telnr" id="kleistad_telnr" type="text" maxlength="15" placeholder="0123456789" value="<?php echo esc_attr( $data['input']['telnr'] ); ?>" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_straat">Straat, nr</label>
		</div>
		<div class="kleistad_col_5">
			<input class="kleistad_input" name="straat" id="kleistad_straat" type="text" required placeholder="straat" maxlength="50" value="<?php echo esc_attr( $data['input']['straat'] ); ?>" />
		</div>
		<div class="kleistad_col_2">
			<input class="kleistad_input" name="huisnr" id="kleistad_huisnr" type="text" maxlength="10" required placeholder="nr" value="<?php echo esc_attr( $data['input']['huisnr'] ); ?>" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_pcode">Postcode, Plaats</label>
		</div>
		<div class="kleistad_col_2">
			<input class="kleistad_input" name="pcode" id="kleistad_pcode" type="text" maxlength="10" placeholder="1234AB" pattern="^[1-9][0-9]{3} ?[a-zA-Z]{2}$" title="1234AB" value="<?php echo esc_attr( $data['input']['pcode'] ); ?>" />
		</div>
		<div class="kleistad_col_5">
			<input class="kleistad_input" name="plaats" id="kleistad_plaats" value="<?php echo esc_attr( $data['input']['plaats'] ); ?>"
				type="text" required maxlength="50" placeholder="MijnWoonplaats" pattern="^[a-zA-Z-'\s]+$"
				oninvalid="setCustomValidity('Een geldige plaatsnaam wordt verwacht')" oninput="setCustomValidity('')" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_email">E-mail adres</label>
		</div>
		<div class="kleistad_col_7">
			<input class="kleistad_input" name="email" id="kleistad_email" type="email" required value="<?php echo esc_attr( $data['input']['email'] ); ?>" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_10">
			<button name="kleistad_submit_registratie" value="wijzigen" type="submit" >Opslaan</button>
			<button class="kleistad_edit_link" data-actie="wachtwoord" type="button" >Wachtwoord wijzigen</button>
		</div>
	</div>
<?php endif ?>
</form>
