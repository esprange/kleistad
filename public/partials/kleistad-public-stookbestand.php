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

if ( ! Kleistad_Roles::override() ) : ?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

<form action="<?php echo esc_url( get_permalink() ); ?>" method="POST" >
	<?php wp_nonce_field( 'kleistad_stookbestand' ); ?>
	<input type="hidden" name="kleistad_gebruiker_id" value="<?php echo esc_attr( $data['gebruiker_id'] ); ?>" />
	<div class="kleistad_row">
		<div class="kleistad_col_3">
			<label class="kleistad_label" for="kleistad_vanaf_datum" >Vanaf</label>
		</div>
		<div class="kleistad_col_7">
			<input type="text" name="vanaf_datum" id="kleistad_vanaf_datum" class="kleistad_datum" value="<?php echo esc_attr( date( '01-01-Y' ) ); ?>" />
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3">
			<label class="kleistad_label" for="kleistad_tot_datum" >Tot</label>
		</div>
		<div class="kleistad_col_7">
			<input type="text" name="tot_datum" id="kleistad_tot_datum" class="kleistad_datum" value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>" />
		</div>
	</div>
	<button type="submit" name="kleistad_submit_stookbestand" id="kleistad_submit_stookbestand">Verzenden</button><br />
</form>
<?php endif; ?>
