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

namespace Kleistad;

$this->form();
?>
	<div class="kleistad-row">
		<div class="kleistad-col-3 kleistad-label">
			<label for="kleistad_start_datum">Start per</label>
		</div>
		<div class="kleistad-col-3 kleistad-input">
			<input class="kleistad-datum kleistad-input" name="start_datum" id="kleistad_start_datum" type="text" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
		</div>
	</div>
	<?php if ( is_user_logged_in() ) : ?>
	<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" />
		<?php
	else :
		require plugin_dir_path( dirname( __FILE__ ) ) . '/partials/public-gebruiker.php';
	endif
	?>
	<div class ="kleistad-row">
		<div class="kleistad-col-10">
			<input type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" checked />
			<label for="kleistad_betaal_ideal">Ik betaal € <?php echo esc_html( ( number_format_i18n( $this->options['dagdelenkaart'], 2 ) ) ); ?></label>
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
			<label for="kleistad_betaal_stort">Ik betaal door storting van € <?php echo esc_html( ( number_format_i18n( $this->options['dagdelenkaart'], 2 ) ) ); ?> volgens de betaalinstructie, zoals aangegeven in de te ontvangen bevestigingsemail.</label>
		</div>
	</div>
	<div class="kleistad-row">
		<div class="kleistad-col-10" style="padding-top: 20px;">
			<button name="kleistad_submit_dagdelenkaart" id="kleistad_submit" type="submit" <?php disabled( ! is_super_admin() && '' !== $data['verklaring'] ); ?>>Betalen</button><br />
		</div>
	</div>
</form>
