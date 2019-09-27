<?php
/**
 * Toon het dagdelenkaart formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

$this->form();
?>
	<div class="kleistad_row">
		<div class="kleistad_col_3 kleistad_label">
			<label for="kleistad_start_datum">Start per</label>
		</div>
		<div class="kleistad_col_2 kleistad_input">
			<input class="kleistad_datum kleistad_input" name="start_datum" id="kleistad_start_datum" type="text" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
		</div>
	</div>
	<?php if ( is_user_logged_in() ) : ?>
	<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" />
		<?php
	else :
		require plugin_dir_path( dirname( __FILE__ ) ) . '/partials/public-gebruiker.php';
	endif
	?>
	<div class ="kleistad_row">
		<div class="kleistad_col_10">
			<input type="radio" name="betaal" id="kleistad_betaal_ideal" class="kleistad_input_cbr" value="ideal" checked />
			<label class="kleistad_label_cbr" for="kleistad_betaal_ideal">Ik betaal € <?php echo esc_html( ( number_format_i18n( $this->options['dagdelenkaart'], 2 ) ) ); ?></label>
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
			<label class="kleistad_label_cbr" for="kleistad_betaal_stort">Ik betaal door storting van € <?php echo esc_html( ( number_format_i18n( $this->options['dagdelenkaart'], 2 ) ) ); ?> volgens de betaalinstructie, zoals aangegeven in de te ontvangen bevestigingsemail.</label>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_10" style="padding-top: 20px;">
			<button name="kleistad_submit_dagdelenkaart" id="kleistad_submit" type="submit" <?php disabled( ! is_super_admin() && '' !== $data['verklaring'] ); ?>>Betalen</button><br />
		</div>
	</div>
</form>
