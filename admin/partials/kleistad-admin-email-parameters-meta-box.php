<?php
/**
 * Toon de emails en email parameters
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<div class="card">
	<ul style="list-style-type:none">
		<li><h3>kleistad_email_saldo_wijziging_ideal / kleistad_email_saldo_wijziging_bank</h3>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de stoker</li>
				<li>[achternaam] : achternaam van de stoker</li>
				<li>[bedrag] : bedrag dat overgemaakt wordt of via iDEAL is betaald</li>
				<li>[saldo] : huidig saldo</li>
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
		<li><h3>kleistad_email_abonnement_* emails</h3>
			<ol>
				<li>start_ideal: bevesting start via ideal betaling</li>
				<li>start_bank: start betalen per bank met instructie</li>
				<li>vervolg: einde 3 maand period in zicht</li>
				<li>gewijzigd: gepauzeerd, herstart of beëindigd door de abonnee</li>
				<li>betaalwijze_ideal: bevestiging voortaan betalen per sepa incasso</li>
				<li>betaalwijze_bank: voortaan betalen per bank met instructie</li>
			</ol>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de abonnee</li>
				<li>[achternaam] : achternaam van de abonnee</li>
				<li>[loginnaam] : loginnaam van de abonnee</li>
				<li>[start_datum] : datum waarop abonnement moet ingaan</li>
				<li>[pauze_datum] : datum waarop abonnement gepauzeerd wordt</li>
				<li>[herstart_datum] : datum waarop abonnement herstart wordt</li>
				<li>[eind_datum] : datum waarop abonnement beëindigd wordt</li>
				<li>[abonnement] : soort abonnement (beperkt of onbeperkt</li>
				<li>[abonnement_code] : code te vermelden bij betaling</li>
				<li>[abonnement_dag] : dag waarvoor beperkt abonnement geldt</li>
				<li>[abonnement_opmerking] : door abonnee geplaatste opmerking</li>
				<li>[abonnement_wijziging] : de wijziging (pauzeren, herstarten of beëindigen)</li>
				<li>[abonnement_borg] : door abonnee te betalen borg</li>
				<li>[abonnement_startgeld] : driemaal het maand abonnee bedrag</li>
				<li>[abonnement_maandgeld] : het maand abonnee bedrag</li>
			</ul>
		</li>
		<li><h3>kleistad_email_dagdelenkaart_ideal en kleistad_email_dagdelenkaart_bank</h3>
			<ul>
				<li>[voornaam] : voornaam van de gebruiker</li>
				<li>[achternaam] : achternaam van de gebruiker</li>
				<li>[dagdelenkaart_code] : code te vermelden bij betaling</li>
				<li>[dagdelenkaart_opmerking] : door gebruiker geplaatste opmerking</li>
				<li>[start_datum] : datum waarop de dagdelenkaart moet ingaan</li>
			</ul>
		</li>
		<li><h3>kleistad_email_cursus_* emails</h3>
			<ol>
				<li>'Inschrijf email' : bij inschrijving via bank; paginanaam aanpassen in cursus beheer</li>
				<li>'Indeling email' : bij indeling via cursus beheer of inschrijving via iDeal; paginanaam aanpassen cursus beheer</li>
				<li>kleistad_email_cursus_lopend : instructie bij inschrijving op lopende cursus</li>
				<li>kleistad_email_cursus_betaling : betalen resterend cursusgeld via email link</li>
				<li>kleistad_email_cursus_betaling_ideal : bevestiging betaling restant bedrag</li>
			</ol>
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
				<li>[cursus_aantal] : aantal ingeschreven cursisten</li>
				<li>[cursus_kosten] : kosten exclusief inschrijfgeld</li>
				<li>[cursus_inschrijfkosten] : inschrijf kosten</li>
				<li>[cursus_opmerking] : de gemaakte opmerking</li>
				<li>[cursus_link] : link naar betaling restant cursus bedrag</li>
			</ul>
		</li>
	</ul>
</div>
