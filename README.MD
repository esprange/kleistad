# Kleistad #
**Contributors:** Eric Sprangers  
**Donate link:** https://www.kleistad.nl  
**Tags:** kleistad, ceramics  
**Requires at least:** 4.8.0  
**Tested up to:** 4.9.8  
**Stable tag:** 4.5.4  
**License:** GPLv3 or later  
**License URI:** http://www.gnu.org/licenses/gpl-3.0.html  

[![Build Status](https://travis-ci.org/esprange/kleistad.svg?branch=master)](https://travis-ci.org/esprange/kleistad)

Wordpress plugin for Stichting Kleistad, Amersfoort (The Netherlands), see www.kleistad.nl.

## Description ##

Deze plugin is specifiek ontwikkeld voor de site www.kleistad.nl en voegt een aantal uitbreidingen via shortcode toe aan deze Wordpress site.

# Gebruik van de Kleistad plugin

De plugin onderkent een groot aantal shortcodes. De uitleg hiervan wordt getoond op de kleistad opties in het admin dashboard.
De plugin maakt gebruik van de Mollie betaal app voor de iDeal betalingen.

## Installation ##

Reguliere Wordpress plugin installatie door:
1. Upload van de plugin naar de '/wp-content/plugins/ directory
2. Activering plugin in het Wordpress 'plugins' menu.

Plaats de shortcodes in de pagina's

## Screenshot ##
* None yet *

## Changelog ##
### 4.5.5 ###
* abonnement werkdag wijzigen toegevoegd
* betalingen overzicht toont geen annuleringen als cursus gestart is
* cursus overzicht toont aantal inschrijvingen
### 4.5.4 ###
* nieuwe shortcode cursus_overzicht
* incasso die niet lukt stuurt email naar klant
* ook in 3 maand periode mag al een abonnement soort wijziging aangevraagd worden
* bugfix in admin abonnement wijzigen scherm
### 4.5.3 ###
* Indeling email wordt alleen verstuurd als cursus nog niet gestart is
* Status huidig abonnement wordt getoond bij abonnement wijzigen
* Wachtlijst functionaliteit verwijderd
### 4.5.2 ###
* Mogelijkheid op cursus_id op te geven bij shortcode cursus_inschrijving
* Mogelijkheid om extras op te geven bij abonnement
### 4.5.1 ###
* Mollie version 2.0.10
* Email layout verbeteringen
* Refactoring stooksaldo verwerking
* Refactoring shortcode handler
* Refactoring gebruiker contact informatie
* Tekst bij cursusinschrijving met inschrijfgeld verbeterd
### 4.5.0 ###
* Nieuwe dashboard functie voor wijzigen cursist inschrijving
* Opmerkingen in emails nu conditioneel
* Pagina aantal in dashboard stooksaldo functie verhoogd naar 15
### 4.4.2 ###
* Onderhoud modus reserveringen oven, alleen door bestuur te gebruiken
* Bestuursleden kunnen 1e stoker wijzigen
### 4.4.1 ###
* Nieuwe versies van externe referenties, Mollie 2.0.6 en Datatables 1.10.19
* Scripts voortaan in de footer
* Restricties op startdatum abonnement en dagdelenkaart
* Bugfix in recept (toevoegingen < 0.5 gr ook toestaan)
### 4.4.0 ###
* In admin functie overzicht abonnees, nu informatie vanuit Mollie toegevoegd.
* Code optimalisatie zodat de kleistad opties maar eenmalig opgehaald worden uit de database.
* Code optimalisatie van Mollie callback
* Bugfixes, o.a. dagdelenkaart
### 4.3.12 ###
* Aanpassing ingeval betalingswijze wordt gewijzigd na pauzeren abonnement. Herstart incasso dan per herstartdatum ipv. eerste dag volgende maand.
### 4.3.11 ###
* Geen functionele wijzigingen, alleen volledige overhaul van code commentaar
### 4.3.10 ###
* In abonnement pauzeren email de door de gebruiker opgegeven herstart datum vermeldt
* In inschrijvingen overzicht pauze datum, herstart datum en eind datum toegevoegd
### 4.3.9 ###
* Bestanden voortaan te downloaden in plaats van verzending per email
### 4.3.8 ###
* Toevoeging plugin update selfservice
### 4.3.0 ###
* Toevoeging selfservice functies voor abonnees, incasso betalingen van abonnementen
* GDPR functies voor opvragen en verwijderen persoonlijke informatie
* Toevoeging dagdelenkaart
### 4.2.0 ###
* Toevoeging betalingen via Mollie
### 4.1.4 ###
* Toevoeging keramiek recepten functionaliteit
### 4.0.92 ###
* Berekening verdeling gewijzigd
### 4.0.87 ###
* Versie na complete refactoring van oude 'kleistad_reserveren' plugin.
* Nieuwe versie gebaseerd op boilerplate plugin template van Devin Vinson.
* Abonnementen toegevoegd
### 3.0 ###
* Cursus beheer en inschrijvingen toegevoegd
### 2.0 ###
* Extra functionaliteiten (saldo beheer, rapporten, bestuur/beheerders functies) en meer gegevens vastleggen per reservering
### 1.1 ###
* Code optimalisatie van de kleistad class en verbetering styling.
### 1.0 ###
* Eerste versie welke de mogelijkheid biedt tot ovenreserveringen

## Upgrade Notice ##

geen.

## Additionele info ##

Deze plugin voegt drie tabellen toe aan de database:

* kleistad_ovens
* kleistad_reserveringen
* kleistad_cursussen

Alle overige informatie wordt via user_meta en custom posts informatie vastgelegd.
