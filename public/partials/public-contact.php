<?php
/**
 * Toon het contact formulier
 *
 * @link       https://www.kleistad.nl
 * @since      6.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

namespace Kleistad;

$this->form();
?>
	<div class="kleistad_row">
		<div class="kleistad_col_4 kleistad_label">
			<label for="kleistad_naam">Je naam (verplicht)</label>
		</div>
		<div class="kleistad_col_6">
			<input class="kleistad_input" name="naam" id="kleistad_contact" type="text"
			required maxlength="25" placeholder="naam" title="Vul s.v.p. je naam in"
			value="<?php echo esc_attr( $data['input']['naam'] ); ?>" autocomplete="given-name" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_4 kleistad_label">
			<label for="kleistad_emailadres">Je email adres (verplicht)</label>
		</div>
		<div class="kleistad_col_6">
			<input class="kleistad_input" name="email" id="kleistad_emailadres" type="email"
			required placeholder="mijnemailadres@voorbeeld.nl" pattern="^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$"
			title="Vul s.v.p. een geldig email adres in"
			value="<?php echo esc_attr( $data['input']['email'] ); ?>" autocomplete="email" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_4 kleistad_label">
			<label for="kleistad_telefoon">Telefoon</label>
		</div>
		<div class="kleistad_col_6">
			<input class="kleistad_input" name="telnr" id="kleistad_telefoon" type="text"
			maxlength="15" placeholder="0123456789" title="Vul s.v.p. een geldig telefoonnummer in"
			value="<?php echo esc_attr( $data['input']['telnr'] ); ?>" autocomplete="tel" />
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_10">
			<label class="kleistad_label">Onderwerp</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_9 kleistad_label" >
			<input class="kleistad_input_cbr" name="onderwerp" id="kleistad_cursus" type="radio" required value="cursus" <?php checked( $data['input']['onderwerp'], 'cursus' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_cursus" >Cursus</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_9 kleistad_label" >
			<input class="kleistad_input_cbr" name="onderwerp" id="kleistad_abonnement" type="radio" required value="abonnement" <?php checked( $data['input']['onderwerp'], 'abonnement' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_abonnement" >Abonnement</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_9 kleistad_label" >
			<input class="kleistad_input_cbr" name="onderwerp" id="kleistad_stook" type="radio" required value="stook" <?php checked( $data['input']['onderwerp'], 'stook' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_stook" >Stook</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_9 kleistad_label" >
			<input class="kleistad_input_cbr" name="onderwerp" id="kleistad_overig" type="radio" required value="overig" <?php checked( $data['input']['onderwerp'], 'overig' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_overig" >Overig</label>
		</div>
	</div>
	<div class ="kleistad_row" >
		<div class="kleistad_col_4 kleistad_label">
			<label for="kleistad_vraag">Je vraag</label>
		</div>
	</div>
	<div class ="kleistad_row" title="Geef aan wat je vraag is of wat je ons wilt mededelen" >
		<div class="kleistad_col_10 kleistad_input">
			<textarea class="kleistad_input" name="vraag" id="kleistad_vraag" maxlength="1000" rows="5" cols="50" required ><?php echo esc_textarea( $data['input']['vraag'] ); ?></textarea>
		</div>
	</div>
	<div class="kleistad_row" style="padding-top:20px;">
		<div class="kleistad_col_10">
			<button name="kleistad_submit_contact" id="kleistad_submit" type="submit" >Verzenden</button>
		</div>
	</div>
</form>
