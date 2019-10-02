<?php
/**
 * Toon het cursus inschrijving formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

?>

<?php
$this->form();
$checked_id = 0;
$count      = 0;
foreach ( $data['open_cursussen'] as $cursus_id => $cursus ) :
	if ( $cursus['selecteerbaar'] ) :
		$count++;
	endif;
endforeach;
if ( ! $count ) :
	?>
	<div class="kleistad_row" >
		<div class="kleistad_col_10 kleistad_label" >
			<?php echo esc_html( $data['cursus_selectie'] ? 'Helaas zijn er geen cursussen beschikbaar of ze zijn al volgeboekt' : 'Helaas is deze cursus nu niet beschikbaar' ); ?>
		</div>
	</div>
	<?php
else :
	?>
	<div style="<?php echo esc_attr( $data['cursus_selectie'] ? '' : 'display: none' ); ?>" >
	<?php
	// Check eerst welke cursus geselecteerd moet staan.
	foreach ( $data['open_cursussen'] as $cursus_id => $cursus ) :
		if ( $cursus['selecteerbaar'] ) :
			// De eerder geselecteerde als die nog steeds selecteerbaar is of als er maar 1 cursus mogelijk is.
			if ( intval( $data['input']['cursus_id'] ) === $cursus_id || 1 === $count ) :
				$checked_id = $cursus_id;
				break;
			endif;
		endif;
	endforeach;
	// Toon nu de cursussen en selecteer de cursus. De rest wordt met javascript gedaan.
	foreach ( $data['open_cursussen'] as $cursus_id => $cursus ) :
		$json_cursus = wp_json_encode(
			[
				'technieken' => $cursus['technieken'],
				'meer'       => $cursus['meer'],
				'ruimte'     => $cursus['ruimte'],
				'prijs'      => $cursus['prijs'],
				'lopend'     => $cursus['lopend'],
			]
		);
		if ( false === $json_cursus ) :
			continue;
		endif;
		?>
		<div class="kleistad_col_10 kleistad_row" >
			<input class="kleistad_input_cbr" name="cursus_id" id="kleistad_cursus_<?php echo esc_attr( $cursus_id ); ?>" type="radio" value="<?php echo esc_attr( $cursus_id ); ?>"
				data-cursus='<?php echo $json_cursus; // phpcs:ignore ?>' <?php disabled( ! $cursus['selecteerbaar'] ); ?> <?php checked( $checked_id, $cursus_id ); ?> />
			<label class="kleistad_label_cbr" for="kleistad_cursus_<?php echo esc_attr( $cursus_id ); ?>">
				<span style="<?php echo esc_attr( $cursus['selecteerbaar'] ? '' : 'color: gray;' ); ?>"><?php echo esc_html( $cursus['naam'] ); ?></span></label>
		</div>
		<?php
	endforeach;
	?>
	</div>
	<div id="kleistad_cursus_technieken" style="visibility: hidden;padding-bottom: 20px;" >
		<div class="kleistad_row" >
			<div class="kleistad_col_10">
				<label class="kleistad_label">kies de techniek(en) die je wilt oefenen</label>
			</div>
		</div>
		<div class="kleistad_row" >
			<div class="kleistad_col_1" >
			</div>
			<div class="kleistad_col_3 kleistad_label" id="kleistad_cursus_draaien" style="visibility: hidden" >
				<input class="kleistad_input_cb" name="technieken[]" id="kleistad_draaien" type="checkbox" value="Draaien" <?php checked( in_array( 'Draaien', $data['input']['technieken'], true ) ); ?> >
				<label class="kleistad_label_cb" for="kleistad_draaien" >Draaien</label>
			</div>
			<div class="kleistad_col_3 kleistad_label" id="kleistad_cursus_handvormen" style="visibility: hidden" >
				<input class="kleistad_input_cb" name="technieken[]" id="kleistad_handvormen" type="checkbox" value="Handvormen" <?php checked( in_array( 'Handvormen', $data['input']['technieken'], true ) ); ?> >
				<label class="kleistad_label_cb" for="kleistad_handvormen" >Handvormen</label>
			</div>
			<div class="kleistad_col_3 kleistad_label" id="kleistad_cursus_boetseren" style="visibility: hidden" >
				<input class="kleistad_input_cb" name="technieken[]" id="kleistad_boetseren" type="checkbox" value="Boetseren" <?php checked( in_array( 'Boetseren', $data['input']['technieken'], true ) ); ?> >
				<label class="kleistad_label_cb" for="kleistad_boetseren" >Boetseren</label>
			</div>
			<div class="kleistad_row" >
			</div>
		</div>
	</div>
	<?php if ( is_super_admin() ) : ?>
	<div class="kleistad_row" >
		<div class="kleistad_col_3 kleistad_label" >
			<label for="kleistad_gebruiker_id">Cursist</label>
		</div>
		<div class="kleistad_col_7">
			<select class="kleistad_input" name="gebruiker_id" id="kleistad_gebruiker_id" >
				<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
					<option value="<?php echo esc_attr( $gebruiker->ID ); ?>"><?php echo esc_html( $gebruiker->display_name ); ?></option>
				<?php endforeach ?>
			</select>
		</div>
	</div>
	<input type="hidden" name="aantal" id="kleistad_aantal" value="1" />
	<?php elseif ( is_user_logged_in() ) : ?>
	<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" />
	<input type="hidden" name="aantal" id="kleistad_aantal" value="1" />
	<?php else : ?>
	<div id="kleistad_cursus_aantal" style="visibility: hidden" >
		<div class="kleistad_row">
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_aantal">Ik kom met </label>
			</div>
			<div class="kleistad_col_2">
				<input class="kleistad_input" name="aantal" id="kleistad_aantal" size="3" value="<?php echo esc_attr( $data['input']['aantal'] ); ?>" />
			</div>
			<div class="kleistad_col_2 kleistad_label">
				<label>deelnemers</label>
			</div>
		</div>
	</div>
		<?php require plugin_dir_path( dirname( __FILE__ ) ) . '/partials/public-gebruiker.php'; ?>
	<?php endif ?>
	<div id="kleistad_cursus_betalen" style="display:none" >
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<input type="radio" name="betaal" id="kleistad_betaal_ideal" class="kleistad_input_cbr" value="ideal" <?php checked( $data['input']['betaal'], 'ideal' ); ?> />
				<label class="kleistad_label_cbr" for="kleistad_betaal_ideal"></label>
			</div>
		</div>
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<?php \Kleistad\Betalen::issuers(); ?>
			</div>
		</div>
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<input type="radio" name="betaal" id="kleistad_betaal_stort" class="kleistad_input_cbr" required value="stort" <?php checked( $data['input']['betaal'], 'stort' ); ?> />
				<label class="kleistad_label_cbr" for="kleistad_betaal_stort"></label>
			</div>
		</div>
	</div>
	<div id="kleistad_cursus_lopend" style="display:none" >
		<div class="kleistad_row">
			<div class="kleistad_col_10">
				<label class="kleistad_label">
				Deze cursus is reeds gestart. Bij inschrijving op deze cursus zal contact met je worden opgenomen en krijg je nadere instructie over de betaling.
				</label>
			</div>
		</div>
	</div>
	<div class="kleistad_row" style="padding-top:20px;">
		<div class="kleistad_col_10">
			<button name="kleistad_submit_cursus_inschrijving" id="kleistad_submit" <?php disabled( ! $checked_id ); ?> type="submit" >Betalen</button>
			<span id="kleistad_submit_enabler" style="<?php echo esc_attr( ( ! $checked_id ) ? '' : 'display: none' ); ?>" ><strong>Er is nog geen cursus gekozen</strong></span>
		</div>
	</div>
	<?php
	endif
?>
</form>