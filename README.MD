 === Kleistad ===
**Contributors:** [eric-sprangers](https://profiles.wordpress.org/eric-sprangers/)  
**Donate link:** https://www.kleistad.nl  
**Tags:** kleistad, ceramics  
**Requires at least:** 4.8.0  
**Tested up to:** 5.8.3  
**Stable tag:** 7.0.4  
**License:** GPLv3 or later  
**License URI:** http://www.gnu.org/licenses/gpl-3.0.html  
**Requires PHP:** 7.4  

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/esprange/kleistad/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/esprange/kleistad/?branch=master)
[![CodeFactor](https://www.codefactor.io/repository/github/esprange/kleistad/badge)](https://www.codefactor.io/repository/github/esprange/kleistad)

Wordpress plugin for Stichting Kleistad, Amersfoort (The Netherlands), see www.kleistad.nl.

## Description ##

Deze plugin is specifiek ontwikkeld voor de site www.kleistad.nl en voegt een aantal uitbreidingen via shortcode toe aan deze Wordpress site.

# Gebruik van de Kleistad plugin

De plugin onderkent een groot aantal shortcodes. De uitleg hiervan wordt getoond op de kleistad opties in het admin dashboard.

## Installation ##

Reguliere Wordpress plugin installatie door:
1. Upload van de plugin naar de '/wp-content/plugins/ directory
2. Activering plugin in het Wordpress 'plugins' menu.
3. Plugin updates via reguliere wordpress functies.

Plaats de shortcodes in de pagina's

## Screenshot ##
* None yet *

## Changelog ##
### 7.1.0 ###
* Nieuwe shortcode kleistad_docent voor het invoeren van beschikbaarheids gegevens.
* Aanpassing workshop aanvraag formulier, plandata afhankelijk van aantal parallele activiteiten en beschikbaarheid docent.
* Activiteiten en ruimtes voortaan niet meer hardcoded maar via dashboard te beheren.
* Vanwege nieuw multi step formulier, aanpassingen voor workshop aanvraag, abonnee inschrijving en cursus inschrijving.
* Aanpassingen in workshop beheer, werkplekreservering.
* Daarnaast refactoring (PHP nu foutvrij) en inclusief alle patches sinds 6.21.3.
### 6.21.3 ###
* Beperkte abonnementen, dag is is niet meer van belang, en wijziging soort mag na startperiode.
* Aanpassing stookrapportage, nu ook voor bestuur totaal overzicht.
* Optimalisatie admin overzicht abonnees.
* Annuleren door gebruiker uit te voeren.
* Refactor de shortcode verwerking.
* Foutafhandeling bij issues met Google of Mollie verbeterd.
* Verbetering uiterlijk profiel, nu gekoppeld aan menu.
### 6.20.3 ###
* Bugfix voor herhaald inschrijven en verwerking refunds Mollie
### 6.20.2 ###
* Aanmeldingen voor een cursus worden omgezet naar de wachtlijst als de cursus vol komt
* Melding als er terug te betalen bedragen staan
### 6.20.0 ###
* Gebruikersprofiel met openstaande vorderingen toegevoegd
* Voorbereiding workshop reserveringen
* Beperkt bugfix en refactoring
### 6.18.2 ###
* Bij annulering via boekhouding de inschrijving of workshop correct op geannuleerd zetten
### 6.18.1 ###
* Diverse verbeteringen
* Bugfixes en beperkte refactoring
### 6.16.5 ###
* Verbetering laden van style scripts en oplossen FOUC datatables
### 6.16.2 ###
* Bugfix saldo storten
* Robuuster maken fout idealbetaling vanuit formulier voor abonnement, inschrijving en dagdelenkaart
### 6.16.0 ###
* Performance verbetering, cursus inschrijvingen opgenomen in aparte database tabel ipv in user_meta
* Performance verbetering, reductie aantal database queries
* Robuuster abonnee inschrijving
### 6.15 ###
* Visuele verbeteringen
* Aanpassing code structuur waardoor betere code checks mogelijk zijn
* Werkplek reserveringen houdt nu voortaan rekening met de feestdagen
### 6.14 ###
* Visuele verbeteringen
* Verbetering wachtlijst functionaliteit
### 6.12 ###
* Werkplekrapport toegevoegd voor de rapportages
* Corona shortcode vervangen door werkplek, deze ajax driven gemaakt
### 6.10 ###
* Cursisten kunnen zich niet dubbel inschrijven voor een cursus
* Aanpassing maximum in cursusbeheer wijzigt de status vol van een cursus
* Cursisten kunnen zichzelf afmelden voor de wachtlijst
* Bestuur kan een cursist afmelden voor de wachtlijst
* Oven tarieven voor laag, midden en hoge temperaturen
### 6.9 ###
* Docenten en bestuur kunnen werkplek reserveringen voor cursisten toevoegingen
* In cursisten correctie via dashboard kan ook het aantal gecorrigeerd worden
### 6.8 ###
* De docent ontvangt voortaan reply email op email verzonden door docent
* Het is mogelijk om factuur adresgegevens aan te passen voor de workshops
* Bugfix, nieuwe cursisten krijgen niet automatisch een rol en verbetering wachtlijst
### 6.7 ###
* Corona (werkplekbeheer) ondersteunt nu ook registratie van beheerders
* Corona (werkplekbeheer) geeft nu betere rapportage mbt aanwezigheid i.g.v. positieve test
### 6.6 ###
* Cursus inschrijving ondersteunt nu wachtlijsten
* Nieuwe shortcode cursus_extra om gegevens extra deelnemers in te voeren
### 6.5 ###
* Stooksaldo kan nu ook een afwijkend bedrag zijn
* Cursussen welke binnen een week beginnen moeten in hun geheel betaald worden bij inschrijving
### 6.4 ###
* Recept termen zijn via dashboard aan te passen
### 6.3 ###
* Stooksaldo's in dashboard kunnen gesorteerd worden
* Corona shortcode voor reserveren werkplekken toegevoegd
* Aanpassing abonnement wijzigen, herstart bij pauze nu mogelijk
* Debiteuren uitgebreid zodat hiermee ook een blokkade datum kan worden aangepast
* Debiteuren sorteert op vervaldatum en kleurt kritieke betalingen
* Debiteuren annulering storneert automatisch ingeval van Mollie betaling
* Debiteuren afboeken voor dubieuze debiteuren
* Omzetrapportage pdf rapport
* Contact formulier shortcode
### 6.2 ###
* Shortcode verkoop toegevoegd voor verkoop losse artikelen
* Controle op geblokkeerde periode
* Aanscherping controle op debiteuren acties
### 6.1 ###
* Verbetering abonnement status melding
* Mogelijkheid tot aanpassen wachtwoord in registratie
* Wachtwoord reset email nu in Kleistad formaat
* Voorkom zwakke wachtwoorden
* Totaalregel omzet
* Aparte email voor indeling op lopende cursus
* Registratie ondersteunt aanpasing email adres
* Opgemaakte email voor aanpassing email adres
* Opgemaakte email voor wijziging wachtwoord
* Opmerkingen veld toegevoegd voor aanmaken korting en annuleringen
* Verbeterde selectie bij versturen herinneremail cursusbetaling
* Verbeterde bepaling cursus betaald
* Verbeterde betaalpagina
* Privacy fix in emailpagina
* Financiële uitbreiding met automatische facturen, omzet registratie en uitbreiding gebruik iDeal
### 6.0 ###
* Alle functionaliteit nu volledig Ajax, geen page refresh noodzaak meer.
* Code volledig herzien, gebruikt namespaces

## Upgrade Notice ##

geen.

## Additionele info ##

Deze plugin voegt vijf tabellen toe aan de database:

*  Ovens
*  Reserveringen
*  Cursussen
*  Workshops
*  Orders
*  Inschrijvingen

De plugin maakt gebruik van Mollie, de Google Calendar API en de geodata locatieserver.

Alle overige informatie wordt via user_meta en custom posts informatie vastgelegd.
