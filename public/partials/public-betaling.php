<?php
/**
 * Toon het betaling formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( 'betalen' === $data['actie'] ) :
	$this->form();
	?>

	<input type="hidden" name="order_id" value="<?php echo esc_attr( $data['order_id'] ); ?>" />
	<input type="hidden" name="artikel_type" value="<?php echo esc_attr( $data['artikel_type'] ); ?>" />
	<input type="hidden" name="betaal" value="ideal" />
	<h2>Overzicht betaling <?php echo esc_html( $data['betreft'] ); ?> </h2>

	<div class="kleistad_row">
		<div class="kleistad_col_3">
			<p>Voor</p>
		</div>
		<div class="kleistad_col_7">
			<p><?php echo esc_html( $data['klant'] ); ?></p>
		</div>
	</div>
	<?php
	foreach ( $data['regels']  as $regels ) :
		?>
	<div class="kleistad_row">
		<div class="kleistad_col_5">
			<p><?php echo esc_html( $regel['artikel'] ); ?></p>
		</div>
		<div class="kleistad_col_2">
			<p><?php echo esc_html( $regel['aantal'] ); ?></p>
		</div>
		<div class="kleistad_col_3">
			<p>&euro; <?php echo esc_html( number_format_i18n( $regel['aantal'] * $regel['prijs'], 2 ) ); ?></p>
		</div>
	</div>
		<?php
		endforeach
	?>
	<div class="kleistad_row">
		<div class="kleistad_col_7">
			<p style="text-align:right">Reeds betaald</p>
		</div>
		<div class="kleistad_col_3">
			<p>&euro; <?php echo esc_html( number_format_i18n( $data['reeds_betaald'], 2 ) ); ?></p>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_7">
			<p style="text-align:right">Totale kosten</p>
		</div>
		<div class="kleistad_col_3">
			<p>&euro; <?php echo esc_html( number_format_i18n( $data['reeds_betaald'] + $data['openstaand'], 2 ) ); ?></p>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_7">
			<p>&nbsp;</p>
		</div>
		<div class="kleistad_col_3">
			<hr>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_7">
			<p style="text-align:right">Nog te betalen</p>
		</div>
		<div class="kleistad_col_3">
			<p>&euro; <?php echo esc_html( number_format_i18n( $data['openstaand'], 2 ) ); ?></p>
		</div>
	</div>
	<div class ="kleistad_row">
		<div class="kleistad_col_10">
			<?php \Kleistad\Betalen::issuers(); ?>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_10" style="padding-top: 20px;">
			<button type="submit" name="kleistad_submit_betaling" id="kleistad_submit">Betalen</button><br />
		</div>
	</div>
</form>
<?php endif ?>
