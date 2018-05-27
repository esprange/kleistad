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

if ( ! $data['leeg'] ) :
	$inschrijvingkosten = $data['cursus']->inschrijfkosten * $data['inschrijving']->aantal;
	$restantkosten      = $data['cursus']->cursuskosten * $data['inschrijving']->aantal;
	$cursuskosten       = $restantkosten + $inschrijvingkosten;
	?>

<form action="<?php echo esc_url( get_permalink() ); ?>" method="POST">
	<?php wp_nonce_field( 'kleistad_betaling' ); ?>
<input type="hidden" name="cursist_id" value="<?php echo esc_attr( $data['cursist']->ID ); ?>" />
<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $data['cursus']->id ); ?>" />
<input type="hidden" name="betaal" class="kleistad_input_cbr" value="ideal" />
<h2>Overzicht betaling cursuskosten</h2>

<div class="kleistad_row">
	<div class="kleistad_col_3">
		<p>Cursist</p>
	</div>
	<div class="kleistad_col_7">
		<p><?php echo esc_html( $data['cursist']->first_name . ' ' . $data['cursist']->last_name ); ?></p>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3">
		<p>Aantal personen</p>
	</div>
	<div class="kleistad_col_7">
		<p><?php echo esc_html( $data['inschrijving']->aantal ); ?></p>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3">
		<p>Reeds betaald</p>
	</div>
	<div class="kleistad_col_7">
		<p>&euro; <?php echo esc_html( number_format_i18n( $inschrijvingkosten, 2 ) ); ?></p>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3">
		<p>Totale cursuskosten</p>
	</div>
	<div class="kleistad_col_7">
		<p>&euro; <?php echo esc_html( number_format_i18n( $cursuskosten, 2 ) ); ?></p>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3">
		<p>&nbsp;</p>
	</div>
	<div class="kleistad_col_7">
		<hr>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3">
		<p>Nog te betalen</p>
	</div>
	<div class="kleistad_col_7">
		<p>&euro; <?php echo esc_html( number_format_i18n( $restantkosten, 2 ) ); ?></p>
	</div>
</div>
<div class ="kleistad_row">
	<div class="kleistad_col_10">
		<?php Kleistad_Betalen::issuers(); ?>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_10" style="padding-top: 20px;">
		<button type="submit" name="kleistad_submit_betaling" id="kleistad_submit">Betalen</button><br />
	</div>
</div>
</form>
	<?php
	endif
?>
