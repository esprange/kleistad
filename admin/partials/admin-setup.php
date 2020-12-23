<?php
/**
 * Toon het setup formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

namespace Kleistad;

?>
<div style="float:left;width:50%;">
<form method="POST" action="options.php" >
	<?php settings_fields( 'kleistad-setup' ); ?>
	<table class="form-table">
		<tr>
			<th scope="row">Mollie geheime sleutel</th>
			<td><input type="text" name="kleistad-setup[sleutel]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['sleutel'] ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row">Mollie geheime sleutel voor testen</th>
			<td><input type="text" name="kleistad-setup[sleutel_test]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['sleutel_test'] ); ?>" /></td>
		</tr>

		<tr>
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

		<tr>
			<th scope="row">Google kalender id</th>
			<td><input type="text" name="kleistad-setup[google_kalender_id]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['google_kalender_id'] ); ?>" /></td>
		</tr>

		<tr>
			<th scope="row">Google client id</th>
			<td><input type="text" name="kleistad-setup[google_client_id]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['google_client_id'] ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row">Google geheime sleutel</th>
			<td><input type="text" name="kleistad-setup[google_sleutel]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['google_sleutel'] ); ?>" /></td>
		</tr>

		<tr >
			<th scope="row">Email IMAP server</th>
			<td><input type="text" name="kleistad-setup[imap_server]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['imap_server'] ); ?>" /><p class="example">imap.example.com:poortnr/ssl</p></td>
		</tr>
		<tr>
			<th scope="row">Email IMAP paswoord</th>
			<td><input type="text" name="kleistad-setup[imap_pwd]" class="regular-text"
					value="<?php echo esc_attr( $this->setup['imap_pwd'] ); ?>" /></td>
		</tr>
	</table>
	<?php submit_button(); ?>
	<p>&nbsp;</p>
</form>
</div>
<div class="card" style="float:right;width:50%;" >
<form method="POST" >
	<p>Huidige Google connectie status: <strong><?php echo Googleconnect::is_authorized() ? 'gekoppeld' : 'niet gekoppeld'; ?></strong></p>
	<hr/>
	<p>Zonder koppeling is de kalender via de shortcode 'kleistad_kalender' niet zichtbaar en zullen workshops en cursussen niet in de Google kalender worden vastgelegd.
	Nadat de koppeling gemaakt is kunnen bestaande workshops en cursussen die nog niet in de kalender zijn opgenomen wel worden toegevoegd.
	Open daarvoor de cursus of workshop en sla deze op (er hoeven geen parameters gewijzigd te worden).</p>
	<p>Met onderstaande knop wordt gelinkt naar Google. Zorg dan dat je ingelogd bent op het juiste Google account en geef dan toestemming tot de toegang van Kleistad tot de kalender</p>
	<?php submit_button( 'Google Kalender koppelen', 'primary', 'connect', true, disabled( Googleconnect::is_authorized(), true, false ) ); ?>
	<p>&nbsp;</p>
</form>
</div>
<div class="card" style="float:right;width:50%;" >
<form method="POST" >
	<p>Forceer dagelijkse job</p>
	<hr/>
	<p>Elke dag wordt om 9:00 een job gestart die kijkt of er herinneringen, stookmeldingen, verzoeken om restant betaling, incasso's e.d. nodig zijn.</p>
	<p>Met onderstaande knop kan deze handeling op elk moment geforceerd worden. De job bevat logica die er voor zorgt dat een handeling niet dubbel wordt verricht,
	hoe vaak de job ook per dag gestart wordt.</p>
	<?php submit_button( 'Dagelijkse job uitvoeren', 'primary', 'dagelijks' ); ?>
	<p>&nbsp;</p>
</form>
</div>
