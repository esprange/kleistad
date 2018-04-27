<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<form method="post" action="options.php" >
	<?php settings_fields( 'kleistad-opties' ); ?>
	<table class="form-table" >
		<tr >
			<th scope="row">Prijs onbeperkt abonnement</th>
			<td><input type="text" name="kleistad-opties[onbeperkt_abonnement]" 
					   value="<?php echo esc_attr( $this->options['onbeperkt_abonnement'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs beperkt abonnement</th>
			<td><input type="text" name="kleistad-opties[beperkt_abonnement]" 
					   value="<?php echo esc_attr( $this->options['beperkt_abonnement'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs borg kast</th>
			<td><input type="text" name="kleistad-opties[borg_kast]" 
					   value="<?php echo esc_attr( $this->options['borg_kast'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs dagdelenkaart</th>
			<td><input type="number" step="0.01" min="0"  name="kleistad-opties[dagdelenkaart]" 
					   value="<?php echo esc_attr( $this->options['dagdelenkaart'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs standaaard cursus</th>
			<td><input type="number" step="0.01" min="0" name="kleistad-opties[cursusprijs]" 
					   value="<?php echo esc_attr( $this->options['cursusprijs'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs cursus inschrijving</th>
			<td><input type="number" step="0.01" min="0"  name="kleistad-opties[cursusinschrijfprijs]" 
					   value="<?php echo esc_attr( $this->options['cursusinschrijfprijs'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs standaard workshop</th>
			<td><input type="number" step="0.01" min="0"  name="kleistad-opties[workshopprijs]" 
					   value="<?php echo esc_attr( $this->options['workshopprijs'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs kinderworkshop</th>
			<td><input type="number" step="0.01" min="0"  name="kleistad-opties[kinderworkshopprijs]" 
					   value="<?php echo esc_attr( $this->options['kinderworkshopprijs'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Maximum cursisten</th>
			<td><input type="number" step="1" min="1"  max="99" name="kleistad-opties[cursusmaximum]" 
					   value="<?php echo esc_attr( $this->options['cursusmaximum'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Termijn (dagen) dat correctie stook mogelijk is</th>
			<td><input type="number" min="0"  name="kleistad-opties[termijn]" 
					   value="<?php echo esc_attr( $this->options['termijn'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Mollie geheime sleutel</th>
			<td><input type="text" name="kleistad-opties[sleutel]" 
					   value="<?php echo esc_attr( $this->options['sleutel'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Mollie geheime sleutel voor testen</th>
			<td><input type="text" name="kleistad-opties[sleutel_test]" 
					   value="<?php echo esc_attr( $this->options['sleutel_test'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Mollie betalen actief</th>
			<td><input type="radio" name="kleistad-opties[betalen]" 
					   value="0" <?php checked( 0, $this->options['betalen'] ); ?>/>Uit<br/>
				<input type="radio" name="kleistad-opties[betalen]" 
					   value="1" <?php checked( 1, $this->options['betalen'] ); ?>/>Aan</td>
		</tr>

	</table>

	<?php submit_button(); ?>
</form>
