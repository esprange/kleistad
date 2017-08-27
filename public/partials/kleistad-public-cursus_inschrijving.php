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
if ( ! true ) :
	?>
  <p>Geen toegang tot dit formulier</p>
<?php
else :
	extract( $data );
?>

  <form action="<?php echo get_permalink(); ?>" method="POST">
		<?php wp_nonce_field( 'kleistad_cursus_inschrijving' ); ?>
	  <table class="kleistad_form" >
			<?php if ( count( $open_cursussen ) < 1 ) : ?>
			<tr>
				<td colspan="4">Helaas zijn er geen cursussen beschikbaar of zijn ze al volgeboekt </td>
			</tr>
			<?php
		  else :
				foreach ( $open_cursussen as $cursus_id => $cursus ) :
					$checked = ($input['cursus_id'] == $cursus_id ) ? 'checked' : '';
					$disabled = ($cursus['vervallen'] || $cursus['vol']) ? 'disabled' : '';
					?>
				  <tr>
					  <td style="text-align:center"><input type="radio" name="cursus_id" value="<?php echo $cursus_id; ?>" 
													   data-technieken='<?php echo json_encode( $cursus['technieken'] ); ?>' <?php echo $checked . ' ' . $disabled; ?> ></td>
				  <td colspan="3"><?php echo $cursus['naam']; ?></td>
				  </tr>
				<?php endforeach ?>
			<tr title="kies de techniek(en) die je wilt oefenen" >
				<td style="visibility:hidden" id="kleistad_cursus_technieken" >Techniek</td>
				<td style="visibility:hidden" id="kleistad_cursus_draaien" >
					<input type="checkbox" name="technieken[]" value="Draaien">Draaien</td>
				<td style="visibility:hidden" id="kleistad_cursus_handvormen" >
					<input type="checkbox" name="technieken[]" value="Handvormen">Handvormen</td>
				<td style="visibility:hidden" id="kleistad_cursus_boetseren" >
					<input type="checkbox" name="technieken[]" value="Boetseren">Boetseren</td>
			</tr>
			<?php if ( is_super_admin() ) : ?>
			  <tr>
				  <td><label for="kleistad_gebruiker_id">Cursist</label></td>
				  <td colspan="2">
					  <select id="kleistad_gebruiker_id" name="gebruiker_id" >      
							<?php foreach ( $gebruikers as $gebruiker ) : ?>
							<option value="<?php echo $gebruiker->id; ?>"><?php echo $gebruiker->display_name; ?></option>
							<?php endforeach ?>
					  </select>
				  </td>
				  <td></td>
			  </tr>
				<?php
			  elseif ( is_user_logged_in() ) :
				?>
			  <tr>
				  <td colspan="4"><input type="hidden" name="gebruiker_id" value="<?php echo get_current_user_id(); ?>" /></td>
			  </tr>
			<?php else : ?> 
			  <tr>
				  <td><label for="kleistad_voornaam">Naam</label></td>
				  <td><input type="text" name="voornaam" id="kleistad_voornaam" required maxlength="25" placeholder="voornaam" value="<?php echo $input['voornaam']; ?>" /></td>
				  <td colspan="2" ><input type="text" name="achternaam" id="kleistad_achternaam" required maxlength="25" placeholder="achternaam" value="<?php echo $input['achternaam']; ?>" /></td>
			  </tr>
			  <tr>
				  <td><label for="kleistad_emailadres">Email adres</label></td>
				  <td colspan="3" ><input type="email" name="emailadres" id="kleistad_emailadres" required placeholder="mijnemailadres@voorbeeld.nl" value="<?php echo $input['emailadres']; ?>" /></td>
			  </tr>
			  <tr>
				  <td><label for="kleistad_telnr">Telefoon</label></td>
				  <td colspan="3" ><input type="text" name="telnr" id="kleistad_telnr" maxlength="15" placeholder="0123456789" value="<?php echo $input['telnr']; ?>" /></td>
			  </tr>
			  <tr>    
				  <td><label for="kleistad_straat">Straat, nr</label></td>
				  <td colspan="2" ><input type="text" name="straat" id="kleistad_straat" required placeholder="straat" maxlength="50" value="<?php echo $input['straat']; ?>" /></td>
				  <td><input type="text" name="huisnr" id="kleistad_huisnr" maxlength="10" placeholder="nr" value="<?php echo $input['huisnr']; ?>" /></td>
			  </tr>
			  <tr>
				  <td><label for="kleistad_pcode">Postcode, Plaats</label></td>
				  <td><input type="text" name="pcode" id="kleistad_pcode" maxlength="10" placeholder="1234AB" pattern="[1-9][0-9]{3}\s?[a-zA-Z]{2}" title="1234AB" value="<?php echo $input['pcode']; ?>" /></td>
				  <td colspan="2" ><input type="text" name="plaats" id="kleistad_plaats" required maxlength="50" placeholder="MijnWoonplaats" value="<?php echo $input['plaats']; ?>" /></td>
			  </tr>
			<?php endif ?>
			<tr title="Wat is je ervaring met klei? Je kunt hier ook andere opmerkingen achterlaten die van belang zijn voor de cursus indeling" ><td><label for="kleistad_opmerking_veld">Opmerking</label></td>
				<td colspan="3" ><textarea name="opmerking" id="kleistad_opmerking_veld" rows="5" cols="50"><?php echo $input['opmerking']; ?></textarea></td>
				<td></td>
			</tr>
			<?php endif ?>
	  </table>
	  <button type="submit" name="kleistad_submit_cursus_inschrijving" id="kleistad_submit_cursus_inschrijving" >Verzenden</button>
  </form>

<?php endif ?>
