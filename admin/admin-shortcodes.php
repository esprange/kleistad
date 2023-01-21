<?php
/**
 * Shortcodes
 *
 * @link       https://www.kleistad.nl
 * @since      7.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

?>
<ul style="list-style-type:none">
	<li><h3>publiek toegankelijk (dus zonder ingelogd te zijn)</h3>
		<ol>
			<li>[kleistad_abonnee_inschrijving] inschrijving als abonnee
				<ul style="list-style-type:square;margin-left:25px">
					<li>verklaring= De optionele verklaring die goedgekeurd moet worden.
						<p>Bijvoorbeeld: <code>verklaring=<?php echo htmlspecialchars( 'ik heb de <a href="https://www.kleistad.nl/.. ..huisregels.pdf" target="_blank" rel="noopener">Huisregels</a> gelezen' ); // phpcs:ignore ?></code></p>
					</li>
				</ul>
			</li>
			<li>[kleistad_cursus_inschrijving] inschrijving voor cursus.
				<ul style="list-style-type:square;margin-left:25px">
					<li>cursus= De specifieke cursussen die getoond moeten worden i.p.v. alle gepubliceerde cursussen.
						<p>Bijvoorbeeld: <code>cursus=C11,C22</code></p>
					</li>
					<li>verbergen= Verbergt het formulier en toont in plaats daarvan de tekst. Als op de tekst geklikt wordt, wordt het formulier zichtbaar.
						<p>Bijvoorbeeld: <code>verbergen=Inschrijven voor cursus</code></p>
					</li>
				</ul>
			</li>
			<li>[kleistad_cursus_extra] het invoeren van de gegevens van extra cursus deelnemers</li>
			<li>[kleistad_dagdelenkaart] aankoop dagdelenkaart
				<ul style="list-style-type:square;margin-left:25px">
					<li>verklaring= De optionele verklaring die goedgekeurd moet worden.
						<p>Bijvoorbeeld: <code>verklaring=<?php echo htmlspecialchars( 'ik heb de <a href="https://www.kleistad.nl/.. ..huisregels.pdf" target="_blank" rel="noopener">Huisregels</a> gelezen' ); // phpcs:ignore ?></code><p>
					</li>
				</ul>
			</li>
			<li>[kleistad_recept] overzicht van keramiek recepten</li>
			<li>[kleistad_workshop_aanvraag] aanvraag voor workshops, kinderfeestjes, etc.</li>
			<li>[kleistad_betaling] het betalen van een uitstaand bedrag per iDeal (via link vanuit email)</li>
			<li>[kleistad_contact] het contact formulier</li>
			<li>[kleistad_showcase_gallerij] de gallerij van voor verkoop aangeboden werkstukken</li>
		</ol>
	</li>
	<li><h3>toegankelijk voor leden</h3>
		<ol>
			<li>[kleistad_abonnee_wijziging] wijzigen abonnement door lid</li>
		</ol>
	</li>
	<li><h3>toegankelijk voor leden, verkoop en bestuur</h3>
		<ol>
			<li>[kleistad_showcase_beheer] aanmelden werkstukken voor verkoop door lid
			<ul style="list-style-type:square;margin-left:25px">
				<li>actie=verkoop. Inplannen tentoonstellen werkstukken door verkoop commissie</li>
			</ul>
			</li>
		</ol>
	</li>
	<li><h3>toegankelijk voor leden, docenten en bestuur</h3>
		<ol>
			<li>[kleistad_kalender] overzicht workshops en cursussen</li>
			<li>[kleistad_rapport] overzicht stook activiteiten</li>
			<li>[kleistad_recept_beheer] wijzigen keramiek recepten</li>
			<li>[kleistad_registratie] wijzigen adresgegevens</li>
			<li>[kleistad_reservering] reserveren ovenstook
				<ul style="list-style-type:square;margin-left:25px">
					<li>oven= Het ovennummer (verplicht)
						<p>Bijvoorbeeld <code>oven=3</code></p>
					</li>
				</ul>
			</li>
			<li>[kleistad_saldo] wijzigen saldo</li>
			<li>[kleistad_werkplek] reserveren van een werkplek</li>
			<li>[kleistad_werkplekrapport] overzichten werkplekgebruik
				<ul style="list-style-type:square;margin-left:25px">
					<li>actie=overzicht. Overzicht van gebruik werkplekken door alle gebruikers. Alleen door bestuur te gebruiken</li>
					<li>actie=individueel. Gebruik werkplekken door een specifieke gebruiker. Alleen door bestuur te gebruiken</li>
					<li>actie=reserveringen. Gebruik werkplekken door de ingelogde gebruiker.</li>
				</ul>
			</li>
		</ol>
	</li>
	<li><h3>toegankelijk voor docenten</h3>
		<ol>
			<li>[kleistad_docent] formulier om de beschikbaarheid van de docent voor workshops aan te geven</li>
		</ol>
	</li>
	<li><h3>toegankelijk voor docenten en bestuur</h3>
		<ol>
			<li>[kleistad_email] formulier om emails naar abonnees en/of cursisten te sturen</li>
			<li>[kleistad_cursus_overzicht] overzicht cursussen en cursist per cursus</li>
			<li>[kleistad_cursus_verbruik] formulier om materialen verbruik cursist te registreren</li>
		</ol>
	</li>
	<li><h3>toegankelijk voor bestuur</h3>
		<ol>
			<li>[kleistad_abonnement_overzicht] overzicht abonnees</li>
			<li>[kleistad_cursus_beheer] formulier om cursussen te beheren</li>
			<li>[kleistad_omzet_rapportage] overzicht omzet op maandbasis</li>
			<li>[kleistad_registratie_overzicht] overzicht van alle cursisten en leden</li>
			<li>[kleistad_saldo_overzicht] overzicht saldo cursisten en leden</li>
			<li>[kleistad_stookbestand] opvragen stookbestand</li>
			<li>[kleistad_verkoop] verkoop overige artikelen invoeren</li>
			<li>[kleistad_workshop_beheer] formulier om workshops te beheren</li>
		</ol>
	</li>
	<li><h3>toegankelijk voor boekhouder</h3>
		<ol>
			<li>[debiteuren] overzicht openstaande orders
				<ul style="list-style-type:square;margin-left:25px">
					<li>actie=zoek. Met zoekfunctie die ook gesloten orders toont</li>
					<li>actie=blokkade. Om een kwartaal af te sluiten</li>
					<li>actie=aanmanen. Om een overzicht te hebben van orders waarvan de vervaldatum verstreken is</li>
				</ul>
			</li>
		</ol>
	</li>
</ul>
