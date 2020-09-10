<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

?>
<p>Je huidige stooksaldo is <strong>&euro; <?php echo esc_html( $data['saldo'] ); ?></strong></p>
<?php $this->form(); ?>
	<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( $data['gebruiker_id'] ); ?>" />
	<div class="kleistad_row">
		<div class="kleistad_col_2">
			<label class="kleistad_label">Bedrag</label>
		</div>
		<div class="kleistad_col_2">
			<input class="kleistad_input_cbr" type="radio" name="bedrag" id="kleistad_b15" value="15" />
			<label class="kleistad_label_cbr" for="kleistad_b15">&euro; 15</label>
		</div>
		<div class="kleistad_col_2">
			<input class="kleistad_input_cbr" type="radio" name="bedrag" id="kleistad_b30" value="30" checked="checked" />
			<label class="kleistad_label_cbr" for="kleistad_b30">&euro; 30</label>
		</div>
		<div class="kleistad_col_4">
			<input class="kleistad_input_cbr" type="radio" name="bedrag" id="kleistad_ander" value="0" />
			<label class="kleistad_label_cbr" for="kleistad_ander">anders &euro;&nbsp;
				<input name="ander" type="number" min="15" max="100" maxlength="3" style="width:4em;" title="minimum 15, maximum 100 euro" >
			</label>
		</div>
	</div>
	<div class ="kleistad_row">
		<div class="kleistad_col_10">
			<input type="radio" name="betaal" id="kleistad_betaal_ideal" class="kleistad_input_cbr" value="ideal" checked />
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
			<input type="radio" name="betaal" id="kleistad_betaal_stort" class="kleistad_input_cbr" required value="stort" />
			<label class="kleistad_label_cbr" for="kleistad_betaal_stort"></label>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_10" style="padding-top: 20px;">
			<button type="submit" name="kleistad_submit_saldo" id="kleistad_submit" >Betalen</button><br />
		</div>
	</div>
</form>
