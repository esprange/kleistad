<?php
/**
 * Email parameters
 *
 * @link       https://www.kleistad.nl
 * @since      7.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

?>
<ul style="list-style-type:none">
	<li><h3>abonnement_* emails</h3>
		<ol>
			<li>gewijzigd: gepauzeerd, herstart of beëindigd door de abonnee</li>
			<li>ideal: bevestiging ideal betaling via betaal link</li>
			<li>regulier_bank: de maandelijkse factuur en betalen per bank met instructie</li>
			<li>regulier_incasso: de maandelijkse factuur en bevestiging betaling</li>
			<li>regulier_incasso_mislukt: de maandelijkse factuur omdat incasso mislukt en betalen per bank met instructie</li>
			<li>start_ideal: bevesting start na ideal betaling</li>
			<li>start_bank: start betalen per bank met instructie</li>
			<li>vervolg: einde start periode in zicht
				<ul style="list-style-type:square;margin-left:25px">
					<li>[voornaam] : voornaam van de abonnee</li>
					<li>[achternaam] : achternaam van de abonnee</li>
					<li>[loginnaam] : loginnaam van de abonnee</li>
					<li>[start_datum] : datum waarop abonnement moet ingaan</li>
					<li>[pauze_datum] : datum waarop abonnement gepauzeerd wordt</li>
					<li>[herstart_datum] : datum waarop abonnement herstart wordt</li>
					<li>[eind_datum] : datum waarop abonnement beëindigd wordt</li>
					<li>[abonnement] : soort abonnement (beperkt of onbeperkt</li>
					<li>[abonnement_code] : code te vermelden bij betaling</li>
					<li>[abonnement_opmerking] : door abonnee geplaatste opmerking</li>
					<li>[abonnement_wijziging] : de wijziging (pauzeren of beëindigen)</li>
					<li>[abonnement_extras] : de extras bij het abonnement</li>
					<li>[abonnement_startgeld] : het bedrag voor de startperiode, driemaal het maand abonnee bedrag</li>
					<li>[abonnement_maandgeld] : het maand abonnee bedrag</li>
					<li>[abonnement_overbrugging] : het maand abonnee bedrag</li>
					<li>[abonnement_bedrag] : te betalen bedrag</li>
					<li>[abonnement_link] : de betaal link</li>
				</ul>
			</li>
		</ol>
	</li>
	<li><h3>bestelling_* emails</h3>
		<ol>
			<li>bestelling : bestelling inclusief factuur</li>
			<li>bestelling_ideal : bevestiging ideal betaling
				<ul style="list-style-type:square;margin-left:25px">
					<li>[naam] : voornaam van de klant</li>
					<li>[bedrag] : te betalen bedrag</li>
					<li>[bestel_link] : link naar ideal betaling</li>
				</ul>
			</li>
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
			<li>cursus_wijziging: bevestiging aanpassing inschrijving naar andere cursus
				<ul style="list-style-type:square;margin-left:25px">
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
					<li>[cursus_extra_cursisten] : tekst om te tonen als er extra cursisten zijn</li>
					<li>[cursus_hoofd_cursist] : de naam van de hoofdcursist als er meer dan 1 inschrijving is</li>
					<li>[cursus_kosten] : kosten exclusief inschrijfgeld</li>
					<li>[cursus_inschrijfkosten] : inschrijf kosten</li>
					<li>[cursus_opmerking] : de gemaakte opmerking</li>
					<li>[cursus_link] : link naar betaling restant cursus bedrag</li>
					<li>[cursus_ruimte_link] : Link om ingedeeld te worden vanuit de wachtlijst</li>
					<li>[cursus_uitschrijf_link] : link om uit te schrijven van wachtlijst</li>
				</ul>
			</li>
		</ol>
	</li>
	<li><h3>dagdelenkaart_* emails</h3>
		<ol>
			<li>dagdelenkaart_bank : aanvraag dagdelenkaart</li>
			<li>dagdelenkaart_ideal : bevesting betaling dagdelenkaart per ideal
				<ul style="list-style-type:square;margin-left:25px">
					<li>[voornaam] : voornaam van de gebruiker</li>
					<li>[achternaam] : achternaam van de gebruiker</li>
					<li>[start_datum] : datum waarop de dagdelenkaart moet ingaan</li>
					<li>[dagdelenkaart_code] : code te vermelden bij betaling</li>
					<li>[dagdelenkaart_opmerking] : door gebruiker geplaatste opmerking</li>
					<li>[dagdelenkaart_prijs] : kosten van de dagdelenkaart</li>
					<li>[dagdelenkaart_link] : link naar betaling per ideal</li>
				</ul>
			</li>
		</ol>
	</li>
	<li><h3>order_* emails</h3>
		<ol>
			<li>order_correctie : correctie order</li>
			<li>order_annuliering : annulering order
				<ul style="list-style-type:square;margin-left:25px">
					<li>[naam] : naam van de klant</li>
					<li>[artikel] : artikel dat gecorrigeerd of geannuleerd is</li>
				</ul>
			</li>
		</ol>
	</li>
	<li><h3>saldo_* emails</h3>
		<ol>
			<li>saldo_bank : aanvraag saldo met factuur</li>
			<li>saldo_terugboeking : terugboeken saldo</li>
			<li>saldo_negatief : melding saldo tekort</li>
			<li>saldo_ideal : bevestiging betaling saldo per ideal met factuur</li>
			<li>saldo_ideal_betaald : bevesting betaling saldo per ideal
				<ul style="list-style-type:square;margin-left:25px">
					<li>[voornaam] : voornaam van de stoker</li>
					<li>[achternaam] : achternaam van de stoker</li>
					<li>[bedrag] : bedrag dat overgemaakt wordt of via iDEAL is betaald</li>
					<li>[saldo] : huidig saldo</li>
					<li>[saldo_link] : link naar betaling per ideal</li>
				</ul>
			</li>
		</ol>
	</li>
	<li><h3>stook_* emails</h3>
		<ol>
			<li>stook_melding : melding dat er gestookt gaat worden
				<ul style="list-style-type:square;margin-left:25px">
					<li>[voornaam] : voornaam van de hoofdstoker</li>
					<li>[achternaam] : achternaam van de hoofdstoker</li>
					<li>[bedrag] : bruto bedrag van de stook</li>
					<li>[verdeling] : verdeling van de stook zoals op moment van verzending van de email</li>
					<li>[datum_verwerking] : datum waarop kosten afgeboekt worden</li>
					<li>[datum_deadline] : laatste datum waarop verdeling aangepast kan worden</lI>
					<li>[stookoven] : naam van de oven</li>
				</ul>
			</li>
			<li>stook_kosten_verwerkt : melding dat de stook op de saldo verwerkt is
				<ul style="list-style-type:square;margin-left:25px">
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
			<li>workshop_herbevestiging : herbevestiging van de gemaakte afspraken voor de workshop</li>
			<li>workshop_betaling : betalen workshop kosten via email link</li>
			<li>workshop_ideal : bevestiging betaling workshop kosten per ideal</li>
			<li>workshop_reactie : een reactie op een nieuwe aanvraag of vervolg email
				<ul style="list-style-type:square;margin-left:25px">
					<li>[contact] : contactpersoon van de workshop aanvraag</li>
					<li>[organisatie] : organisatie welke de workshop aanvraagt</li>
					<li>[naam] : titel van de cursus ('de workshop', 'het kinderfeest', etc.)</li>
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
					<li>[reactie] : de reactie op de vraag van de aanvrager</li>
				</ul>
			</li>
		</ol>
	</li>
	<li><h3>workshop_aanvraag_* emails</h3>
		<ol>
			<li>bevestiging
				<ul style="list-style-type:square;margin-left:25px">
					<li>[contact] : naam van de aanvrager</li>
					<li>[naam] : titel van de cursus ('de workshop', 'het kinderfeest', etc. )</li>
					<li>[periode] : aangegeven periode</li>
					<li>[omvang] : aangegeven aantal deelnemers</li>
					<li>[email] : opgegeven email adres</li>
					<li>[telefoon] : opgegeven telefoon nummer</li>
				</ul>
			</li>
		</ol>
	</li>
	<li><h3>Generieke emails</h3>
		<ol>
			<li>email_wijziging : De aanpassing van het email adres</li>
			<li>wachtwoord_wijziging : De aanpassing een wachtwoord
				<ul style="list-style-type:square;margin-left:25px">
					<li>[voornaam] : De voornaam van de gebruiker</li>
					<li>[achternaam] : De achternaam van de gebruiker</li>
					<li>[email] : Het nieuwe email adres van de gebruiker</li>
				</ul>
			</li>
			<li>contact_vraag : Een vraag via het formulier
				<ul style="list-style-type:square;margin-left:25px">
					<li>[naam] : De naam van de vraagsteller</li>
					<li>[onderwerp] : Het onderwerp van de vraag</li>
					<li>[email] : Het email adres van de vraagsteller</li>
					<li>[telefoon] : Het telefoonnummer van de vraagsteller</li>
					<li>[vraag] : De vraag</li>
				</ul>
			</li>
		</ol>
	</li>
</ul>
