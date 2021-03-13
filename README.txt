=== Kleistad ===
Contributors: Eric Sprangers
Donate link: https://www.kleistad.nl
Tags: kleistad, ceramics
Requires at least: 4.8.0
Tested up to: 5.7
Stable tag: 6.14.6
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires PHP: 7.4

[![Build Status](https://travis-ci.org/esprange/kleistad.svg?branch=master)](https://travis-ci.org/esprange/kleistad)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/esprange/kleistad/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/esprange/kleistad/?branch=master)
[![CodeFactor](https://www.codefactor.io/repository/github/esprange/kleistad/badge)](https://www.codefactor.io/repository/github/esprange/kleistad)

Wordpress plugin for Stichting Kleistad, Amersfoort (The Netherlands), see www.kleistad.nl.

== Description ==

Deze plugin is specifiek ontwikkeld voor de site www.kleistad.nl en voegt een aantal uitbreidingen via shortcode toe aan deze Wordpress site.

# Gebruik van de Kleistad plugin

De plugin onderkent een groot aantal shortcodes. De uitleg hiervan wordt getoond op de kleistad opties in het admin dashboard.

== Installation ==

Reguliere Wordpress plugin installatie door:
1. Upload van de plugin naar de '/wp-content/plugins/ directory
2. Activering plugin in het Wordpress 'plugins' menu.
3. Plugin updates via reguliere wordpress functies.

Plaats de shortcodes in de pagina's

== Screenshot ==
* None yet *

== Changelog ==
= 6.14.5 =
* Kleine bugs opgelost en visuele verbeteringen
= 6.14.3 =
* Visuele verbeteringen
= 6.14.2 =
* Verbetering wachtlijst functionaliteit
= 6.14.1 =
* Geen functionele aanpassingen, wel styling verbeteren als voorbereiding op nieuw Kleistad thema
* Aantal bugfixes
= 6.12.5 =
* Werkplekrapport toegevoegd voor de rapportages
* refactoring
= 6.11.0 =
* prerelease, forse refactoring
* Corona shortcode vervangen door werkplek, deze ajax driven gemaakt
* Werkplek gebruik rapportages moeten nog toegevoegd worden
= 6.10.2 =
* Cursisten kunnen zich niet dubbel inschrijven voor een cursus
* Aanpassing maximum in cursusbeheer wijzigt de status vol van een cursus
= 6.10.1 =
* Cursisten kunnen zichzelf afmelden voor de wachtlijst
* Bestuur kan een cursist afmelden voor de wachtlijst
* Oven tarieven voor laag, midden en hoge temperaturen
* Fix in het plain text deel van uitgaande berichten
= 6.9.0 =
* Docenten en bestuur kunnen werkplek reserveringen voor cursisten toevoegingen
* In cursisten correctie via dashboard kan ook het aantal gecorrigeerd worden
= 6.8.1 =
* De docent ontvangt voortaan reply email op email verzonden door docent
* Het is mogelijk om factuur adresgegevens aan te passen voor de workshops
* Bugfix, nieuwe cursisten krijgen niet automatisch een rol en verbetering wachtlijst
= 6.7.1 =
* Bugfixes, o.a. abonnement betaling en cursusbeheer
* Nieuwe versie datatables
= 6.7.0 =
* Corona (werkplekbeheer) ondersteunt nu ook registratie van beheerders
* Corona (werkplekbeheer) geeft nu betere rapportage mbt aanwezigheid i.g.v. positieve test
= 6.6.1 =
* Cursus inschrijving ondersteunt nu wachtlijsten
= 6.6.0 =
* Nieuwe shortcode cursus_extra om gegevens extra deelnemers in te voeren
* Bugfix in cursus restantbedrag in email en versturen herinneringsemail  
= 6.5.3 =
* Agenda maakt nu gebruik van fullcalendar 5.3.2 en datatables 1.10.21
* Fix in workshops en cursus zodat een correctie factuur alleen verzonden wordt als de order wijzigt
* Aanpassing plugin afhankelijk omgeving (development, staging, production)
= 6.5.1 =
* Stooksaldo kan nu ook een afwijkend bedrag zijn
* Cursussen welke binnen een week beginnen moeten in hun geheel betaald worden bij inschrijving
* Refactoring dashboard functies
= 6.4.0 =
* Recept termen zijn via dashboard aan te passen
* Refunds en chargebacks bij betalen zijn verbeterd
= 6.3.6 =
* Stooksaldo's in dashboard kunnen gesorteerd worden
* Fix avg cleanup
* Dagdelenkaart overzicht bevat einddatum
* Abonnementen download bevat overbruggingsemail vlag
= 6.3.5 =
* Corona shortcode voor reserveren werkplekken toegevoegd
* Bugfix in verwerking betaling stooksaldo (saldo in email niet actueel)
= 6.3.3 =
* Aanpassing abonnement wijzigen, herstart bij pauze nu mogelijk
= 6.3.2 =
* Bugfixes en refactoring
* Terugstorten via Mollie verbeterd
= 6.3.1 =
* Kleine bugfixes en verbetering contact form en dubieuze debiteuren
* Gebruik nieuwste versies van FullCalendar en jsTree
= 6.3.0 =
* Admin schermen verbeterd, knop voor dagelijks job toegevoegd
* Debiteuren uitgebreid zodat hiermee ook een blokkade datum kan worden aangepast
* Debiteuren sorteert op vervaldatum en kleurt kritieke betalingen
* Debiteuren annulering storneert automatisch ingeval van Mollie betaling
* Debiteuren afboeken voor dubieuze debiteuren
* Omzetrapportage pdf rapport
* Contact formulier shortcode
= 6.2.0 =
* Shortcode verkoop toegevoegd voor verkoop losse artikelen
* Controle op geblokkeerde periode
* Aanscherping controle op debiteuren acties
= 6.1.6 =
* Fix in factuurnummering
* Fix in aanpassen wachtwoord
* Verbetering abonnement status melding
* Google services update
= 6.1.5 =
* Mogelijkheid tot aanpassen wachtwoord in registratie
* Wachtwoord reset email nu in Kleistad formaat
* Voorkom zwakke wachtwoorden
= 6.1.4 =
* Bugfixes en dynamische aanpassing betaalstatus van o.a. inschrijvingen
* Totaalregel omzet
* Aparte email voor indeling op lopende cursus
* Registratie ondersteunt aanpasing email adres
* Opgemaakte email voor aanpassing email adres
* Opgemaakte email voor wijziging wachtwoord
= 6.1.3 =
* Bugfixes na in productie name versie 6.1.2
* Opmerkingen veld toegevoegd voor aanmaken korting en annuleringen
* Verbeterde selectie bij versturen herinneremail cursusbetaling
* Verbeterde bepaling cursus betaald
* Verbeterde betaalpagina
* Privacy fix in emailpagina
= 6.1.1 =
* Aanpassing emails als custom posttype
= 6.1.0 =
* Financiële uitbreiding met automatische facturen, omzet registratie en uitbreiding gebruik iDeal
= 6.0.0 =
* Alle functionaliteit nu volledig Ajax, geen page refresh noodzaak meer.
* Code volledig herzien, gebruikt namespaces
* Geen functionele aanpassingen.
= 5.7.2 =
* Registratie overzicht uitgebreid met info over dagdelenkaarten.
= 5.7.1 =
* Verdere refactoring, volledige aanpassing van formulier verwerking, nu in Ajax
* Copy naar Klembord bij abonnement en cursus overzicht verwijderd. Email functie vervangt dit.
= 5.7.0 =
* Geen functionele verbeteringen, wel aanpassing file downloads waardoor versnelling plugin
* Updates van meest recente bibliotheken
= 5.6.1 =
* bugfixes en laatste versie externe libs
= 5.6.0 =
* Toevoeging workshop aanvraag shortcode en aanpassing workshop beheer
* Verbetering email (naast html ook plain text), om valse spam indicatie te voorkomen
= 5.5.1 =
* Nieuwe versie van FullCalendar (4.2.0)
* Nieuwe versie google/apiclient (0.103)
* Refactoring om bestanden te verkleinen
* Email shortcode verbetering, inclusief test email mogelijkheid
* Kleine verbeter acties
= 5.5.0 =
* Als cursusinschrijving op cursus selectie wordt gebruikt worden alle cursussen getoond.
* Diverse kleine verbeteringen, o.a. kalender layout, cursus beheer layout
* Nieuwe shortcode kleistad_email, voor het versturen van opgemaakte email naar abonnees en cursisten.
* Versturen formulieren geeft proces indicatie centraal op scherm.
= 5.4.0 =
* Cursus beheer verbeterd, o.a. lesrooster invoeren en verwijdering cursus mogelijk
* Verbetering van de stookmelding, nu met verdeling
* Diverse kleine layout aanpassingen
= 5.3.4 =
* Plugin grootte verkleind
= 5.3.3 =
* Refactor van registratie scripts en styles t.b.v. performance
= 5.3.1 =
* Download van workshops
* Refactor downloads
= 5.3.0 =
* Maakt gebruik van versie 4.0.1 FullCalendar
* Workshopbeheer toont docent ipv techniek in tabel
* Ovenreservering in verleden mogelijk voor beheerders
* Correctie labels cursisten overzicht download
* Sortering cursisten overzicht download op inschrijfdatum
= 5.2.2 =
* Code refactoring a.g.v. controles door scrutinizer
* Oven reservering fors gewijzigd
* Op basis van postcode, huisnr de straatnaam en plaats opzoeken
= 5.2.0 =
* Kleuren agenda aangepast
* Externe libraries geactualiseerd
= 5.1.0 =
* Agenda functie vervangen door FullCalendar object
= 5.0.4 =
* Kosten borg niet meer tonen als bedrag borg gelijk is aan 0
* Bugfixes
= 5.0.3 =
* ook overige afspraken zijn zichtbaar in de kalender, naast cursussen en workshops
= 5.0.2 =
* integratie met Google Calender voor individuele workshops en cursussen.
* nadat ingedeeld is kan er niet opnieuw ingeschreven worden.
* aanpassing downloadfile abonnees (shortcode registratie overzicht).
* als er nog maar ruimte is voor 1 deelnemer dan wordt keuze aantal niet getoond.
* in het dashboard kan er gezocht worden in het cursisten en abonnee overzicht.
* verbeterde invoercontrole bij aanmaak nieuwe cursussen.
* bugfix: verwijder subscriber rol als abonnee stopt
* bugfix: ruimte kan niet kleiner worden dan 0
= 4.5.11 =
* titels in dialoog popups
= 4.5.9, 4.5.10 =
* bugfix en refactoring versie
= 4.5.8 =
* cursus inschrijving voor specifieke cursussen nu mogelijkheid
* verbetering admin menu abonnees
= 4.5.7 =
* abonnement status tekst consistent gemaakt
* bug fix abonnement pauze status
* Mollie update 2.1.1
= 4.5.6 =
* abonnement overzicht toegevoegd
= 4.5.5 =
* abonnement werkdag wijzigen toegevoegd
* betalingen overzicht toont geen annuleringen als cursus gestart is
* cursus overzicht toont aantal inschrijvingen
= 4.5.4 =
* nieuwe shortcode cursus_overzicht
* incasso die niet lukt stuurt email naar klant
* ook in 3 maand periode mag al een abonnement soort wijziging aangevraagd worden
* bugfix in admin abonnement wijzigen scherm
= 4.5.3 =
* Indeling email wordt alleen verstuurd als cursus nog niet gestart is
* Status huidig abonnement wordt getoond bij abonnement wijzigen
* Wachtlijst functionaliteit verwijderd
= 4.5.2 =
* Mogelijkheid op cursus_id op te geven bij shortcode cursus_inschrijving
* Mogelijkheid om extras op te geven bij abonnement
= 4.5.1 =
* Mollie version 2.0.10
* Email layout verbeteringen
* Refactoring stooksaldo verwerking
* Refactoring shortcode handler
* Refactoring gebruiker contact informatie
* Tekst bij cursusinschrijving met inschrijfgeld verbeterd
= 4.5.0 =
* Nieuwe dashboard functie voor wijzigen cursist inschrijving
* Opmerkingen in emails nu conditioneel
* Pagina aantal in dashboard stooksaldo functie verhoogd naar 15
= 4.4.2 =
* Onderhoud modus reserveringen oven, alleen door bestuur te gebruiken
* Bestuursleden kunnen 1e stoker wijzigen
= 4.4.1 =
* Nieuwe versies van externe referenties, Mollie 2.0.6 en Datatables 1.10.19
* Scripts voortaan in de footer
* Restricties op startdatum abonnement en dagdelenkaart
* Bugfix in recept (toevoegingen < 0.5 gr ook toestaan)
= 4.4.0 =
* In admin functie overzicht abonnees, nu informatie vanuit Mollie toegevoegd.
* Code optimalisatie zodat de kleistad opties maar eenmalig opgehaald worden uit de database.
* Code optimalisatie van Mollie callback
* Bugfixes, o.a. dagdelenkaart
= 4.3.12 =
* Aanpassing ingeval betalingswijze wordt gewijzigd na pauzeren abonnement. Herstart incasso dan per herstartdatum ipv. eerste dag volgende maand.
= 4.3.11 =
* Geen functionele wijzigingen, alleen volledige overhaul van code commentaar
= 4.3.10 =
* In abonnement pauzeren email de door de gebruiker opgegeven herstart datum vermeldt
* In inschrijvingen overzicht pauze datum, herstart datum en eind datum toegevoegd
= 4.3.9 =
* Bestanden voortaan te downloaden in plaats van verzending per email
= 4.3.8 =
* Toevoeging plugin update selfservice
= 4.3.0 =
* Toevoeging selfservice functies voor abonnees, incasso betalingen van abonnementen
* GDPR functies voor opvragen en verwijderen persoonlijke informatie
* Toevoeging dagdelenkaart
= 4.2.0 =
* Toevoeging betalingen via Mollie
= 4.1.4 =
* Toevoeging keramiek recepten functionaliteit
= 4.0.92 =
* Berekening verdeling gewijzigd
= 4.0.87 =
* Versie na complete refactoring van oude 'kleistad_reserveren' plugin.
* Nieuwe versie gebaseerd op boilerplate plugin template van Devin Vinson.
* Abonnementen toegevoegd
= 3.0 =
* Cursus beheer en inschrijvingen toegevoegd
= 2.0 =
* Extra functionaliteiten (saldo beheer, rapporten, bestuur/beheerders functies) en meer gegevens vastleggen per reservering
= 1.1 =
* Code optimalisatie van de kleistad class en verbetering styling.
= 1.0 =
* Eerste versie welke de mogelijkheid biedt tot ovenreserveringen

== Upgrade Notice ==

geen.

== Additionele info ==

Deze plugin voegt vier tabellen toe aan de database:

*  Ovens
*  Reserveringen
*  Cursussen
*  Workshops
*  Orders

De plugin maakt gebruik van Mollie, de Google Calendar API en de geodata locatieserver.

Alle overige informatie wordt via user_meta en custom posts informatie vastgelegd.
