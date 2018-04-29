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
	<ul style="list-style-type:none">
		<li><h3>kleistad_email_saldo_wijziging / kleistad_email_saldo_wijziging_betaald</h3>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de stoker</li>
				<li>[achternaam] : achternaam van de stoker</li>
				<li>[bedrag] : bedrag dat overgemaakt wordt of via iDEAL is betaald</li>
			</ul>
		</li>
		<li><h3>kleistad_email_stookmelding</h3>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de hoofdstoker</li>
				<li>[achternaam] : achternaam van de hoofdstoker</li>
				<li>[bedrag] : bruto bedrag van de stook</li>
				<li>[datum_verwerking] : datum waarop kosten afgeboekt worden</li>
				<li>[datum_deadline] : laatste datum waarop verdeling aangepast kan worden</lI>
				<li>[stookoven] : naam van de oven</li>
			</ul>
		</li>
		<li><h3>kleistad_email_stookkosten_verwerkt</h3>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de medestoker</li>
				<li>[achternaam] : achternaam van de medestoker</li>
				<li>[stoker] : naam van de hoofdstoker</li>
				<li>[stookdeel] : percentage van de stook</li>
				<li>[stookdatum] : datum waarop de stook gestart is</li>
				<li>[stookoven] : naam van de oven</li>
				<li>[bedrag] : bedrag van de stookkosten voor de medestoker</li>
				<li>[saldo] : nieuw saldo van de medestoker</li>
			</ul>						
		</li>
		<li><h3>kleistad_email_abonnement en kleistad_email_abonnement_betaald</h3>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de abonnee</li>
				<li>[achternaam] : achternaam van de abonnee</li>
				<li>[start_datum] : datum waarop abonnement moet ingaan</li>
				<li>[abonnement] : soort abonnement (beperkt of onbeperkt</li>
				<li>[abonnement_code] : code te vermelden bij betaling</li>
				<li>[abonnement_dag] : dag waarvoor beperkt abonnement geldt</li>
				<li>[abonnement_opmerking] : door abonnee geplaatste opmerking</li>
				<li>[abonnement_startgeld] : driemaal het maand abonnee bedrag</li>
				<li>[abonnement_maandgeld] : het maand abonnee bedrag</li>
			</ul>
		</li>
		<li><h3>'cursus indeling email'</h3>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de cursist</li>
				<li>[achternaam] : achternaam van de cursist</li>
				<li>[cursus_naam] : titel van de cursus</li>
				<li>[cursus_docent] : naam van de docent</li>
				<li>[cursus_start_datum] : start van de cursus</li>
				<li>[cursus_start_tijd] : start tijd van de cursus</li>
				<li>[cursus_eind_datum] : einde van de cursus</li>
				<li>[cursus_eind_tijd] : eind tijd van de cursus</li>
				<li>[cursus_technieken] : gekozen technieken</li>
				<li>[cursus_code] : code te vermelden bij betaling</li>
				<li>[cursus_kosten] : kosten exclusief inschrijfgeld</li>
				<li>[cursus_inschrijfkosten] : inschrijf kosten</li>
			</ul>
		</li>
		<li><h3>'cursus inschrijving email'</h3>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de cursist</li>
				<li>[achternaam] : achternaam van de cursist</li>
				<li>[cursus_naam] : titel van de cursus</li>
				<li>[cursus_docent] : naam van de docent</li>
				<li>[cursus_start_datum] : start van de cursus</li>
				<li>[cursus_start_tijd] : start tijd van de cursus</li>
				<li>[cursus_eind_datum] : einde van de cursus</li>
				<li>[cursus_eind_tijd] : eind tijd van de cursus</li>
				<li>[cursus_technieken] : gekozen technieken</li>
				<li>[cursus_opmerking] : door cursist geplaatste opmerking</li>
				<li>[cursus_code] : code te vermelden bij betaling</li>
				<li>[cursus_kosten] : kosten exclusief inschrijfgeld</li>
				<li>[cursus_inschrijfkosten] : inschrijf kosten</li>
			</ul>
		</li>
	</ul>
</div>
