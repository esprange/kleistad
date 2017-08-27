<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
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

<form action="<?php echo get_permalink(); ?>" method="POST">
	<?php wp_nonce_field( 'kleistad_abonnee_inschrijving' ); ?>
	<table class="kleistad_form" >
		<tr>
			<td><label for="kleistad_abonnement_keuze">Keuze abonnement</label></td>
			<td><input type="radio" name="abonnement_keuze" id="kleistad_abonnement_keuze" value="onbeperkt" />Onbeperkt</td>
			<td><input type="radio" name="abonnement_keuze" value="beperkt" />Beperkt</td>
			<td></td>
		</tr>
		<tr title="kies de dag dat je van jouw beperkt abonnement gebruikt gaat maken" >
			<td></td>
			<td style="visibility:hidden" id="kleistad_dag"><select name="dag" >
					<option value="maandag" selected="selected">Maandag</option>
					<option value="dinsdag" >Dinsdag</option>
					<option value="woensdag" >Woensdag</option>
					<option value="donderdag" >Donderdag</option>
					<option value="vrijdag" >Vrijdag</option>
				</select></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td><label for="kleistad_start_datum">Start abonnement</label></td>
			<td><input id="kleistad_start_datum" class="kleistad_datum" type="text" name="start_datum" value="<?php echo date( 'd-m-Y' ); ?>" /></td>
			<td colspan="2"></td>
		</tr>
		<?php if ( is_super_admin() ) : ?>
		<tr>
			<td><label for="kleistad_gebruiker_id">Abonnee</label></td>
			<td colspan="2">
				<select id="kleistad_gebruiker_id" name="gebruiker_id" >      
					<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
					<option value="<?php echo $gebruiker->id; ?>"><?php echo $gebruiker->display_name; ?></option>
					<?php endforeach ?>
				</select>
			</td>
			<td></td>
		</tr>
		<?php else : ?> 
		<tr>
			<td><label for="kleistad_voornaam">Naam</label></td>
			<td><input type="text" name="voornaam" id="kleistad_voornaam" required maxlength="25" placeholder="voornaam" value="<?php echo $data['input']['voornaam']; ?>" /></td>
			<td colspan="2" ><input type="text" name="achternaam" id="kleistad_achternaam" required maxlength="25" placeholder="achternaam" value="<?php echo $data['input']['achternaam']; ?>" /></td>
		</tr>
		<tr>
			<td><label for="kleistad_emailadres">Email adres</label></td>
			<td colspan="3" ><input type="email" name="emailadres" id="kleistad_emailadres" required placeholder="mijnemailadres@voorbeeld.nl" value="<?php echo $data['input']['emailadres']; ?>" /></td>
		</tr>
		<tr>
			<td><label for="kleistad_telnr">Telefoon</label></td>
			<td colspan="3" ><input type="text" name="telnr" id="kleistad_telnr" maxlength="15" placeholder="0123456789" value="<?php echo $data['input']['telnr']; ?>" /></td>
		</tr>
		<tr>    
			<td><label for="kleistad_straat">Straat, nr</label></td>
			<td colspan="2" ><input type="text" name="straat" id="kleistad_straat" required placeholder="straat" maxlength="50" value="<?php echo $data['input']['straat']; ?>" /></td>
			<td><input type="text" name="huisnr" id="kleistad_huisnr" maxlength="10" placeholder="nr" value="<?php echo $data['input']['huisnr']; ?>" /></td>
		</tr>
		<tr>
			<td><label for="kleistad_pcode">Postcode, Plaats</label></td>
			<td><input type="text" name="pcode" id="kleistad_pcode" maxlength="10" placeholder="1234AB" pattern="[1-9][0-9]{3}\s?[a-zA-Z]{2}" title="1234AB" value="<?php echo $data['input']['pcode']; ?>" /></td>
			<td colspan="2" ><input type="text" name="plaats" id="kleistad_plaats" required maxlength="50" placeholder="MijnWoonplaats" value="<?php echo $data['input']['plaats']; ?>" /></td>
		</tr>
		<?php endif ?>
		<tr title="Wat is je ervaring met klei? Je kunt hier ook andere opmerkingen achterlaten die van belang zouden kunnen zijn voor Kleistad" >
			<td><label for="kleistad_opmerking_veld">Opmerking</label></td>
			<td colspan="3" ><textarea name="opmerking" id="kleistad_opmerking_veld" rows="5" cols="50"><?php echo $data['input']['opmerking']; ?></textarea>
			</td>
		</tr>
	</table>
	<button type="submit" name="kleistad_submit_abonnee_inschrijving" id="kleistad_submit_abonnee_inschrijving" >Verzenden</button>
</form>

<?php endif ?>
