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

if ( ! true ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

	<form class="kleistad_formulier" action="<?php echo esc_url( get_permalink() ); ?>" method="POST">
		<?php wp_nonce_field( 'kleistad_cursus_inschrijving' ); ?>
			<?php if ( count( $data['open_cursussen'] ) < 1 ) : ?>
			<div class="kleistad_row" >
				<div class="kleistad_label kleistad_col_10" >
					Helaas zijn er geen cursussen beschikbaar of zijn ze al volgeboekt
				</div>
			</div>
			<?php
		else :
			foreach ( $data['open_cursussen'] as $cursus_id => $cursus ) :
				if ( $cursus['vervallen'] ) {
					$disabled = 'disabled';
					$cursus_naam = $cursus['naam'] . ': VERVALLEN';
					$cursus_kleur = 'color: gray;';
				} elseif ( $cursus['vol'] ) {
					$disabled = 'disabled';
					$cursus_naam = $cursus['naam'] . ': VOL';
					$cursus_kleur = 'color: gray;';
				} else {
					$disabled = '';
					$cursus_naam = $cursus['naam'];
					$cursus_kleur = '';
				}
				?>
				<div class="kleistad_row kleistad_col_10">
					<input class="kleistad_input_cbr" name="cursus_id" id="kleistad_cursus_<?php echo esc_attr( $cursus_id ); ?>" type="radio" required value="<?php echo esc_attr( $cursus_id ); ?>" 
						   data-technieken='<?php echo wp_json_encode( $cursus['technieken'] ); ?>' <?php checked( $data['input']['cursus_id'], $cursus_id ); ?><?php echo esc_attr( $disabled ); ?> >
					<label class="kleistad_label_cbr" for="kleistad_cursus_<?php echo esc_attr( $cursus_id ); ?>">
						<span style="<?php echo esc_attr( $cursus_kleur ); ?>"><?php echo esc_html( $cursus_naam ); ?></span></label>
				</div>
			<?php endforeach ?>
			<div id="kleistad_cursus_technieken" style="visibility: hidden" >
				<div class="kleistad_row" >
					<div class="kleistad_col_10" style="text-align:center" >
						<label class="kleistad_label">kies de techniek(en) die je wilt oefenen</label>
					</div>
				</div>
				<div class="kleistad_row" >
					<div class="kleistad_col_1" >
					</div>
					<div class="kleistad_label kleistad_col_3" id="kleistad_cursus_draaien" style="visibility: hidden" >
						<input class="kleistad_input_cbr" name="technieken[]" id="kleistad_draaien" type="checkbox" value="Draaien">
						<label class="kleistad_label_cbr" for="kleistad_draaien" >Draaien</label>
					</div>
					<div class="kleistad_label kleistad_col_3" id="kleistad_cursus_handvormen" style="visibility: hidden" >
						<input class="kleistad_input_cbr" name="technieken[]" id="kleistad_handvormen" type="checkbox" value="Handvormen">
						<label class="kleistad_label_cbr" for="kleistad_handvormen" >Handvormen</label>
					</div>
					<div class="kleistad_label kleistad_col_3" id="kleistad_cursus_boetseren" style="visibility: hidden" >
						<input class="kleistad_input_cbr" name="technieken[]" id="kleistad_boetseren" type="checkbox" value="Boetseren">
						<label class="kleistad_label_cbr" for="kleistad_boetseren" >Boetseren</label>
					</div>
				</div>
			</div>
			<?php if ( is_super_admin() ) : ?>
				<div class="kleistad_row" >
					<div class="kleistad_label kleistad_col_3" >
						<label for="kleistad_gebruiker_id">Cursist</label>
					</div>
					<div class="kleistad_col_7">
						<select class="kleistad_input" name="gebruiker_id" id="kleistad_gebruiker_id" >      
							<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
								<option value="<?php echo esc_attr( $gebruiker->id ); ?>"><?php echo esc_html( $gebruiker->display_name ); ?></option>
							<?php endforeach ?>
						</select>
					</div>
				</div>
				<?php
			elseif ( is_user_logged_in() ) :
				?>
				<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" />
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
				<div class="kleistad_row">
					<div class="kleistad_label kleistad_col_3"
						 <label for="subscribe"></label>
					</div>
					<div class="kleistad_col_7">
						<input type="checkbox" name="mc4wp-subscribe" id="subscribe" value="1" checked />
						Ik wil de Kleistad nieuwsbrief ontvangen.
					</div>
				</div>

			<?php endif ?>
			<div class ="kleistad_row" title="Wat is je ervaring met klei? Je kunt hier ook andere opmerkingen achterlaten die van belang zijn voor de cursus indeling" >
				<div class="kleistad_label kleistad_col_3">
					<label for="kleistad_opmerking">Opmerking</label>
				</div>
				<div class="kleistad_input kleistad_col_7">
					<textarea class="kleistad_input" name="opmerking" id="kleistad_opmerking" rows="5" cols="50"><?php echo esc_textarea( $data['input']['opmerking'] ); ?></textarea>
				</div>
			</div>
		<?php endif ?>
		<div class="kleistad_row">
			<div class="kleistad_col_10">
				<button name="kleistad_submit_cursus_inschrijving" id="kleistad_submit_cursus_inschrijving" type="submit" >Verzenden</button>
			</div>
		</div>

	</form>

<?php endif ?>
