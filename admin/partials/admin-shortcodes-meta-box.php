<?php
/**
 * Toon de shortcodes meta box
 *
 * @link       https://www.kleistad.nl
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
				<li>[kleistad_abonnee_inschrijving verklaring=''] inschrijving als abonnee (verklaring parameter is optioneel)</li>
				<li>[kleistad_cursus_inschrijving cursus=C11,C22,.. verbergen="tekst"] inschrijving voor cursus (cursus parameter is optioneel). Met de optionele parameterverbergen wordt het formulier verborgen en de tekst getoond.</li>
				<li>[kleistad_dagdelenkaart verklaring=''] aankoop dagdelenkaart (verklaring parameter is optioneel)</li>
				<li>[kleistad_recept] overzicht van keramiek recepten</li>
				<li>[kleistad_workshop_aanvraag] aanvraag voor workshops en kinderfeestjes</li>
			</ul>
		</li>
		<li><h3>toegankelijk voor leden</h3>
			<ul style="list-style-type:square">
				<li>[kleistad_abonnee_wijziging] wijzigen abonnement door lid</li>
				<li>[kleistad_kalender] overzicht workshops en cursussen</li>
				<li>[kleistad_rapport] overzicht stook activiteiten door lid</li>
				<li>[kleistad_recept_beheer] wijzigen keramiek recepten door lid</li>
				<li>[kleistad_registratie] wijzigen adresgegevens door lid</li>
				<li>[kleistad_reservering oven=1] reserveren ovenstook (oven parameter is verplicht)</li>
				<li>[kleistad_saldo] wijzigen stooksaldo door lid</li>
			</ul>
		</li>
		<li><h3>toegankelijk voor bestuur</h3>
			<ul style="list-style-type:square">
				<li>[kleistad_abonnement_overzicht] overzicht abonnees</li>
				<li>[kleistad_betalingen] formulier om betalingen cursisten en workshops te registreren</li>
				<li>[kleistad_cursus_beheer] formulier om cursussen te beheren</li>
				<li>[kleistad_cursus_overzicht] overzicht cursussen en cursist per cursus</li>
				<li>[kleistad_email] formulier om emails naar abonnees en/of cursisten te sturen</li>
				<li>[kleistad_registratie_overzicht] overzicht van alle cursisten en leden</li>
				<li>[kleistad_saldo_overzicht] overzicht stooksaldo leden</li>
				<li>[kleistad_stookbestand] opvragen stookbestand</li>
				<li>[kleistad_workshop_beheer] formulier om workshops te beheren</li>
			</ul>
		</li>
		<li><h3>toegankelijk voor een cursist</h3>
			<ul style="list-style-type:square">
				<li>[kleistad_betaling] het betalen van de restant betaling (vanuit email)</li>
			</ul>
		</li>
	</ul>
	<p>bij de optionele verklaring parameter bij <strong>kleistad_abonnee_inschrijving</strong> en <strong>kleistad_dagdelenkaart</strong> kan bijvoorbeeld ingevuld worden:</p>
	<code><?php echo htmlspecialchars( 'ik heb de <a href="https://www.kleistad.nl/wp/wp-content/uploads/2017/08/Huisregels-inloop-atelier-KLEISTAD-aug2017.pdf" target="_blank" rel="noopener">Huisregels inloop atelier KLEISTAD -aug2017</a> gelezen' ); // phpcs:ignore ?></code>
	<p>bij de optionele cursus parameter bij <strong>kleistad_cursus_inschrijving</strong> moet een cursus code opgegeven worden, bijvoorbeeld <code>C29</code>. In dat geval wordt er overzicht van cursussen getoond maar alleen de cursus waar het om gaat.</p>
</div>
