<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( is_user_logged_in() && ! is_super_admin() ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

	<form class="kleistad_formuler" action="<?php echo esc_url( get_permalink() ); ?>" method="POST">
		<?php wp_nonce_field( 'kleistad_abonnee_inschrijving' ); ?>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<label class="kleistad_label">Keuze abonnement</label>
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" name="abonnement_keuze" id="kleistad_onbeperkt" type="radio" checked required value="onbeperkt" <?php checked( 'onbeperkt', $data['input']['abonnement_keuze'] ); ?> />
				<label class="kleistad_label_cbr" for="kleistad_onbeperkt" >Onbeperkt</label>
			</div>
			<div class="kleistad_col_1">
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" name="abonnement_keuze" id="kleistad_beperkt" type="radio" required value="beperkt" <?php checked( 'beperkt', $data['input']['abonnement_keuze'] ); ?> />
				<label class="kleistad_label_cbr" for="kleistad_beperkt">Beperkt</label>
			</div>
		</div>
		<div class="kleistad_row" id="kleistad_dag" style="visibility:hidden" title="kies de dag dat je van jouw beperkt abonnement gebruikt gaat maken" >
			<div class="kleistad_label kleistad_col_3">
				<label for="kleistad_dag_keuze">Dag</label>
			</div>
			<div class ="kleistad_col_7">
				<select class="kleistad_input" name="dag" id="kleistad_dag_keuze" >
					<option value="maandag" <?php selected( $data['input']['dag'], 'maandag' ); ?> >Maandag</option>
					<option value="dinsdag" <?php selected( $data['input']['dag'], 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $data['input']['dag'], 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $data['input']['dag'], 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $data['input']['dag'], 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_3">
				<label for="kleistad_start_datum">Start per</label>
			</div>
			<div class="kleistad_input kleistad_col_7">
				<input class="kleistad_datum, kleistad_input" name="start_datum" id="kleistad_start_datum" type="text" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>" />
			</div>
		</div>
		<?php if ( is_super_admin() ) : ?>
			<div class="kleistad_row">
				<div class="kleistad_label kleistad_col_3">
					<label for="kleistad_gebruiker_id">Abonnee</label>
				</div>
				<div class="kleistad_col_7">
					<select class="kleistad_input" name="gebruiker_id" id="kleistad_gebruiker_id" >      
						<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
							<option value="<?php echo esc_attr( $gebruiker->id ); ?>"><?php echo esc_html( $gebruiker->display_name ); ?></option>
						<?php endforeach ?>
					</select>
				</div>
			</div>
		<?php else : ?> 
			<div class="kleistad_row">
				<div class="kleistad_label kleistad_col_3">
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
				<div class="kleistad_label kleistad_col_3">
					<label for="kleistad_emailadres">Email adres</label>
				</div>
				<div class="kleistad_col_7">
					<input class="kleistad_input" name="EMAIL" id="kleistad_emailadres" type="email" required placeholder="mijnemailadres@voorbeeld.nl" value="<?php echo esc_attr( $data['input']['EMAIL'] ); ?>" />
				</div>
			</div>
			<div class="kleistad_row">
				<div class="kleistad_label kleistad_col_3">
					<label for="kleistad_telnr">Telefoon</label>
				</div>
				<div class="kleistad_col_7">
					<input class="kleistad_input" name="telnr" id="kleistad_telnr" type="text" maxlength="15" placeholder="0123456789" value="<?php echo esc_attr( $data['input']['telnr'] ); ?>" />
				</div>
			</div>
			<div class="kleistad_row">
				<div class="kleistad_label kleistad_col_3">
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
				<div class="kleistad_label kleistad_col_3">
					<label for="kleistad_pcode">Postcode, Plaats</label>
				</div>
				<div class="kleistad_col_2">
					<input class="kleistad_input" name="pcode" id="kleistad_pcode" type="text" maxlength="10" placeholder="1234AB" pattern="[1-9][0-9]{3}\s?[a-zA-Z]{2}" title="1234AB" value="<?php echo esc_attr( $data['input']['pcode'] ); ?>" />
				</div>
				<div class="kleistad_col_5">
					<input class="kleistad_input" name="plaats" id="kleistad_plaats" type="text" required maxlength="50" placeholder="MijnWoonplaats" value="<?php echo esc_attr( $data['input']['plaats'] ); ?>" />
				</div>
			</div>
		<?php endif ?>
		<div class ="kleistad_row" title="Wat is je ervaring met klei? Je kunt hier ook andere opmerkingen achterlaten die van belang zouden kunnen zijn voor Kleistad" >
			<div class="kleistad_label kleistad_col_3">
				<label for="kleistad_opmerking">Opmerking</label>
			</div>
			<div class="kleistad_input kleistad_col_7">
				<textarea class="kleistad_input" name="opmerking" id="kleistad_opmerking" rows="5" cols="50"><?php echo esc_textarea( $data['input']['opmerking'] ); ?></textarea>
			</div>
		</div>
		<?php if ( ! is_super_admin() ) : ?>
			<div class="kleistad_row">
				<div class="kleistad_label kleistad_col_3">
					 <label for="subscribe"></label>
				</div>
				<div class="kleistad_col_7">
					<input type="checkbox" name="mc4wp-subscribe" id="subscribe" value="1" checked />
					Ik wil de Kleistad nieuwsbrief ontvangen.
				</div>
			</div>
			<?php if ( '' !== $data['verklaring'] ) : ?>
				<div class="kleistad_row">
					<div class="kleistad_label kleistad_col_3">
						 <label for="verklaring"></label>
					</div>
					<div class="kleistad_col_7">
						<input type="checkbox" id="verklaring" onchange="document.getElementById( 'kleistad_submit_abonnee_inschrijving' ).disabled = !this.checked;" />
						<?php echo $data['verklaring']; // WPCS: XSS ok. ?>
					</div>
				</div>
			<?php
			endif;
			endif
			?>
		<div class="kleistad_row">
			<div class="kleistad_col_10">
				<button name="kleistad_submit_abonnee_inschrijving" id="kleistad_submit_abonnee_inschrijving" type="submit" <?php echo ( '' !== $data['verklaring'] ? 'disabled' : '' ); ?>>Verzenden</button>
			</div>
		</div>
	</form>

<?php endif ?>
