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
		<?php
		wp_nonce_field( 'kleistad_cursus_inschrijving' );

		$count = 0;
		foreach ( $data['open_cursussen'] as $cursus_id => $cursus ) :
			if ( ! $cursus['vervallen'] && ! $cursus['vol'] ) :
				$count++;
			endif;
		endforeach;
		if ( $count < 1 ) :
			?>
			<div class="kleistad_row" >
				<div class="kleistad_label kleistad_col_10" >
					Helaas zijn er geen cursussen beschikbaar of zijn ze al volgeboekt
				</div>
			</div>
			<?php
		else :
			?>
			<?php
			$checked_done = false;
			$meer   = false;
			$ruimte = 1;
			foreach ( $data['open_cursussen'] as $cursus_id => $cursus ) :
				if ( $cursus['vervallen'] ) {
					$disabled = true;
					$cursus_naam = $cursus['naam'] . ': VERVALLEN';
					$cursus_kleur = 'color: gray;';
				} elseif ( $cursus['vol'] ) {
					$disabled = true;
					$cursus_naam = $cursus['naam'] . ': VOL';
					$cursus_kleur = 'color: gray;';
				} else {
					$disabled = false;
					$cursus_naam = $cursus['naam'];
					$cursus_kleur = '';
				}
				$checked = false;
				if ( ( 1 === $count && ! $disabled ) || // Er is maar één open cursus.
					 ( 0 === $data['input']['cursus_id'] && ! $disabled ) || // De eerst mogelijke cursus wordt geselecteerd.
					 ( $cursus_id === $data['input']['cursus_id'] ) // Bij het eerder invullen was deze cursus geselecteerd.
				) {
					if ( ! $checked_done ) {
						$checked = true;
						$prijs = $cursus['prijs'];
						$meer = $cursus['meer'];
						$checked_done = true;
						$ruimte = $cursus['ruimte'];
					}
				}

				?>
			<div class="kleistad_row kleistad_col_10" >
				<input class="kleistad_input_cbr" name="cursus_id" id="kleistad_cursus_<?php echo esc_attr( $cursus_id ); ?>" type="radio" value="<?php echo esc_attr( $cursus_id ); ?>" 
					data-technieken='<?php echo wp_json_encode( $cursus['technieken'] ); ?>' 
					data-meer="<?php echo esc_attr( $cursus['meer'] ); ?>"
					data-prijs="<?php echo esc_attr( $cursus['prijs'] ); ?>"
					data-ruimte="<?php echo esc_attr( $cursus['ruimte'] ); ?>"
					<?php disabled( $disabled ); ?> 
					<?php checked( $checked ); ?> />
				<label class="kleistad_label_cbr" for="kleistad_cursus_<?php echo esc_attr( $cursus_id ); ?>">
					<span style="<?php echo esc_attr( $cursus_kleur ); ?>"><?php echo esc_html( $cursus_naam ); ?></span></label>
			</div>
			<?php endforeach ?>
			<div id="kleistad_cursus_technieken" style="visibility: hidden;padding-bottom:20px;" >
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
			<div class="kleistad_row" >
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
				<input type="hidden" name="aantal" id="kleistad_aantal" value="1" />
				<?php
			elseif ( is_user_logged_in() ) :
				?>
				<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" />
				<input type="hidden" name="aantal" id="kleistad_aantal" value="1" />
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
						<input class="kleistad_input" name="plaats" id="kleistad_plaats" type="text" required  maxlength="50" placeholder="MijnWoonplaats" value="<?php echo esc_attr( $data['input']['plaats'] ); ?>" />
					</div>
				</div>
				<div id="kleistad_cursus_aantal" style="<?php echo esc_attr( ! $meer ? 'visibility: hidden' : '' ); ?>" >
					<div class="kleistad_row">
						<div class="kleistad_label kleistad_col_3">
							<label for="kleistad_aantal">Ik kom met </label>
						</div>
						<div class="kleistad_col_3">
							<input class="kleistad_input" name="aantal" id="kleistad_aantal" value="<?php echo esc_attr( $data['input']['aantal'] ); ?>" />
						</div>
						<div class="kleistad_label kleistad_col_4">
							<label>deelnemers</label>
						</div>
					</div>
				</div>
				<div class="kleistad_row">
					<div class="kleistad_label kleistad_col_5">
						 <label for="subscribe">Ik wil de Kleistad nieuwsbrief ontvangen.</label>
					</div>
					<div class="kleistad_col_5">
						<input type="checkbox" name="mc4wp-subscribe" id="subscribe" value="1" checked />
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
			<div class ="kleistad_row">
				<div class="kleistad_col_10">
					<input type="radio" name="betaal" id="kleistad_betaal_ideal" class="kleistad_input_cbr" value="ideal" checked />
					<label class="kleistad_label_cbr" for="kleistad_betaal_ideal">
						<img src="<?php echo esc_url( plugins_url( '/../images/iDEAL_48x48.png', __FILE__ ) ); ?>" style="padding: 15px 3px 15px 3px;"/>
						ik betaal €&nbsp;<span name="bedrag_tekst"><?php echo esc_html( number_format( $prijs, 2, ',', '' ) ); ?></span>&nbsp;en word meteen ingedeeld. Mijn bank:&nbsp;
						<select name="bank" id="kleistad_bank" style="padding-left:15px;width: 200px;font-weight:normal">
							<?php Kleistad_Betalen::issuers(); ?>
						</select>
					</label>
				</div>
			</div>
			<div class ="kleistad_row">
				<div class="kleistad_col_10">
					<input type="radio" name="betaal" id="kleistad_betaal_stort" class="kleistad_input_cbr" required value="stort" />
					<label class="kleistad_label_cbr" for="kleistad_betaal_stort">
						ik betaal later door storting van €&nbsp;<span name="bedrag_tekst"><?php echo esc_html( number_format( $prijs, 2, ',', '' ) ); ?></span>. Indeling vindt daarna plaats.
					</label>
				</div>
			</div>
		<?php endif ?>
		<div class="kleistad_row" style="padding-top:20px;">
			<div class="kleistad_col_10">
				<button name="kleistad_submit_cursus_inschrijving" id="kleistad_submit_cursus_inschrijving" type="submit" >Inschrijven</button>
			</div>
		</div>

	</form>

<?php endif ?>
