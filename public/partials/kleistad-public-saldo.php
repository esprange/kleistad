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
	<p>Je kunt onderstaand melden dat je het saldo hebt aangevuld</p><hr />
	<form action="<?php echo esc_url( get_permalink() ); ?>" method="POST">
		<?php wp_nonce_field( 'kleistad_saldo' ); ?>
		<input type="hidden" name="kleistad_gebruiker_id" value="<?php echo esc_attr( $data['gebruiker_id'] ); ?>" />
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<label class="kleistad_label" >Betaald</label>
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" name="kleistad_via" id="kleistad_bank" type="radio" value="bank" checked="checked" />
				<label class="kleistad_label_cbr" for="kleistad_bank">Bank</label>
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" name="kleistad_via" id="kleistad_kas" type="radio" value="kas" />
				<label class="kleistad_label_cbr" for="kleistad_kas">Contant</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<label class="kleistad_label">Bedrag</label>
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" type="radio" name="kleistad_bedrag" id="kleistad_b15" value="15" />
				<label class="kleistad_label_cbr" for="kleistad_b15">&euro; 15</label>
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" type="radio" name="kleistad_bedrag" id="kleistad_b30" value="30" checked="checked" />
				<label class="kleistad_label_cbr" for="kleistad_b30">&euro; 30</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<label class="kleistad_label" for="kleistad_datum">Datum betaald</label>
			</div>
			<div class="kleistad_col_6">
				<input class="kleistad_input kleistad_datum" name="kleistad_datum" id="kleistad_datum" type="text" value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>" /><br /><br />
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_10">
				<input class="kleistad_input_cbr" id="kleistad_controle" type="checkbox" onchange="document.getElementById( 'kleistad_submit_saldo' ).disabled = !this.checked;" />
				<label class="kleistad_label_cbr" for="kleistad_controle">Klik dit aan voordat je verzendt</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_10">
				<button type="submit" name="kleistad_submit_saldo" id="kleistad_submit_saldo" disabled>Verzenden</button><br />
			</div>
		</div>
	</form>
<?php endif ?>
