<?php
/**
 * Toon het instellingen meta box
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<form method="POST" action="options.php" >
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
			<th scope="row">Prijs standaaard cursus excl. inschrijving</th>
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

		<?php
		$i = 1;
		while ( isset( $this->options['extra'][ $i ]['naam'] ) ) :
			?>
		<tr >
			<th scope="row">Abonnement extra <?php echo esc_html( $i ); ?></th>
			<td><input type="text" class="kleistad-extra" name="kleistad-opties[extra][<?php echo esc_attr( $i ); ?>][naam]"
					value="<?php echo esc_attr( $this->options['extra'][ $i ]['naam'] ); ?>"  <?php echo ! empty( $this->options['extra'][ $i ]['naam'] ) ? 'readonly' : ''; ?> /></td>
			<th scope="row">Prijs</th>
			<td><input type="number" step="0.01" min="0"  name="kleistad-opties[extra][<?php echo esc_attr( $i ); ?>][prijs]"
					value="<?php echo esc_attr( $this->options['extra'][ $i ]['prijs'] ); ?>" /></td>
		</tr>
			<?php
			$i++;
		endwhile;
		?>

		<tr id="kleistad-extra-toevoegen">
			<th>Extra toevoegen</th>
			<td colspan="3"><button type="button" id="kleistad-extra"><span class="dashicons dashicons-plus"></span></button></td>
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

		<tr >
			<th scope="row">Google kalender id</th>
			<td><input type="text" name="kleistad-opties[google_kalender_id]"
					value="<?php echo esc_attr( $this->options['google_kalender_id'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Google client id</th>
			<td><input type="text" name="kleistad-opties[google_client_id]"
					value="<?php echo esc_attr( $this->options['google_client_id'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Google geheime sleutel</th>
			<td><input type="text" name="kleistad-opties[google_sleutel]"
					value="<?php echo esc_attr( $this->options['google_sleutel'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Email IMAP server ( als imap.example.com:poortnr/ssl )</th>
			<td><input type="text" name="kleistad-opties[imap_server]"
					value="<?php echo esc_attr( $this->options['imap_server'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Email IMAP paswoord</th>
			<td><input type="text" name="kleistad-opties[imap_pwd]"
					value="<?php echo esc_attr( $this->options['imap_pwd'] ); ?>" /></td>
		</tr>

	</table>
	<p class="submit">
		<?php submit_button(); ?>
	</p>
</form>
