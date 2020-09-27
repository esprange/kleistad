<?php
/**
 * Subformulier voor registratie van gebruiker gegevens (wordt in andere formulieren ingevoegd)
 *
 * De specifieke velden voor het registreren van een gebruiker.
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

?>

<div class="kleistad_row">
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_voornaam">Voornaam</label>
	</div>
	<div class="kleistad_col_4">
		<input class="kleistad_input" name="first_name" id="kleistad_voornaam" type="text"
		required maxlength="25" placeholder="voornaam" title="Vul s.v.p. de voornaam in"
		value="<?php echo esc_attr( $data['input']['first_name'] ); ?>" autocomplete="given-name" />
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_achternaam">Achternaam</label>
	</div>
	<div class="kleistad_col_4">
		<input class="kleistad_input" name="last_name" id="kleistad_achternaam" type="text"
		required maxlength="25" placeholder="achternaam" title="Vul s.v.p. de achternaam in"
		value="<?php echo esc_attr( $data['input']['last_name'] ); ?>" autocomplete="family-name" />
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_emailadres">Email adres</label>
	</div>
	<div class="kleistad_col_4">
		<input class="kleistad_input" name="user_email" id="kleistad_emailadres" type="email"
		required placeholder="mijnemailadres@voorbeeld.nl" pattern="^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$"
		title="Vul s.v.p. een geldig email adres in"
		value="<?php echo esc_attr( $data['input']['user_email'] ); ?>" autocomplete="email" />
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_emailadres_controle">Email adres (controle)</label>
	</div>
	<div class="kleistad_col_4">
		<input class="kleistad_input" name="email_controle" id="kleistad_emailadres_controle" type="email"
		required title="Vul ter controle s.v.p. opnieuw het email adres in"
		value="<?php echo esc_attr( $data['input']['email_controle'] ); ?>" />
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_telnr">Telefoon</label>
	</div>
	<div class="kleistad_col_2">
		<input class="kleistad_input" name="telnr" id="kleistad_telnr" type="text"
		maxlength="15" placeholder="0123456789" title="Vul s.v.p. een geldig telefoonnummer in"
		value="<?php echo esc_attr( $data['input']['telnr'] ); ?>" autocomplete="tel" />
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_pcode">Postcode, huisnummer</label>
	</div>
	<div class="kleistad_col_2">
		<input class="kleistad_input" name="pcode" id="kleistad_pcode" type="text"
			maxlength="10" placeholder="1234AB" pattern="^[1-9][0-9]{3}?[A-Z]{2}$" title="Vul s.v.p. een geldige Nederlandse postcode in"
			value="<?php echo esc_attr( $data['input']['pcode'] ); ?>" autocomplete="postal-code" />
	</div>
	<div class="kleistad_col_2">
		<input class="kleistad_input" name="huisnr" id="kleistad_huisnr" type="text"
			maxlength="10" placeholder="nr" title="Vul s.v.p. een huisnummer in"
			value="<?php echo esc_attr( $data['input']['huisnr'] ); ?>" />
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_straat">Straat, Plaats</label>
	</div>
	<div class="kleistad_col_3">
		<input class="kleistad_input" name="straat" id="kleistad_straat" type="text" tabindex="-1"
		maxlength="50" placeholder="straat" title="Vul s.v.p. een straatnaam in"
		value="<?php echo esc_attr( $data['input']['straat'] ); ?>" />
	</div>
	<div class="kleistad_col_4">
		<input class="kleistad_input" name="plaats" id="kleistad_plaats" type="text" tabindex="-1"
		maxlength="50" placeholder="MijnWoonplaats" title="Vul s.v.p. de woonplaats in"
		value="<?php echo esc_attr( $data['input']['plaats'] ); ?>" />
	</div>
</div>
<?php if ( ! is_super_admin() ) : ?>
<div class ="kleistad_row" title="Wat is je ervaring met klei? Je kunt hier ook andere opmerkingen achterlaten die van belang zouden kunnen zijn voor Kleistad" >
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_opmerking">Opmerking</label>
	</div>
	<div class="kleistad_col_7 kleistad_input">
		<textarea class="kleistad_input" name="opmerking" id="kleistad_opmerking" rows="3" cols="50"><?php echo esc_textarea( $data['input']['opmerking'] ); ?></textarea>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_10">
		<input type="checkbox" class="kleistad_input_cb" name="mc4wp-subscribe" id="subscribe" value="1" <?php checked( $data['input']['mc4wp-subscribe'], '1' ); ?> />
		<label class="kleistad_label_cb" for="subscribe">Ik wil de Kleistad nieuwsbrief ontvangen.</label>
	</div>
</div>
<?php endif ?>
<?php if ( ( isset( $data['verklaring'] ) && '' !== $data['verklaring'] ) ) : ?>
	<div class="kleistad_row">
		<div class="kleistad_col_10">
			<input type="checkbox" class="kleistad_input_cb" id="verklaring" onchange="document.getElementById( 'kleistad_submit' ).disabled = !this.checked;" />
			<label class="kleistad_label_cb" for="verklaring"><?php echo $data['verklaring']; // phpcs:ignore ?></label>
		</div>
	</div>
<?php endif ?>
