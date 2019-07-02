<?php
/**
 * Toon het workshop aanvraag formulier
 *
 * @link       https://www.kleistad.nl
 * @since      5.6.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

?>

<form method="POST" autocomplete="off">
	<?php wp_nonce_field( 'kleistad_workshop_aanvraag' ); ?>
	<div class="kleistad_row" >
		<div class="kleistad_col_5">
			<label class="kleistad_label">Wil je een vraag stellen over een</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_3 kleistad_label" >
			<input class="kleistad_input_cbr" name="naam" id="kleistad_kinderfeest" type="radio" required value="kinderfeest" <?php checked( $data['input']['naam'], 'kinderfeest' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_kinderfeest" >Kinderfeest</label>
		</div>
		<div class="kleistad_col_3 kleistad_label" >
			<input class="kleistad_input_cbr" name="naam" id="kleistad_workshop" type="radio" required value="workshop" <?php checked( $data['input']['naam'], 'workshop' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_workshop" >Workshop</label>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_contact">Naam</label>
		</div>
		<div class="kleistad_col_4">
			<input class="kleistad_input" name="contact" id="kleistad_contact" type="text"
			required maxlength="25" placeholder="naam" title="Vul s.v.p. je naam in"
			value="<?php echo esc_attr( $data['input']['contact'] ); ?>" autocomplete="given-name" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_emailadres">Email adres</label>
		</div>
		<div class="kleistad_col_4">
			<input class="kleistad_input" name="email" id="kleistad_emailadres" type="email"
			required placeholder="mijnemailadres@voorbeeld.nl" pattern="^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$"
			title="Vul s.v.p. een geldig email adres in"
			value="<?php echo esc_attr( $data['input']['email'] ); ?>" autocomplete="home mail" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_emailadres_controle">Email adres (controle)</label>
		</div>
		<div class="kleistad_col_4">
			<input class="kleistad_input" name="email_controle" id="kleistad_emailadres_controle" type="email"
			required title="Vul ter controle s.v.p. opnieuw het email adres in"
			value="<?php echo esc_attr( $data['input']['email_controle'] ); ?>"
			oninput="validate_email(this, kleistad_emailadres);"/>
		</div>
	</div>
	<script type="text/javascript">
		function validate_email( input, compare ) {
			input.setCustomValidity( ( input.value === compare.value ) ? '' : 'E-mailadressen zijn niet gelijk' );
		}
	</script>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_telefoon">Telefoon</label>
		</div>
		<div class="kleistad_col_2">
			<input class="kleistad_input" name="telnr" id="kleistad_telefoon" type="text"
			maxlength="15" placeholder="0123456789" title="Vul s.v.p. een geldig telefoonnummer in"
			value="<?php echo esc_attr( $data['input']['telnr'] ); ?>" autocomplete="tel" />
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_10">
			<label class="kleistad_label">Hoeveel deelnemers verwacht je ?</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_4 kleistad_label" >
			<input class="kleistad_input_cbr" name="omvang" id="kleistad_klein" type="radio" required value="6 of minder" <?php checked( $data['input']['omvang'], '6 of minder' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_klein" >6 of minder</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_4 kleistad_label" >
			<input class="kleistad_input_cbr" name="omvang" id="kleistad_middel" type="radio" required value="tussen 7 en 12" <?php checked( $data['input']['omvang'], 'tussen 7 en 12' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_middel" >tussen 7 en 12</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_4 kleistad_label" >
			<input class="kleistad_input_cbr" name="omvang" id="kleistad_groot" type="radio" required value="meer dan 12" <?php checked( $data['input']['omvang'], 'meer dan 12' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_groot" >meer dan 12</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_10">
			<label class="kleistad_label">Wanneer verwacht je dat het moet plaatsvinden ?</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_4 kleistad_label" >
			<input class="kleistad_input_cbr" name="periode" id="kleistad_kt" type="radio" required value="binnen 1 maand" <?php checked( $data['input']['omvang'], '6 of minder' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_kt" >binnen 1 maand</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_4 kleistad_label" >
			<input class="kleistad_input_cbr" name="periode" id="kleistad_mt" type="radio" required value="tussen 1 en 2 maanden" <?php checked( $data['input']['omvang'], 'tussen 1 en 2 maanden' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_mt" >tussen 1 en 2 maanden</label>
		</div>
	</div>
	<div class="kleistad_row" >
		<div class="kleistad_col_1" >
		</div>
		<div class="kleistad_col_4 kleistad_label" >
			<input class="kleistad_input_cbr" name="periode" id="kleistad_lt" type="radio" required value="over 3 maanden of later" <?php checked( $data['input']['omvang'], 'over 3 maanden of later' ); ?> >
			<label class="kleistad_label_cbr" for="kleistad_lt" >over 3 maanden of later</label>
		</div>
	</div>
	<div class ="kleistad_row" title="Heb je nadere vragen, stel ze gerust. Of laat hier hier vraagen achter die van belang zouden kunnen zijn voor Kleistad" >
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_vraag">Wil je iets vragen of wil je iets delen ?</label>
		</div>
		<div class="kleistad_col_7 kleistad_input">
			<textarea class="kleistad_input" name="vraag" id="kleistad_vraag" rows="5" cols="50"><?php echo esc_textarea( $data['input']['vraag'] ); ?></textarea>
		</div>
	</div>
	<div class="kleistad_row" style="padding-top:20px;">
		<div class="kleistad_col_10">
			<button name="kleistad_submit_workshop_aanvraag" id="kleistad_submit" type="submit" >Aanvragen</button>
		</div>
	</div>
</form>
