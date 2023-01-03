 === Kleistad ===
Contributors: Eric Sprangers
Donate link: https://www.kleistad.nl
Tags: kleistad, ceramics
Requires at least: 4.8.0
Tested up to: 6.1.1
Stable tag: 7.9.6





[![CodeFactor](https://www.codefactor.io/repository/github/esprange/kleistad/badge)](https://www.codefactor.io/repository/github/esprange/kleistad)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/0c21c0e2b3d548079260b477857b179b)](https://www.codacy.com/gh/esprange/kleistad/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=esprange/kleistad&amp;utm_campaign=Badge_Grade)

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
= 7.9.6 =
* Verbetering galerie showcase, toont overige werkstukken van keramist.
* Criterium werkplekreservering in weken na cursus instelbaar.
* Cursist rol wordt niet meer verwijderd na afloop cursus.
* Vervallen workshop is te herstellen.
* Optimalisatie debiteuren beheer.
* Saldo storten en restitutie verbeterd.
* Diverse bugfixes mbt timestamps.
= 7.8.8 =
* Cursus beheer, reservering van werkplekken toegevoegd.
* Abonnement wijziging (stop) verbeterd, ook in dashboard
= 7.8.5 =
* Beschrijving gebruiker kan nu in html (dus met links e.d.).
= 7.8.4 =
* Bugfix workshopbeheer.
= 7.8.3 =
* Workshop beheer, 'geen reactie nodig' button toegevoegd.
* Workshop beheer, reservering van werkplekken toegevoegd.
* Workshop beheer, kolom technieken toegevoegd.
* Verbetering correctie achteraf in workshop beheer.
* Toon welke concept workshop aanvraag gaat vervallen
* Aanpassing prioriteiten kolommen workshop beheer in responsive mode
* Verbetering correctie cursus inschrijving
* Refactor recept beheer
* Toon in inschrijvingen overzicht gebruiker id bij hover
= 7.7.4 =
* Bladerfunctie toegevoegd aan gallerij showcase
= 7.7.0 =
* Registratie voor leden uitgebreid met verwijzing naar eigen website en biografie
* Nieuwe shortcode voor gallerij showcase
= 7.6.2 =
* Nieuwe functie voor verkoop van werkstukken
* Als vanuit dashboard een inschrijving correctie plaatsvindt wordt de cursus zonodig op vol gezet
* Bij cursusbeheer kan een cursus pas op vervallen worden gezet nadat er geen actieve inschrijvingen meer zijn
* Updates van externe pakketten
* Bugfix voor wachtwoord wijziging door gebruiker en beperkte refactoring.
* Verkoop losse artikelen aan bekende klant, toon alleen actieve leden, actieve cursisten, docenten en bestuur
* Verwijder cursist rol twee weken na einde laatste cursus deelname
= 7.5.3 =
* Verkoop losse artikelen ook op saldo mogelijk
* Factuur nummer zichtbaar in debiteuren overzicht
* Bugfix, bij materiaal verbruik alleen ingedeelde cursisten tonen
= 7.4.5 =
* Bugfixes, waaronder fix wijziging cursus
= 7.4.4 =
* Mogelijkheid tot registratie materialen verbruik per cursist
* Nieuwe rol voor cursisten
= 7.3.12 =
* Mogelijkheid tot geforceerd indelen van een cursist op de wachtlijst
= 7.3.11 =
* Extra instelling om alleen betalingen per ideal vanuit de inschrijfformulieren mogelijk te maken
* Aanpassing dat beheerder een cursist op wachtlijst opnieuw kan inschrijven en na bankbetaling kan inschrijven
* Zowel bij workshop beheer als bij cursus beheer het overzicht aangepast dat alleen de voornaam van de docent getoond wordt
= 7.3.10 =
* Diverse bugs en refactoring
* Stookbestand ook in de toekomst, vasthouden workshop beheer filter status
* Verbetering email template
* strftime, deprecated functie vanaf PHP 8.1 verwijderd
= 7.3.4 =
* Orders kunnen voortaan altijd gewijzigd worden. De bestaande order vervalt (wordt gecrediteerd) en een nieuwe wordt aangemaakt.
* Extra werkplekrapport optie reserveringen. Toont toekomstig werkplek gebruik van de ingelogde gebruiker.
* Bug fix werkplek reserveringen.
= 7.2.7 =
* Diverse verbeteringen waaronder verbetering workshop beheer.
= 7.2.4 =
* Merge van workshop aanvragen en workshop reserveringen. Een klant reserveert per direct een workshop
* Docenten worden geïnformeerd bij een workshop boeking.
* Datatables sorteer volgorde wordt vastgehouden.
= 7.1.8 =
* Aanpassing naar docent beschikbaarheid, nu ook ochtend, middag, namiddag en avond.
* Beperking op maximaal gelijktijdig te reserveren stook
= 7.1.5 =
* Aanpassing workshop aanvraag, ook techniek uitvraag en nu ochtend, middag en namiddag.
* Meerdere docenten per workshop.
* Enkele bugs opgelost.
= 7.1.0 =
* Nieuwe shortcode kleistad_docent voor het invoeren van beschikbaarheids gegevens.
* Aanpassing workshop aanvraag formulier, plandata afhankelijk van aantal parallele activiteiten en beschikbaarheid docent.
* Activiteiten en ruimtes voortaan niet meer hardcoded maar via dashboard te beheren.
* Vanwege nieuw multi step formulier, aanpassingen voor workshop aanvraag, abonnee inschrijving en cursus inschrijving.
* Aanpassingen in workshop beheer, werkplekreservering.
* Daarnaast refactoring (PHP nu foutvrij) en inclusief alle patches sinds 6.21.3.

== Upgrade Notice ==

geen.

== Additionele info ==

Deze plugin voegt vijf tabellen toe aan de database:

*  Ovens
*  Reserveringen
*  Cursussen
*  Workshops
*  Orders
*  Inschrijvingen

De plugin maakt gebruik van Mollie, de Google Calendar API en de geodata locatieserver.

Alle overige informatie wordt via user_meta en custom posts informatie vastgelegd.
