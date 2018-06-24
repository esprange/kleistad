# Kleistad #
**Contributors:** esprange  
**Donate link:** www.sprako.nl/wordpress/eric  
**Tags:** kleistad, ceramics  
**Requires at least:** 4.8.0  
**Tested up to:** 4.9.6  
**Stable tag:** 4.3.10  
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
### 4.3.10 ###
* In abonnement pauzeren email de door de gebruiker opgegeven herstart datum vermeldt
* In inschrijvingen overzicht pauze datum, herstart datum en eind datum toegevoegd
### 4.3.9 ###
* Bestanden voortaan te downloaden in plaats van verzending per email
### 4.3.8 ###
* Toevoeging plugin update selfservice
### 4.3.0 ###
* Toevoeging selfservice functies voor abonnees, incasso betalingen van abonnementen
### 4.2.0 ###
* Toevoeging betalingen via Mollie
### 4.1.4 ###
* Toevoeging keramiek recepten functionaliteit
### 4.0.92 ###
* Berekening verdeling gewijzigd
### 4.0.87 ###
* Versie na complete refactoring van oude 'kleistad_reserveren' plugin.

## Upgrade Notice ##

geen.

## Additionele info ##

Deze plugin voegt drie tabellen toe aan de database:

* kleistad_ovens
* kleistad_reserveringen
* kleistad_cursussen

Alle overige informatie wordt via user_meta en custom posts informatie vastgelegd.
