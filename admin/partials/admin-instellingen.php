<?php
/**
 * Toon het instellingen formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

if ( 'f_instellingen' === $active_tab ) :
	?>
<form method="POST" action="options.php" >
	<?php settings_fields( 'kleistad-opties' ); ?>
	<table class="form-table" >
		<tr >
			<th scope="row">Prijs onbeperkt abonnement</th>
			<td><input type="number" step="0.01" name="kleistad-opties[onbeperkt_abonnement]" class="small-text"
					value="<?php echo esc_attr( $this->options['onbeperkt_abonnement'] ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row">Prijs beperkt abonnement</th>
			<td><input type="number" step="0.01"  name="kleistad-opties[beperkt_abonnement]" class="small-text"
					value="<?php echo esc_attr( $this->options['beperkt_abonnement'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs dagdelenkaart</th>
			<td><input type="number" step="0.01" min="0"  name="kleistad-opties[dagdelenkaart]" class="small-text"
					value="<?php echo esc_attr( $this->options['dagdelenkaart'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs standaaard cursus excl. inschrijving</th>
			<td><input type="number" step="0.01" min="0" name="kleistad-opties[cursusprijs]" class="small-text"
					value="<?php echo esc_attr( $this->options['cursusprijs'] ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row">Prijs cursus inschrijving</th>
			<td><input type="number" step="0.01" min="0"  name="kleistad-opties[cursusinschrijfprijs]" class="small-text"
					value="<?php echo esc_attr( $this->options['cursusinschrijfprijs'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Prijs standaard workshop</th>
			<td><input type="number" step="0.01" min="0"  name="kleistad-opties[workshopprijs]" class="small-text"
					value="<?php echo esc_attr( $this->options['workshopprijs'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Standaard maximum cursisten per cursus/workshop</th>
			<td><input type="number" step="1" min="1"  max="99" name="kleistad-opties[cursusmaximum]" class="small-text"
					value="<?php echo esc_attr( $this->options['cursusmaximum'] ); ?>" /></td>
		</tr>

		<?php
		$i = 1;
		while ( isset( $this->options['extra'][ $i ]['naam'] ) ) :
			?>
		<tr >
			<th scope="row">Abonnement extra <?php echo esc_html( $i ); ?></th>
			<td><input type="text" class="kleistad-extra regular-text" name="kleistad-opties[extra][<?php echo esc_attr( $i ); ?>][naam]"
					value="<?php echo esc_attr( $this->options['extra'][ $i ]['naam'] ); ?>"  <?php echo ! empty( $this->options['extra'][ $i ]['naam'] ) ? 'readonly' : ''; ?> /></td>
			<th scope="row">Prijs</th>
			<td><input type="number" step="0.01" min="0"  name="kleistad-opties[extra][<?php echo esc_attr( $i ); ?>][prijs]" class="small-text"
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
			<th scope="row">Termijn (dagen) dat correctie stook mogelijk is</th>
			<td><input type="number" min="0"  name="kleistad-opties[termijn]"
					value="<?php echo esc_attr( $this->options['termijn'] ); ?>" class="tiny-text" /></td>
		</tr>

		</table>
	<?php submit_button(); ?>
	<p>&nbsp;</p>
	</form>
<?php elseif ( 't_instellingen' === $active_tab ) : ?>
	<form method="POST" action="options.php" >
	<?php settings_fields( 'kleistad-setup' ); ?>
	<table class="form-table" >
		<tr >
			<th scope="row">Mollie geheime sleutel</th>
			<td><input type="text" name="kleistad-setup[sleutel]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['sleutel'] ); ?>" /></td>
			<th scope="row">Mollie geheime sleutel voor testen</th>
			<td><input type="text" name="kleistad-setup[sleutel_test]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['sleutel_test'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Mollie betalen actief</th>
			<td>
				<p>
				<label>
				<input type="radio" name="kleistad-setup[betalen]"
					value="0" <?php checked( 0, $this->setup['betalen'] ); ?>/>Uit
				</label><br>
				<label>
				<input type="radio" name="kleistad-setup[betalen]"
					value="1" <?php checked( 1, $this->setup['betalen'] ); ?>/>Aan
				</label>
				</p>
			</td>
		</tr>

		<tr >
			<th scope="row">Google kalender id</th>
			<td><input type="text" name="kleistad-setup[google_kalender_id]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['google_kalender_id'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Google client id</th>
			<td><input type="text" name="kleistad-setup[google_client_id]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['google_client_id'] ); ?>" /></td>
			<th scope="row">Google geheime sleutel</th>
			<td><input type="text" name="kleistad-setup[google_sleutel]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['google_sleutel'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Email IMAP server</th>
			<td><input type="text" name="kleistad-setup[imap_server]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['imap_server'] ); ?>" /><p class="example">imap.example.com:poortnr/ssl</p></td>
			<th scope="row">Email IMAP paswoord</th>
			<td><input type="text" name="kleistad-setup[imap_pwd]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['imap_pwd'] ); ?>" /></td>
		</tr>
	</table>
	<?php submit_button(); ?>
	<p>&nbsp;</p>
</form>
<?php endif ?>
