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

?>
<div class="card" style="position:absolute;left:50%;width:50%;z-index:2" >
<form method="POST" enctype="multipart/form-data">
	<p>Lees beschikbaarheid voor Corona reserveringen</p>
	<hr/>
	<p>Maak met excel een bestand met het volgende formaat en sla dit op als .csv bestand. Laad het bestand om de beschikbaarheid aan te geven.</p>
	<div style="float:left">
	<img src="<?php echo plugin_dir_url( __DIR__ ); //phpcs:ignore ?>images/corona.png"
		style="width:200px" alt="dag als d-m-y, starttijd, eindtijd, aantal draaien, aantal handvormen" >
	</div>
	<div style="float:right">
	<input type="file" name="corona_file" accept=".csv" ><br/>
	<?php submit_button( 'Bestand laden', 'primary', 'corona' ); ?>
	</div>
</form>
</div>

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
			<th scope="row">Prijs standaard cursus excl. inschrijving</th>
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
