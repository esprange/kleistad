<?php
/**
 * De class voor de rendering van instellingen functies van de plugin.
 *
 * @link https://www.kleistad.nl
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * Admin display class
 */
class Admin_Instellingen_Display {

	/**
	 * Toon de instellingen
	 *
	 * @return void
	 */
	public function instellingen() : void {
		?>
		<form method="POST" action="options.php" >
		<?php settings_fields( 'kleistad-opties' ); ?>
		<table class="form-table" >
			<?php $this->instellingen_prijzen(); ?>
			<tr >
				<th scope="row"><label for="cursusmaximum">Standaard maximum cursisten per cursus/workshop</label></th>
				<td colspan="3"><input type="number" step="1" min="1"  max="99" name="kleistad-opties[cursusmaximum]" id="cursusmaximum" class="small-text"
					value="<?php echo esc_attr( opties()['cursusmaximum'] ); ?>" /></td>
			</tr>

			<?php
			$index = 1;
			while ( isset( opties()['extra'][ $index ]['naam'] ) ) :
				$id = str_replace( ' ', '_', opties()['extra'][ $index ]['naam'] );
				?>
			<tr >
				<th scope="row"><label for="optie_<?php echo esc_attr( $id ); ?>">Abonnement extra <?php echo esc_html( $index ); ?></label></th>
				<td><input type="text" class="kleistad-extra regular-text" name="kleistad-opties[extra][<?php echo esc_attr( $index ); ?>][naam]" id="optie_<?php echo esc_attr( $id ); ?>"
						value="<?php echo esc_attr( opties()['extra'][ $index ]['naam'] ); ?>"  <?php echo ! empty( opties()['extra'][ $index ]['naam'] ) ? 'readonly' : ''; ?> /></td>
				<th scope="row"><label for="prijs_<?php echo esc_attr( $id ); ?>">Prijs</label></th>
				<td><input type="number" step="0.01" min="0"  name="kleistad-opties[extra][<?php echo esc_attr( $index ); ?>][prijs]" class="small-text" id="prijs_<?php echo esc_attr( $id ); ?>"
						value="<?php echo esc_attr( opties()['extra'][ $index ]['prijs'] ); ?>" /></td>
			</tr>
				<?php
				$index++;
			endwhile;
			?>

			<tr id="kleistad-extra-toevoegen">
				<th>Extra toevoegen</th>
				<td colspan="3"><button type="button" id="kleistad-extra"><span class="dashicons dashicons-plus"></span></button></td>
			</tr>
			<tr >
				<th scope="row"><label for="termijn">Termijn (dagen) dat correctie stook mogelijk is</label></th>
				<td colspan="3"><input type="number" min="0"  name="kleistad-opties[termijn]" id="termijn"
						value="<?php echo esc_attr( opties()['termijn'] ); ?>" class="small-text" /></td>
			</tr>
			<tr >
				<th scope="row"><label for="oven_midden">Oven temperatuur waarbij het midden tarief gaat gelden</label></th>
				<td colspan="3"><input type="number" min="0"  name="kleistad-opties[oven_midden]" id="oven_midden"
						value="<?php echo esc_attr( opties()['oven_midden'] ); ?>" class="small-text" /></td>
			</tr>
			<tr >
				<th scope="row"><label for="oven_hoog">Oven temperatuur waarbij het hoge tarief gaat gelden</label></th>
				<td colspan="3"><input type="number" min="0"  name="kleistad-opties[oven_hoog]" id="oven_hoog"
						value="<?php echo esc_attr( opties()['oven_hoog'] ); ?>" class="small-text" /></td>
			</tr>
			<tr >
				<th scope="row"><label for="weken_werkplek">Aantal weken vooruit dat werkplekken gereserveerd kunnen worden</label></th>
				<td colspan="3"><input type="number" min="1"  name="kleistad-opties[weken_werkplek]" id="weken_werkplek"
						value="<?php echo esc_attr( opties()['weken_werkplek'] ); ?>" class="small-text" /></td>
			</tr>

			</table>
		<?php submit_button(); ?>
		<p>&nbsp;</p>
		</form>
		<?php
	}

	/**
	 * Toon de technische instellingen
	 *
	 * @return void
	 */
	public function setup() : void {
		$googleconnect = new Googleconnect();
		?>
		<div style="float:left;width:50%;">
		<form method="POST" action="options.php" >
			<?php settings_fields( 'kleistad-setup' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">Mollie betalen actief</th>
					<td>
						<p>
							<label>
								<input type="radio" name="kleistad-setup[betalen]"
									value="0" <?php checked( 0, setup()['betalen'] ); ?>/>Uit
							</label><br>
							<label>
								<input type="radio" name="kleistad-setup[betalen]"
									value="1" <?php checked( 1, setup()['betalen'] ); ?>/>Aan
							</label>
						</p>
					</td>
				</tr>
				<?php $this->setup_tekst_parameters(); ?>
			</table>
			<?php submit_button(); ?>
			<p>&nbsp;</p>
		</form>
		</div>
		<div class="card" style="float:right;width:50%;" >
		<form method="POST" >
			<p>Huidige Google connectie status: <strong><?php echo $googleconnect->is_authorized() ? 'gekoppeld' : 'niet gekoppeld'; ?></strong></p>
			<hr/>
			<p>Zonder koppeling is de kalender via de shortcode 'kleistad_kalender' niet zichtbaar en zullen workshops en cursussen niet in de Google kalender worden vastgelegd.
			Nadat de koppeling gemaakt is kunnen bestaande workshops en cursussen die nog niet in de kalender zijn opgenomen wel worden toegevoegd.
			Open daarvoor de cursus of workshop en sla deze op (er hoeven geen parameters gewijzigd te worden).</p>
			<p>Met onderstaande knop wordt gelinkt naar Google. Zorg dan dat je ingelogd bent op het juiste Google account en geef dan toestemming tot de toegang van Kleistad tot de kalender</p>
			<?php submit_button( 'Google Kalender koppelen', 'primary', 'connect', true, disabled( $googleconnect->is_authorized(), true, false ) ); ?>
			<p>&nbsp;</p>
		</form>
		</div>
		<div class="card" style="float:right;width:50%;" >
		<form method="POST" >
			<p>Forceer dagelijkse job</p>
			<hr/>
			<p>Elke dag wordt om 9:00 een job gestart die kijkt of er herinneringen, stookmeldingen, verzoeken om restant betaling, incasso's e.d. nodig zijn.</p>
			<p>Met onderstaande knop kan deze handeling op elk moment geforceerd worden. De job bevat logica die er voor zorgt dat een handeling niet dubbel wordt verricht,
			hoe vaak de job ook per dag gestart wordt.</p>
			<?php submit_button( 'Dagelijkse job uitvoeren', 'primary', 'dagelijks' ); ?>
			<p>&nbsp;</p>
		</form>
		</div>
		<?php
	}

	/**
	 * Toon de email_parameters
	 *
	 * @return void
	 *
	 * @suppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function email_parameters() : void {
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
					<li>vervolg: einde 3 maand periode in zicht
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
						<li>[abonnement_dag] : dag waarvoor beperkt abonnement geldt</li>
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
					<li>saldo_bank : aanvraag saldo</li>
					<li>saldo_ideal : bevesting betaling saldo per ideal
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
					<li>workshop_betaling : betalen workshop kosten via email link</li>
					<li>workshop_ideal : bevestiging betaling workshop kosten per ideal
					<ul style="list-style-type:square;margin-left:25px">
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
					</li>
				</ol>
			</li>
			<li><h3>workshop_aanvraag_* emails</h3>
				<ol>
					<li>bevestiging
						<ul style="list-style-type:square;margin-left:25px">
							<li>[contact] : naam van de aanvrager</li>
							<li>[naam] : titel van de cursus ('de workshop' of 'het kinderfeest' )</li>
							<li>[periode] : aangegeven periode</li>
							<li>[omvang] : aangegeven aantal deelnemers</li>
							<li>[email] : opgegeven email adres</li>
							<li>[telefoon] : opgegeven telefoon nummer</li>
						</ul>
					</li>
					<li>workshop_aanvraag_reactie
						<ul style="list-style-type:square;margin-left:25px">
							<li>[reactie] : de reactie op de vraag van de aanvrager</li>
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
		<?php
	}

	/**
	 * Toon de shortcodes
	 *
	 * @return void
	 *
	 * @suppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function shortcodes() : void {
		?>
		<ul style="list-style-type:none">
			<li><h3>publiek toegankelijk (dus zonder ingelogd te zijn)</h3>
				<ol>
					<li>[kleistad_abonnee_inschrijving] inschrijving als abonnee
					<ul style="list-style-type:square;margin-left:25px">
						<li>verklaring= De optionele verklaring die goedgekeurd moet worden.
						<p>Bijvoorbeeld: <code>verklaring=<?php echo htmlspecialchars( 'ik heb de <a href="https://www.kleistad.nl/.. ..huisregels.pdf" target="_blank" rel="noopener">Huisregels</a> gelezen' ); // phpcs:ignore ?></code></p>
						</li>
					</ul>
					</li>
					<li>[kleistad_cursus_inschrijving] inschrijving voor cursus.
					<ul style="list-style-type:square;margin-left:25px">
						<li>cursus= De specifieke cursussen die getoond moeten worden i.p.v. alle gepubliceerde cursussen.
						<p>Bijvoorbeeld: <code>cursus=C11,C22</code></p>
						</li>
						<li>verbergen= Verbergt het formulier en toont in plaats daarvan de tekst. Als op de tekst geklikt wordt, wordt het formulier zichtbaar.
						<p>Bijvoorbeeld: <code>verbergen=Inschrijven voor cursus</code></p>
						</li>
					</ul>
					</li>
					<li>[kleistad_cursus_extra] het invoeren van de gegevens van extra cursus deelnemers</li>
					<li>[kleistad_dagdelenkaart] aankoop dagdelenkaart
					<ul style="list-style-type:square;margin-left:25px">
						<li>verklaring= De optionele verklaring die goedgekeurd moet worden.
						<p>Bijvoorbeeld: <code>verklaring=<?php echo htmlspecialchars( 'ik heb de <a href="https://www.kleistad.nl/.. ..huisregels.pdf" target="_blank" rel="noopener">Huisregels</a> gelezen' ); // phpcs:ignore ?></code><p>
						</li>
					</ul>
					</li>
					<li>[kleistad_recept] overzicht van keramiek recepten</li>
					<li>[kleistad_workshop_aanvraag] aanvraag voor workshops en kinderfeestjes</li>
					<li>[kleistad_betaling] het betalen van een uitstaand bedrag per iDeal (via link vanuit email)</li>
					<li>[kleistad_contact] het contact formulier</li>
				</ol>
			</li>
			<li><h3>toegankelijk voor leden</h3>
				<ol>
					<li>[kleistad_abonnee_wijziging] wijzigen abonnement door lid</li>
				</ol>
			</li>
			<li><h3>toegankelijk voor leden, docenten en bestuur</h3>
				<ol>
					<li>[kleistad_kalender] overzicht workshops en cursussen</li>
					<li>[kleistad_rapport] overzicht stook activiteiten</li>
					<li>[kleistad_recept_beheer] wijzigen keramiek recepten</li>
					<li>[kleistad_registratie] wijzigen adresgegevens</li>
					<li>[kleistad_reservering] reserveren ovenstook
					<ul style="list-style-type:square;margin-left:25px">
						<li>oven= Het ovennummer (verplicht)
						<p>Bijvoorbeeld <code>oven=3</code></p>
						</li>
					</ul>
					</li>
					<li>[kleistad_saldo] wijzigen stooksaldo</li>
					<li>[kleistad_werkplek] reserveren van een werkplek</li>
				</ol>
			</li>
			<li><h3>toegankelijk voor docenten en bestuur</h3>
				<ol>
					<li>[kleistad_email] formulier om emails naar abonnees en/of cursisten te sturen</li>
					<li>[kleistad_cursus_overzicht] overzicht cursussen en cursist per cursus</li>
				</ol>
			</li>
			<li><h3>toegankelijk voor bestuur</h3>
				<ol>
					<li>[kleistad_abonnement_overzicht] overzicht abonnees</li>
					<li>[kleistad_cursus_beheer] formulier om cursussen te beheren</li>
					<li>[kleistad_omzet_rapportage] overzicht omzet op maandbasis</li>
					<li>[kleistad_registratie_overzicht] overzicht van alle cursisten en leden</li>
					<li>[kleistad_saldo_overzicht] overzicht stooksaldo leden</li>
					<li>[kleistad_stookbestand] opvragen stookbestand</li>
					<li>[kleistad_verkoop] verkoop overige artikelen invoeren</li>
					<li>[kleistad_workshop_beheer] formulier om workshops te beheren</li>
					<li>[kleistad_werkplekrapport] overzichten werkplekgebruik
					<ul style="list-style-type:square;margin-left:25px">
						<li>actie=overzicht. Overzicht van gebruik werkplekken door alle gebruikers</li>
						<li>actie=individueel. Gebruik werkplekken door een gebruiker</li>
					</ul>
					</li>
				</ol>
			</li>
			<li><h3>toegankelijk voor boekhouder</h3>
				<ol>
					<li>[debiteuren] overzicht openstaande orders
					<ul style="list-style-type:square;margin-left:25px">
						<li>actie=zoek. Met zoekfunctie die ook gesloten orders toont</li>
						<li>actie=blokkade. Om een kwartaal af te sluiten</li>
					</ul>
					</li>
				</ol>
			</li>
		</ul>
		<?php
	}

	/**
	 * Toon de tekst parameters
	 *
	 * @return void
	 */
	private function setup_tekst_parameters() : void {
		$parameters = [
			'sleutel'            => 'Mollie geheime sleutel',
			'sleutel_test'       => 'Mollie geheime sleutel voor test',
			'google_kalender_id' => 'Google kalender id',
			'google_client_id'   => 'Google client id',
			'google_sleutel'     => 'Google geheime sleutel',
			'imap_server'        => 'Email IMAP server',
			'imap_adres'         => 'Email IMAP adres',
			'imap_pwd'           => 'Email IMAP paswoord',
		];
		foreach ( $parameters as $id => $naam ) {
			?>
			<tr >
				<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $naam ); ?></label></th>
				<td colspan="3">
					<input type="text" name="kleistad-setup[<?php echo esc_attr( $id ); ?>]" id="<?php echo esc_attr( $id ); ?>" class="regular-text"
						value="<?php echo esc_attr( setup()[ $id ] ); ?>" />
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Toon de prijzen
	 *
	 * @return void
	 */
	private function instellingen_prijzen() : void {
		$prijzen = [
			'onbeperkt_abonnement' => 'Prijs onbeperkt abonnement',
			'beperkt_abonnement'   => 'Prijs beperkt abonnement',
			'dagdelenkaart'        => 'Prijs dagdelenkaart',
			'cursusprijs'          => 'Prijs standaard cursus excl. inschrijving',
			'cursusinschrijfprijs' => 'Prijs cursus inschrijving',
			'workshopprijs'        => 'Prijs standaard workshop',
		];
		foreach ( $prijzen as $id => $naam ) {
			?>
			<tr >
				<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $naam ); ?></label></th>
				<td colspan="3"><input type="number" step="0.01" name="kleistad-opties[<?php echo esc_attr( $id ); ?>]" id="<?php echo esc_attr( $id ); ?>" class="small-text"
					value="<?php echo esc_attr( opties()[ $id ] ); ?>" /></td>
			</tr>
			<?php
		}
	}
}
