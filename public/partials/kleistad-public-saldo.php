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

if ( ! Kleistad_Roles::reserveer() ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

	<p>Je huidige stooksaldo is <strong>&euro; <?php echo esc_html( $data['saldo'] ); ?></strong></p>
	<form action="<?php echo esc_url( get_permalink() ); ?>" method="POST">
		<?php wp_nonce_field( 'kleistad_saldo' ); ?>
		<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( $data['gebruiker_id'] ); ?>" />
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<label class="kleistad_label">Bedrag</label>
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" type="radio" name="bedrag" id="kleistad_b15" value="15" />
				<label class="kleistad_label_cbr" for="kleistad_b15">&euro; 15</label>
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" type="radio" name="bedrag" id="kleistad_b30" value="30" checked="checked" />
				<label class="kleistad_label_cbr" for="kleistad_b30">&euro; 30</label>
			</div>
		</div>
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<input type="radio" name="betaal" id="kleistad_betaal_ideal" class="kleistad_input_cbr" value="ideal" checked />
				<label class="kleistad_label_cbr" for="kleistad_betaal_ideal">
					<img src="<?php echo esc_url( plugins_url( '/../images/iDEAL_48x48.png', __FILE__ ) ); ?>" style="padding: 15px 3px 15px 3px;"/>
					ik betaal €&nbsp;<span name="bedrag_tekst">30,00</span>&nbsp;en verhoog mijn saldo. Mijn bank:&nbsp;
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
					ik betaal door storting van €&nbsp;<span name="bedrag_tekst">30,00</span>. Verhoging saldo vindt daarna plaats.
				</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_10" style="padding-top: 20px;">
				<button type="submit" name="kleistad_submit_saldo" id="kleistad_submit_saldo">Saldo Aanpassen</button><br />
			</div>
		</div>
	</form>
<?php endif ?>
