=== Kleistad ===
Contributors: esprange
Donate link: www.sprako.nl/wordpress/eric
Tags: comments
Requires at least: 4.8.0
Tested up to: 4.8.0
Stable tag: 4.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Wordpress plugin for Stichting Kleistad, Amersfoort (The Netherlands), see www.kleistad.nl.

== Description ==

Deze plugin is specifiek ontwikkeld voor de site www.kleistad.nl en voegt een aantal uitbreidingen via shortcode toe aan deze Wordpress site.

# Gebruik van de Kleistad plugin

## De shortcodes zijn: 
* publiek toegankelijk (dus zonder ingelogd te zijn)
    + [kleistad_abonnee_inschrijving] inschrijving als abonnee
    + [kleistad_cursus_inschrijving] inschrijving voor cursus
* toegankelijk voor leden
    + [kleistad_reservering oven=1] reserveren ovenstook
    + [kleistad_rapport] overzicht stook activiteiten door lid
    + [kleistad_saldo] wijzigen stooksaldo door lid
    + [kleistad_registratie] wijzigen adresgegevens door lid
* toegankelijk voor bestuur
    + [kleistad_saldo_overzicht] overzicht stooksaldo leden
    + [kleistad_stookbestand] opvragen stookbestand
    + [kleistad_registratie_overzicht] overzicht van alle cursisten en leden
    + [kleistad_cursus_beheer] formulier om cursussen te beheren
    + [kleistad_betalingen] formulier om betalingen cursisten te registreren

== Installation ==

Reguliere Wordpress plugin installatie door:
1. Upload van de plugin naar de '/wp-content/plugins/ directory
2. Activering plugin in het Wordpress 'plugins' menu.

Plaats de shortcodes in de pagina's

== Screenshot ==
* None yet

== Changelog ==

= 4.0.0 =
* None yet

== Upgrade Notice ==

= 4.0.0 =
Versie na complete refactoring van oude 'kleistad_reserveren' plugin.

== Arbitrary section ==

Deze plugin voegt drie tabellen toe aan de database:

* kleistad_ovens
* kleistad_reserveringen
* kleistad_cursussen

Alle overige informatie wordt via user_meta informatie vastgelegd.

`<?php code(); // goes in backticks ?>`