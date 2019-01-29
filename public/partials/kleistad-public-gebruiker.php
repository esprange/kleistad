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
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

?>

	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_voornaam">Naam</label>
		</div>
		<div class="kleistad_col_3">
			<input class="kleistad_input" name="FNAME" id="kleistad_voornaam" type="text" required maxlength="25" placeholder="voornaam" value="<?php echo esc_attr( $data['input']['FNAME'] ); ?>" />
		</div>
		<div class="kleistad_col_4">
			<input class="kleistad_input" name="LNAME" id="kleistad_achternaam" type="text" required maxlength="25" placeholder="achternaam" value="<?php echo esc_attr( $data['input']['LNAME'] ); ?>" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_emailadres">Email adres</label>
		</div>
		<div class="kleistad_col_7">
			<input class="kleistad_input" name="EMAIL" id="kleistad_emailadres" type="email" required placeholder="mijnemailadres@voorbeeld.nl" value="<?php echo esc_attr( $data['input']['EMAIL'] ); ?>" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_emailadres_controle">Email adres (controle)</label>
		</div>
		<div class="kleistad_col_7">
			<input class="kleistad_input" name="email_controle" id="kleistad_emailadres_controle" type="email" required value="<?php echo esc_attr( $data['input']['email_controle'] ); ?>" />
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
			<input class="kleistad_input" name="pcode" id="kleistad_pcode" type="text" maxlength="10" placeholder="1234AB" pattern="[1-9][0-9]{3}\s?[a-zA-Z]{2}" title="1234AB" value="<?php echo esc_attr( $data['input']['pcode'] ); ?>" />
		</div>
		<div class="kleistad_col_5">
			<input class="kleistad_input" name="plaats" id="kleistad_plaats" type="text" required maxlength="50" placeholder="MijnWoonplaats" value="<?php echo esc_attr( $data['input']['plaats'] ); ?>" />
		</div>
	</div>
	<?php if ( ! is_super_admin() ) : ?>
	<div class ="kleistad_row" title="Wat is je ervaring met klei? Je kunt hier ook andere opmerkingen achterlaten die van belang zouden kunnen zijn voor Kleistad" >
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_opmerking">Opmerking</label>
		</div>
		<div class="kleistad_col_7 kleistad_input">
			<textarea class="kleistad_input" name="opmerking" id="kleistad_opmerking" rows="5" cols="50"><?php echo esc_textarea( $data['input']['opmerking'] ); ?></textarea>
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
