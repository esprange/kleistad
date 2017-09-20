<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

?>
<div class="wrap">

	<h2>Instellingen Kleistad plugin</h2>
	<table>
		<tr>
			<td>
				<form method="post" action="options.php" >
					<?php settings_fields( 'kleistad-opties' ); ?>
					<table class="form-table" >
						<tr >
							<th scope="row">Prijs onbeperkt abonnement</th>
							<td><input type="text" name="kleistad-opties[onbeperkt_abonnement]" 
									   value="<?php echo esc_attr( $this->options['onbeperkt_abonnement'] ); ?>" /></td>
						</tr>

						<tr >
							<th scope="row">Prijs beperkt abonnement</th>
							<td><input type="text" name="kleistad-opties[beperkt_abonnement]" 
									   value="<?php echo esc_attr( $this->options['beperkt_abonnement'] ); ?>" /></td>
						</tr>

						<tr >
							<th scope="row">Prijs dagdelenkaart</th>
							<td><input type="number" step="0.01" min="0"  name="kleistad-opties[dagdelenkaart]" 
									   value="<?php echo esc_attr( $this->options['dagdelenkaart'] ); ?>" /></td>
						</tr>

						<tr >
							<th scope="row">Prijs standaaard cursus</th>
							<td><input type="number" step="0.01" min="0" name="kleistad-opties[cursusprijs]" 
									   value="<?php echo esc_attr( $this->options['cursusprijs'] ); ?>" /></td>
						</tr>

						<tr >
							<th scope="row">Prijs cursus inschrijving</th>
							<td><input type="number" step="0.01" min="0"  name="kleistad-opties[cursusinschrijfprijs]" 
									   value="<?php echo esc_attr( $this->options['cursusinschrijfprijs'] ); ?>" /></td>
						</tr>

						<tr >
							<th scope="row">Prijs standaard workshop</th>
							<td><input type="number" step="0.01" min="0"  name="kleistad-opties[workshopprijs]" 
									   value="<?php echo esc_attr( $this->options['workshopprijs'] ); ?>" /></td>
						</tr>

						<tr >
							<th scope="row">Prijs kinderworkshop</th>
							<td><input type="number" step="0.01" min="0"  name="kleistad-opties[kinderworkshopprijs]" 
									   value="<?php echo esc_attr( $this->options['kinderworkshopprijs'] ); ?>" /></td>
						</tr>

						<tr >
							<th scope="row">Termijn (dagen) dat correctie stook mogelijk is</th>
							<td><input type="number" min="0"  name="kleistad-opties[termijn]" 
									   value="<?php echo esc_attr( $this->options['termijn'] ); ?>" /></td>
						</tr>

					</table>

					<?php submit_button(); ?>
				</form>
			</td><td style="padding-left:10em;">
				<h2>Gebruik van de Kleistad plugin</h2>

				De shortcodes zijn: 
				<ul style="list-style-type:none">
					<li><h3>publiek toegankelijk (dus zonder ingelogd te zijn)</h3>
						<ul style="list-style-type:square">
							<li>[kleistad_abonnee_inschrijving] inschrijving als abonnee</li>
							<li>[kleistad_cursus_inschrijving] inschrijving voor cursus</li>
						</ul>                      
					</li>
					<li><h3>toegankelijk voor leden</h3>
						<ul style="list-style-type:square">
							<li>[kleistad_reservering oven=1] reserveren ovenstook</li>
							<li>[kleistad_rapport] overzicht stook activiteiten door lid</li>
							<li>[kleistad_saldo] wijzigen stooksaldo door lid</li>
							<li>[kleistad_registratie] wijzigen adresgegevens door lid</li>
						</ul>
					</li>
					<li><h3>toegankelijk voor bestuur</h3>
						<ul style="list-style-type:square">
							<li>[kleistad_saldo_overzicht] overzicht stooksaldo leden</li>
							<li>[kleistad_stookbestand] opvragen stookbestand</li>
							<li>[kleistad_registratie_overzicht] overzicht van alle cursisten en leden</li>
							<li>[kleistad_cursus_beheer] formulier om cursussen te beheren </li>
							<li>[kleistad_betalingen] formulier om betalingen cursisten te registreren</li>
						</ul>
					</li>
				</ul>
			</td>
		</tr>
		<tr>
			<td style="padding-left:10em;">
				<h2>Email parameters</h2>
				<ul style="list-style-type:none">
					<li><h3>kleistad_email_saldo_wijziging</h3></li>
					<ul style="list-style-type:square">
						<li>[voornaam] : voornaam van de stoker</li>
						<li>[achternaam] : achternaam van de stoker</li>
						<li>[bedrag] : bedrag dat bijgestort is op saldo</li>
						<li>[datum] : datum waarop storting heeft plaatsgevonden</li>
						<li>[via] : of er per bank of contact is gestort</li>						
					</ul>
					<li><h3>kleistad_email_stookmelding</h3></li>
					<ul style="list-style-type:square">
						<li>[voornaam] : voornaam van de hoofdstoker</li>
						<li>[achternaam] : achternaam van de hoofdstoker</li>
						<li>[bedrag] : bruto bedrag van de stook</li>
						<li>[datum_verwerking] : datum waarop kosten afgeboekt worden</li>
						<li>[datum_deadline] : laatste datum waarop verdeling aangepast kan worden</lI>
						<li>[stookoven] : naam van de oven</li>
					</ul>
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
					<li><h3>kleistad_email_abonnement</h3>
						<ul style="list-style-type:square">
						<li>[voornaam] : voornaam van de abonnee</li>
						<li>[achternaam] : achternaam van de abonnee</li>
						<li>[start_datum] : datum waarop abonnement moet ingaan</li>
						<li>[abonnement] : soort abonnement (beperkt of onbeperkt</li>
						<li>[abonnement_code] : code te vermelden bij betaling</li>
						<li>[abonnement_dag] : dag waarvoor beperkt abonnement geldt</li>
						<li>[abonnement_opmerking] : door abonnee geplaatste opmerking</li>
						<li>[abonnement_startgeld] : driemaal het maand abonnee bedrag</li>
						<li>[abonnement_maandgeld] : het maand abonnee bedrag</li>
					</ul>
					<li><h3>'cursus indeling email'</h3>
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
						<li>[cursus_kosten] : kosten exclusief inschrijfgeld</li>
						<li>[cursus_inschrijfkosten] : inschrijf kosten</li>
					</ul>
					<li><h3>'cursus inschrijving email'</h3>
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
						<li>[cursus_opmerking] : door cursist geplaatste opmerking</li>
						<li>[cursus_code] : code te vermelden bij betaling</li>
						<li>[cursus_kosten] : kosten exclusief inschrijfgeld</li>
						<li>[cursus_inschrijfkosten] : inschrijf kosten</li>
					</ul>

				</ul>
			</td>

		</tr>
	</table>

</div>
