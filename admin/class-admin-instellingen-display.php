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
		<!--suppress HtmlUnknownTarget -->
		<form method="POST" action="options.php" >
		<?php settings_fields( 'kleistad-opties' ); ?>
		<div style="height: 80vh;overflow-y: scroll;" >
		<table class="form-table" >
		<?php
			$this->instellingen_prijzen();
			$this->instellingen_parameters();
			$this->setup_lijst_parameters();
		?>
		</table>
		</div>
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
			<div style="height: 80vh;overflow-y: scroll;" >
			<table class="form-table">
				<?php $this->setup_switch_parameters(); ?>
				<?php $this->setup_tekst_parameters(); ?>
			</table>
			</div>
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
		</form>
		</div>
		<?php
	}

	/**
	 * Toon de email_parameters
	 *
	 * @return void
	 */
	public function email_parameters() : void {
		require 'admin-email-parameters.php';
	}

	/**
	 * Toon de shortcodes
	 *
	 * @return void
	 */
	public function shortcodes() : void {
		require 'admin-shortcodes.php';
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
				<td>
					<input type="text" name="kleistad-setup[<?php echo esc_attr( $id ); ?>]" id="<?php echo esc_attr( $id ); ?>" class="regular-text"
						value="<?php echo esc_attr( setup()[ $id ] ); ?>" />
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Toon de lijst parameters
	 *
	 * @return void
	 */
	private function setup_lijst_parameters() : void {
		$parameters = [
			'extra'      => [
				'titel'  => 'Abonnement extra',
				'velden' => [
					[
						'naam'  => 'prijs',
						'titel' => 'Prijs',
						'veld'  => 'type="number" step="0.01" min="0"',
					],
				],
			],
			'werkruimte' => [
				'titel'  => 'Ruimte',
				'velden' => [
					[
						'naam'  => 'kleur',
						'titel' => 'Kleur werkplekreservering',
						'veld'  => 'type="text"',
						'class' => 'kleistad-color',
					],
				],
			],
			'activiteit' => [
				'titel'  => 'Activiteit',
				'velden' => [],
			],
			'actpauze'   => [
				'titel'  => 'Activiteiten pauze',
				'velden' => [
					[
						'naam'  => 'start',
						'titel' => 'Start datum',
						'veld'  => 'type="text"',
						'class' => 'kleistad-datum',
					],
					[
						'naam'  => 'eind',
						'titel' => 'Eind datum ',
						'veld'  => 'type="text"',
						'class' => 'kleistad-datum',
					],
				],
			],
		];
		foreach ( $parameters as $key => $parameter ) {
			$json_velden = wp_json_encode( $parameter['velden'] );
			if ( is_string( $json_velden ) ) {
				?>
	<tr><th scope="row"><?php echo esc_html( $parameter['titel'] ); ?></th><td>
		<table class="form-table" id="<?php echo esc_attr( "kleistad_lijst_$key" ); ?>">
			<thead>
				<tr>
					<th scope="row">Naam</th>
					<?php foreach ( $parameter['velden'] as $veld ) : ?>
						<th scope="row"><?php echo esc_html( $veld['titel'] ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( opties()[ $key ] ?? [] as $index => $optie ) :
					?>
				<tr>
					<td><!--suppress HtmlFormInputWithoutLabel -->
						<input type="text" class="regular-text" name="<?php echo esc_attr( "kleistad-opties[$key][$index][naam]" ); ?>" value="<?php echo esc_attr( $optie['naam'] ); ?>" /></td>
					<?php foreach ( $parameter['velden'] as $veld ) : ?>
					<td><!--suppress HtmlFormInputWithoutLabel -->
						<input <?php echo $veld['veld']; // phpcs:ignore ?>  class="small-text <?php echo esc_attr( $veld['class'] ?? '' ); ?>" name="<?php echo esc_attr( "kleistad-opties[$key][$index][{$veld['naam']}]" ); ?>" value="<?php echo esc_attr( $optie[ $veld['naam'] ] ); ?>" /></td>
					<?php endforeach; ?>
					<td><span id="kleistad_verwijder_<?php echo esc_attr( $key . '_' . $index ); ?>" class="dashicons dashicons-trash" style="cursor: pointer;"></span></td>
				</tr>
					<?php
			endforeach;
				?>
			</tbody>
			<tfoot>
				<tr>
					<th scope="row"><?php echo esc_html( $parameter['titel'] ); ?> toevoegen</th>
					<td>
						<button id="kleistad_voegtoe_<?php echo esc_attr( $key ); ?>" type="button" class="lijst_toevoegen" data-key="<?php echo esc_attr( $key ); ?>" data-parameters='<?php echo $json_velden; // phpcs:ignore ?>'>
							<span class="dashicons dashicons-plus"></span>
						</button>
					</td>
				</tr>
			</tfoot>
		</table>
		</td></tr>
				<?php
			}
		}
	}

	/**
	 * Toon de switch parameters
	 *
	 * @return void
	 */
	private function setup_switch_parameters() : void {
		$parameters = [
			'profiel' => 'Gebruikersprofiel actief',
			'betalen' => 'Mollie betalen actief',
			'stort'   => 'Betalingen per bank toegestaan',
		];
		foreach ( $parameters as $id => $naam ) {
			?>
			<tr>
				<th scope="row"><?php echo esc_html( $naam ); ?></th>
				<td>
					<label>
						<input type="radio" name="kleistad-setup[<?php echo esc_attr( $id ); ?>]"
							value="0" <?php checked( 0, setup()[ $id ] ); ?>/>Uit
					</label>
					<label style="margin-left: 50px">
						<input type="radio" name="kleistad-setup[<?php echo esc_attr( $id ); ?>]"
							value="1" <?php checked( 1, setup()[ $id ] ); ?>/>Aan
					</label>
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
			'materiaalprijs'       => 'Prijs materiaal verbruik per kilo',
			'administratiekosten'  => 'Administratie kosten bij terugstorting',
		];
		foreach ( $prijzen as $id => $naam ) {
			?>
			<tr >
				<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $naam ); ?></label></th>
				<td><input type="number" step="0.01" name="kleistad-opties[<?php echo esc_attr( $id ); ?>]" id="<?php echo esc_attr( $id ); ?>" class="small-text"
					value="<?php echo esc_attr( opties()[ $id ] ); ?>" /></td>
			</tr>
			<?php
		}
	}

	/**
	 * Toon de parameters
	 *
	 * @return void
	 */
	private function instellingen_parameters() : void {
		$parameters = [
			'cursusmaximum'       => [
				'min'   => 1,
				'max'   => 99,
				'label' => 'Standaard maximum cursisten per cursus/workshop',
			],
			'start_maanden'       => [
				'min'   => 1,
				'max'   => 3,
				'label' => 'Start duur abonnement (maanden)',
			],
			'min_pauze_weken'     => [
				'min'   => 1,
				'max'   => 12,
				'label' => 'Minimum pauze duur abonnement (weken)',
			],
			'max_pauze_weken'     => [
				'min'   => 1,
				'max'   => 12,
				'label' => 'Maximum pauze duur abonnement (weken)',
			],
			'termijn'             => [
				'min'   => 0,
				'max'   => 14,
				'label' => 'Termijn (dagen) dat correctie stook mogelijk is',
			],
			'oven_midden'         => [
				'min'   => 0,
				'max'   => 1500,
				'label' => 'Oven temperatuur waarbij het midden tarief gaat gelden',
			],
			'oven_hoog'           => [
				'min'   => 0,
				'max'   => 1500,
				'label' => 'Oven temperatuur waarbij het hoge tarief gaat gelden',
			],
			'stook_max'           => [
				'min'   => 1,
				'max'   => 99,
				'label' => 'Aantal stook reserveringen dat mag openstaan',
			],
			'weken_werkplek'      => [
				'min'   => 1,
				'max'   => 52,
				'label' => 'Aantal weken vooruit dat werkplekken gereserveerd kunnen worden',
			],
			'verloopaanvraag'     => [
				'min'   => 1,
				'max'   => 12,
				'label' => 'Aantal weken voordat een workshop aanvraag verloopt',
			],
			'weken_workshop'      => [
				'min'   => 1,
				'max'   => 52,
				'label' => 'Aantal weken vooruit dat workshop aanvragen gedaan kunnen worden',
			],
			'workshop_wijzigbaar' => [
				'min'   => 0,
				'max'   => 99,
				'label' => 'Aantal dagen na workshop dat workshop wijzigbaar is',
			],
			'max_activiteit'      => [
				'min'   => 1,
				'max'   => 12,
				'label' => 'Aantal activiteiten (cursus, workshop etc.) dat gelijktijdig kan plaatsvinden',
			],
		];
		foreach ( $parameters as $id => $parameter ) :
			?>
			<tr >
				<th scope="row">
					<label for="<?php echo esc_attr( $id ); ?>">
						<?php echo esc_html( $parameter['label'] ); ?>
					</label>
				</th>
				<td>
					<input type="number" min="<?php echo esc_attr( $parameter['min'] ); ?>"
						max="<?php echo esc_attr( $parameter['max'] ); ?>" name="kleistad-opties[<?php echo esc_attr( $id ); ?>]" id="<?php echo esc_attr( $id ); ?>" class="small-text"
						value="<?php echo esc_attr( opties()[ $id ] ); ?>" />
				</td>
			</tr>
			<?php
		endforeach;
	}
}
