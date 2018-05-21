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

	<form action="<?php echo esc_url( get_permalink() ); ?>" method="POST">
		<?php wp_nonce_field( 'kleistad_saldo' ); ?>
		<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( $data['gebruiker_id'] ); ?>" />
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<input type="radio" name="betaal" id="kleistad_betaal_ideal" class="kleistad_input_cbr" value="ideal" checked />
				<label class="kleistad_label_cbr" for="kleistad_betaal_ideal"></label>
			</div>
		</div>
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<?php Kleistad_Betalen::issuers(); ?>
			</div>
		</div>
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<input type="radio" name="betaal" id="kleistad_betaal_stort" class="kleistad_input_cbr" required value="stort" />
				<label class="kleistad_label_cbr" for="kleistad_betaal_stort"></label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_10" style="padding-top: 20px;">
				<button type="submit" name="kleistad_submit_dagdelenkaart" id="kleistad_submit_dagdelenkaart">Kaart aanvragen</button><br />
			</div>
		</div>
	</form>
<?php endif ?>
