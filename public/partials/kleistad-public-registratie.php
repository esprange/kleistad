<?php
/**
 * Toon het registratie formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

if ( ! is_user_logged_in() ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

	<form method="POST">
		<?php wp_nonce_field( 'kleistad_registratie' ); ?>
		<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( $data['input']['gebruiker_id'] ); ?>" >
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
			<div class="kleistad_col_10">
				<button name="kleistad_submit_registratie" type="submit" >Opslaan</button>
			</div>
		</div>
	</form>
<?php endif ?>
