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

if ( ! Kleistad_Roles::reserveer() ) : ?>
<p>Geen toegang tot dit formulier</p>
<?php
else :
?>

<p>Je huidige stooksaldo is <strong>&euro; <?php echo $data['saldo']; ?></strong></p>
<p>Je kunt onderstaand melden dat je het saldo hebt aangevuld</p><hr />
<form action="<?php echo get_permalink(); ?>" method="POST">
	<?php wp_nonce_field( 'kleistad_saldo' ); ?>
	<input type="hidden" name="kleistad_gebruiker_id" value="<?php echo $data['gebruiker_id']; ?>" />
	<fieldset><legend>Betaald</legend>
		<label for="kleistad_bank">Bank
			<input type="radio" name="kleistad_via" id="kleistad_bank" value="bank" checked="checked" /></label>
		<label for="kleistad_kas">Contant
			<input type="radio" name="kleistad_via" id="kleistad_kas" value="kas" /></label>
	</fieldset>
	<fieldset><legend>Bedrag</legend>
		<label for="kleistad_b15">&euro; 15
			<input type="radio" name="kleistad_bedrag" id="kleistad_b15" value="15" /></label>
		<label for="kleistad_b30">&euro; 30
			<input type="radio" name="kleistad_bedrag" id="kleistad_b30" value="30" checked="checked" /></label>
	</fieldset>
	<label for="kleistad_datum">Datum betaald</label>
	<input type="text" name="kleistad_datum" id="kleistad_datum" class="kleistad_datum" value="<?php echo date( 'd-m-Y' ); ?>" /><br /><br />
	<label for="kleistad_controle">Klik dit aan voordat je verzendt</label>&nbsp;
	<input type="checkbox" id="kleistad_controle"
		   onchange="document.getElementById('kleistad_submit_saldo').disabled = !this.checked;" /><br /><br />
	<button type="submit" name="kleistad_submit_saldo" id="kleistad_submit_saldo" disabled>Verzenden</button><br />
</form>
<?php endif ?>
