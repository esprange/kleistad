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

namespace Kleistad;

?>
<p>Je huidige stooksaldo is <strong>&euro; <?php echo esc_html( $data['saldo'] ); ?></strong></p>
<?php $this->form(); ?>
	<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( $data['gebruiker_id'] ); ?>" />
	<div class="kleistad-row">
		<div class="kleistad-col-2">
			<label class="kleistad-label">Bedrag</label>
		</div>
		<div class="kleistad-col-2">
			<input type="radio" name="bedrag" id="kleistad_b15" value="15" />
			<label for="kleistad_b15">&euro; 15</label>
		</div>
		<div class="kleistad-col-2">
			<input type="radio" name="bedrag" id="kleistad_b30" value="30" checked="checked" />
			<label for="kleistad_b30">&euro; 30</label>
		</div>
		<div class="kleistad-col-4">
			<input type="radio" name="bedrag" id="kleistad_ander" value="0" />
			<label for="kleistad_ander">anders &euro;&nbsp;
				<input name="ander" type="number" min="15" max="100" maxlength="3" style="width:4em;" title="minimum 15, maximum 100 euro" >
			</label>
		</div>
	</div>
	<div class ="kleistad-row">
		<div class="kleistad-col-10">
			<input type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" checked />
			<label for="kleistad_betaal_ideal"></label>
		</div>
	</div>
	<div class ="kleistad-row">
		<div class="kleistad-col-10">
			<?php Betalen::issuers(); ?>
		</div>
	</div>
	<div class ="kleistad-row">
		<div class="kleistad-col-10">
			<input type="radio" name="betaal" id="kleistad_betaal_stort" required value="stort" />
			<label for="kleistad_betaal_stort"></label>
		</div>
	</div>
	<div class="kleistad-row">
		<div class="kleistad-col-10" style="padding-top: 20px;">
			<button type="submit" name="kleistad_submit_saldo" id="kleistad_submit" >Betalen</button><br />
		</div>
	</div>
</form>
