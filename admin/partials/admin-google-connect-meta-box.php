<?php
/**
 * Toon de google connect meta box
 *
 * @link       https://www.kleistad.nl
 * @since      5.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

if ( is_wp_error( $result ) ) {
	foreach ( $result->get_error_messages() as $fout ) {
		?>
		<div class="error"><p><?php echo esc_html( $fout ); ?></p></div>
		<?php
	}
}
?>

<form method="POST" >
	<p>Huidige status: <strong><?php echo \Kleistad\Google::is_authorized() ? 'gekoppeld' : 'niet gekoppeld'; ?></strong></p>
	<hr/>
	<p>Om gekoppeld te zijn aan de Google Kalender moet zowel kalender id, client id en client sleutel in het instellingen scherm ingevuld worden.
	Zonder koppeling is de kalender via de shortcode 'kleistad_kalender' niet zichtbaar en zullen workshops en cursussen niet in de Google kalender worden vastgelegd.
	Nadat de koppeling gemaakt is kunnen bestaande workshops en cursussen die nog niet in de kalender zijn opgenomen wel worden toegevoegd.
	Open daarvoor de cursus of workshop en sla deze op (er hoeven geen parameters gewijzigd te worden).</p>
	<p>Met onderstaande knop wordt gelinkt naar Google. Zorg dan dat je ingelogd bent op het juiste Google account en geef dan toestemming tot de toegang van Kleistad tot de kalender</p>
	<p class="submit" >
		<?php submit_button( 'Google Kalender koppelen', 'primary', 'connect' ); ?>
	</p>
	<p>&nbsp;</p>
</form>
