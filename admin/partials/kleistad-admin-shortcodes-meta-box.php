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
<div class="card">
	De shortcodes zijn:
	<ul style="list-style-type:none">
		<li><h3>publiek toegankelijk (dus zonder ingelogd te zijn)</h3>
			<ul style="list-style-type:square">
				<li>[kleistad_abonnee_inschrijving verklaring=''] inschrijving als abonnee</li>
				<li>[kleistad_dagdelenkaart verklaring=''] aankoop dagdelenkaart</li>
				<li>[kleistad_cursus_inschrijving] inschrijving voor cursus</li>
				<li>[kleistad_recept] overzicht van keramiek recepten</li>
			</ul>
		</li>
		<li><h3>toegankelijk voor leden</h3>
			<ul style="list-style-type:square">
				<li>[kleistad_reservering oven=1] reserveren ovenstook</li>
				<li>[kleistad_rapport] overzicht stook activiteiten door lid</li>
				<li>[kleistad_saldo] wijzigen stooksaldo door lid</li>
				<li>[kleistad_registratie] wijzigen adresgegevens door lid</li>
				<li>[kleistad_recept_beheer] wijzigen keramiek recepten door lid</li>
			</ul>
		</li>
		<li><h3>toegankelijk voor bestuur</h3>
			<ul style="list-style-type:square">
				<li>[kleistad_saldo_overzicht] overzicht stooksaldo leden</li>
				<li>[kleistad_stookbestand] opvragen stookbestand</li>
				<li>[kleistad_registratie_overzicht] overzicht van alle cursisten en leden</li>
				<li>[kleistad_cursus_beheer] formulier om cursussen te beheren </li>
				<li>[kleistad_betalingen] formulier om betalingen cursisten te registreren</li>
			</ul>
		</li>
	</ul>
	<p>bij de optionele verklaring parameter bij <strong>kleistad_abonnee_inschrijving</strong> en <strong>kleistad_dagdelenkaart</strong> kan bijvoorbeeld ingevuld worden:</p>
	<code><?php echo htmlspecialchars( 'ik heb de <a href="https://www.kleistad.nl/wp/wp-content/uploads/2017/08/Huisregels-inloop-atelier-KLEISTAD-aug2017.pdf" target="_blank" rel="noopener">Huisregels inloop atelier KLEISTAD -aug2017</a> gelezen' ); // WPCS: XSS ok. ?></code>

</div>
