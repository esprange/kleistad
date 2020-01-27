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
<ul style="list-style-type:none">
<li><h3>abonnement_* emails</h3>
		<ol>
			<li>gewijzigd: gepauzeerd, herstart of beëindigd door de abonnee</li>
			<li>ideal: bevestiging ideal betaling via betaal link</li>
			<li>regulier_bank: de maandelijkse factuur en betalen per bank met instructie</ll>
			<li>regulier_incasso: de maandelijkse factuur en bevestiging betaling</li>
			<li>regulier_incasso_mislukt: de maandelijkse factuur omdat incasso mislukt en betalen per bank met instructie</li>
			<li>start_ideal: bevesting start na ideal betaling</li>
			<li>start_bank: start betalen per bank met instructie</li>
			<li>vervolg: einde 3 maand periode in zicht</li>
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
				<li>[abonnement_wijziging] : de wijziging (pauzeren of beëindigen)</li>
				<li>[abonnement_extras] : de extras bij het abonnement</li>
				<li>[abonnement_startgeld] : driemaal het maand abonnee bedrag</li>
				<li>[abonnement_maandgeld] : het maand abonnee bedrag</li>
				<li>[abonnement_overbrugging] : het maand abonnee bedrag</li>
				<li>[abonnement_bedrag] : te betalen bedrag</li>
				<li>[abonnement_link] : de betaal link</li>
			</ul>
		</ol>
	</li>
	<li><h3>bestelling_* emails</h3>
		<ol>
			<li>bestelling : bestelling inclusief factuur</li>
			<li>bestelling_ideal : bevestiging ideal betaling</li>
			<ul style="list-style-type:square">
				<li>[naam] : voornaam van de klant</li>
				<li>[bedrag] : te betalen bedrag</li>
				<li>[bestel_link] : link naar ideal betaling</li>
			</ul>
		</ol>
	</li>
	<li><h3>cursus_* emails</h3>
		<ol>
			<li>'Inschrijf email' : bij inschrijving via bank; paginanaam aanpassen in cursus beheer</li>
			<li>'Indeling email' : bij indeling via cursus beheer of inschrijving via iDeal; paginanaam aanpassen cursus beheer</li>
			<li>cursus_lopend : instructie bij inschrijving op lopende cursus</li>
			<li>cursus_lopend_betalen : bij indeling na aanbieden prijs op lopende cursus</li>
			<li>cursus_restant : betalen resterend cursusgeld via email link</li>
			<li>cursus_herinnering : herinnering betaling resterend cursusgeld via email link</li>
			<li>cursus_ideal : bevestiging betaling bedrag</li>
			<li>cursus_wijziging: bevestiging aanpassing inschrijving naar andere cursus</li>
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
		</ol>
	</li>
	<li><h3>dagdelenkaart_* emails</h3>
		<ol>
			<li>dagdelenkaart_bank : aanvraag dagdelenkaart</li>
			<li>dagdelenkaart_ideal : bevesting betaling dagdelenkaart per ideal</li>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de gebruiker</li>
				<li>[achternaam] : achternaam van de gebruiker</li>
				<li>[start_datum] : datum waarop de dagdelenkaart moet ingaan</li>
				<li>[dagdelenkaart_code] : code te vermelden bij betaling</li>
				<li>[dagdelenkaart_opmerking] : door gebruiker geplaatste opmerking</li>
				<li>[dagdelenkaart_prijs] : kosten van de dagdelenkaart</li>
				<li>[dagdelenkaart_link] : link naar betaling per ideal</li>
			</ul>
		</ol>
	</li>
	<li><h3>order_* emails</h3>
		<ol>
			<li>order_correctie : correctie order</li>
			<li>order_annuliering : annulering order</li>
			<ul style="list-style-type:square">
				<li>[naam] : naam van de klant</li>
				<li>[artikel] : artikel dat gecorrigeerd of geannuleerd is</li>
			</ul>
		</ol>
	</li>
	<li><h3>saldo_* emails</h3>
		<ol>
			<li>saldo_bank : aanvraag saldo</li>
			<li>saldo_ideal : bevesting betaling saldo per ideal</li>
			<ul style="list-style-type:square">
				<li>[voornaam] : voornaam van de stoker</li>
				<li>[achternaam] : achternaam van de stoker</li>
				<li>[bedrag] : bedrag dat overgemaakt wordt of via iDEAL is betaald</li>
				<li>[saldo] : huidig saldo</li>
				<li>[saldo_link] : link naar betaling per ideal</li>
			</ul>
		</ol>
	</li>
	<li><h3>stook_* emails</h3>
		<ol>
			<li>melding<
				<ul style="list-style-type:square">
					<li>[voornaam] : voornaam van de hoofdstoker</li>
					<li>[achternaam] : achternaam van de hoofdstoker</li>
					<li>[bedrag] : bruto bedrag van de stook</li>
					<li>[verdeling] : verdeling van de stook zoals op moment van verzending van de email</li>
					<li>[datum_verwerking] : datum waarop kosten afgeboekt worden</li>
					<li>[datum_deadline] : laatste datum waarop verdeling aangepast kan worden</lI>
					<li>[stookoven] : naam van de oven</li>
				</ul>
			</li>
			<li>kosten_verwerkt
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
		</ol>
	</li>
	<li><h3>workshop_* emails</h3>
		<ol>
			<li>workshop_afzegging : afzegging van de workshop</li>
			<li>workshop_bevestiging : bevestiging van de gemaakte afspraken voor de workshop</li>
			<li>workshop_betaling : betalen workshop kosten via email link</li>
			<li>workshop_ideal : bevestiging betaling workshop kosten per ideal</li>
			<ul style="list-style-type:square">
				<li>[contact] : contactpersoon van de workshop aanvraag</li>
				<li>[organisatie] : organisatie welke de workshop aanvraagt</li>
				<li>[naam] : titel van de cursus ('de workshop' of 'het kinderfeest')</li>
				<li>[aantal] : aantal deelnemers</li>
				<li>[workshop_docent] : naam van de docent</li>
				<li>[workshop_datum] : datum van de workshop</li>
				<li>[workshop_start_tijd] : start tijd van de workshop</li>
				<li>[workshop_eind_tijd] : eind tijd van de workshop</li>
				<li>[workshop_technieken] : gekozen technieken</li>
				<li>[workshop_programma] : beschrijving van het programma van de workshop</li>
				<li>[workshop_code] : code te vermelden bij betaling</li>
				<li>[workshop_kosten] : kosten</li>
				<li>[workshop_link] : link naar betaling workshop bedrag</li>
			</ul>
		</ol>
	</li>
	<li><h3>workshop_aanvraag_* emails</h3>
		<ol>
			<li>bevestiging
				<ul style="list-style-type:square">
					<li>[contact] : naam van de aanvrager</li>
					<li>[naam] : titel van de cursus ('de workshop' of 'het kinderfeest' )</li>
					<li>[periode] : aangegeven periode</li>
					<li>[omvang] : aangegeven aantal deelnemers</li>
					<li>[email] : opgegeven email adres</li>
					<li>[telefoon] : opgegeven telefoon nummer</li>
				</ul>
			</li>
			<li>workshop_aanvraag_reactie
				<ul style="list-style-type:square">
					<li>[reactie] : de reactie op de vraag van de aanvrager</li>
				</ul>
			</li>
		</ol>
	</li>
	<li><h3>Generieke emails</h3>
		<ol>
			<li>email_wijziging : De aanpassing van het email adres</li>
			<li>wachtwoord_wijziging : De aanpassing een wachtwoord</li>
			<ul style="list-style-type:square">
				<li>[voornaam] : De voornaam van de gebruiker</li>
				<li>[achternaam] : De achternaam van de gebruiker</li>
				<li>[email] : Het nieuwe email adres van de gebruiker</li>
			</ul>
		</ol>
	</li>
</ul>
