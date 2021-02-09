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
	<div class="kleistad-row">
		<div class="kleistad-col-4 kleistad-label">
			<label for="kleistad_naam">Je naam (verplicht)</label>
		</div>
		<div class="kleistad-col-6">
			<input class="kleistad-input" name="naam" id="kleistad_contact" type="text"
			required maxlength="25" placeholder="naam" title="Vul s.v.p. je naam in"
			value="<?php echo esc_attr( $data['input']['naam'] ); ?>" autocomplete="given-name" />
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
			value="<?php echo esc_attr( $data['input']['email'] ); ?>" autocomplete="email" />
		</div>
	</div>
	<div class="kleistad-row">
		<div class="kleistad-col-4 kleistad-label">
			<label for="kleistad_telefoon">Telefoon</label>
		</div>
		<div class="kleistad-col-6">
			<input class="kleistad-input" name="telnr" id="kleistad_telefoon" type="text"
			maxlength="15" placeholder="0123456789" title="Vul s.v.p. een geldig telefoonnummer in"
			value="<?php echo esc_attr( $data['input']['telnr'] ); ?>" autocomplete="tel" />
		</div>
	</div>
	<div class="kleistad-row" >
		<div class="kleistad-col-10">
			<label class="kleistad-label">Onderwerp</label>
		</div>
	</div>
	<div class="kleistad-row" >
		<div class="kleistad-col-1" >
		</div>
		<div class="kleistad-col-9 kleistad-label" >
			<input name="onderwerp" id="kleistad_cursus" type="radio" required value="cursus" <?php checked( $data['input']['onderwerp'], 'cursus' ); ?> >
			<label for="kleistad_cursus" >Cursus</label>
		</div>
	</div>
	<div class="kleistad-row" >
		<div class="kleistad-col-1" >
		</div>
		<div class="kleistad-col-9 kleistad-label" >
			<input name="onderwerp" id="kleistad_abonnement" type="radio" required value="abonnement" <?php checked( $data['input']['onderwerp'], 'abonnement' ); ?> >
			<label for="kleistad_abonnement" >Abonnement</label>
		</div>
	</div>
	<div class="kleistad-row" >
		<div class="kleistad-col-1" >
		</div>
		<div class="kleistad-col-9 kleistad-label" >
			<input name="onderwerp" id="kleistad_stook" type="radio" required value="stook" <?php checked( $data['input']['onderwerp'], 'stook' ); ?> >
			<label for="kleistad_stook" >Stook</label>
		</div>
	</div>
	<div class="kleistad-row" >
		<div class="kleistad-col-1" >
		</div>
		<div class="kleistad-col-9 kleistad-label" >
			<input name="onderwerp" id="kleistad_overig" type="radio" required value="overig" <?php checked( $data['input']['onderwerp'], 'overig' ); ?> >
			<label for="kleistad_overig" >Overig</label>
		</div>
	</div>
	<div class ="kleistad-row" >
		<div class="kleistad-col-4 kleistad-label">
			<label for="kleistad_vraag">Je vraag</label>
		</div>
	</div>
	<div class ="kleistad-row" title="Geef aan wat je vraag is of wat je ons wilt mededelen" >
		<div class="kleistad-col-10 kleistad-input">
			<textarea class="kleistad-input" name="vraag" id="kleistad_vraag" maxlength="1000" rows="5" cols="50" required ><?php echo esc_textarea( $data['input']['vraag'] ); ?></textarea>
		</div>
	</div>
	<div class="kleistad-row" style="padding-top:20px;">
		<div class="kleistad-col-10">
			<button name="kleistad_submit_contact" id="kleistad_submit" type="submit" >Verzenden</button>
		</div>
	</div>
</form>
