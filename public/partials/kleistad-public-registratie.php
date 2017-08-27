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
if ( ! is_user_logged_in() ) :
	?>
  <p>Geen toegang tot dit formulier</p>
<?php
else :
	extract( $data );
?>

  <form action="<?php echo get_permalink(); ?>" method="POST">
		<?php wp_nonce_field( 'kleistad_registratie' ); ?>
	  <input type="hidden" name="gebruiker_id" value="<?php echo get_current_user_id(); ?>" >
	  <table class="kleistad_form" >
		  <tr>
			  <td><label for="kleistad_voornaam">Naam</label></td>
			  <td><input type="text" name="voornaam" id="kleistad_voornaam" required maxlength="25" placeholder="voornaam" value="<?php echo $input['voornaam']; ?>" /></td>
			  <td colspan="2" ><input type="text" name="achternaam" id="kleistad_achternaam" required maxlength="25" placeholder="achternaam" value="<?php echo $input['achternaam']; ?>" /></td>
		  </tr>
		  <tr>
			  <td><label for="kleistad_telnr">Telefoon</label></td>
			  <td colspan="3" ><input type="text" name="telnr" id="kleistad_telnr" maxlength="15" placeholder="0123456789" value="<?php echo $input['telnr']; ?>" /></td>
		  </tr>
		  <tr>    
			  <td><label for="kleistad_straat">Straat, nr</label></td>
			  <td colspan="2" ><input type="text" name="straat" id="kleistad_straat" required placeholder="straat" maxlength="50" value="<?php echo $input['straat']; ?>" /></td>
			  <td><input type="text" name="huisnr" id="kleistad_huisnr" required maxlength="10" placeholder="nr" value="<?php echo $input['huisnr']; ?>" /></td>
		  </tr>
		  <tr>
			  <td><label for="kleistad_pcode">Postcode, Plaats</label></td>
			  <td><input type="text" name="pcode" id="kleistad_pcode" required maxlength="10" placeholder="1234AB" value="<?php echo $input['pcode']; ?>" /></td>
			  <td colspan="2" ><input type="text" name="plaats" id="kleistad_plaats" required maxlength="50" placeholder="MijnWoonplaats" value="<?php echo $input['plaats']; ?>" /></td>
		  </tr>
	  </table>
	  <button type="submit" name="kleistad_submit_registratie" >Opslaan</button>
  </form>
<?php endif ?>
