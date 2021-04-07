<?php
/**
 * Toon het recept beheer formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de cursus beheer formulier.
 */
class Public_Registratie_Overzicht_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		$this->overzicht();
	}

	/**
	 * Toon het overzicht van cursussen
	 *
	 * @return Public_Registratie_Overzicht_Display
	 */
	private function overzicht() : Public_Registratie_Overzicht_Display {
		?>
		<div id="kleistad_deelnemer_info">
			<table class="kleistad-form" id="kleistad_deelnemer_tabel" >
			</table>
		</div>
		<p><label for="kleistad_deelnemer_selectie">Selectie</label>
			<select id="kleistad_deelnemer_selectie" name="selectie" >
				<option value="*" >&nbsp;</option>
				<option value="A" >Actieve abonnees</option>
				<option value="K" >Actieve dagdelenkaart gebruikers</option>
					<?php
					$options = '';
					foreach ( $this->data['cursussen'] as $cursus ) :
						$options = "<option value=\"C$cursus->id\" >C$cursus->id $cursus->naam</option>\n$options";
					endforeach;
					echo $options; // phpcs:ignore
					?>
			</select>
		</p>
		<table class="kleistad-datatable display compact nowrap" id="kleistad_deelnemer_lijst">
			<thead>
				<tr>
					<th data-visible="false">Lid</th>
					<th data-visible="false">Dagdelenkaart</th>
					<th data-visible="false">Cursuslijst</th>
					<th>Achternaam</th>
					<th>Voornaam</th>
					<th>Email</th>
					<th>Telnr</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $this->data['registraties'] as $registratie ) :
					$json_inschrijvingen = wp_json_encode( $registratie['inschrijving_info'] );
					$json_deelnemer      = wp_json_encode( $registratie['deelnemer_info'] );
					$json_abonnee        = wp_json_encode( $registratie['abonnee_info'] );
					$json_dagdelenkaart  = wp_json_encode( $registratie['dagdelenkaart_info'] );
					if ( false === $json_inschrijvingen || false === $json_deelnemer || false === $json_abonnee || false === $json_dagdelenkaart ) :
						continue;
					endif;
					?>
					<tr data-inschrijvingen='<?php echo htmlspecialchars( $json_inschrijvingen, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore ?>'
						data-deelnemer='<?php echo htmlspecialchars( $json_deelnemer, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore ?>'
						data-abonnee='<?php echo htmlspecialchars( $json_abonnee, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore ?>'
						data-dagdelenkaart='<?php echo htmlspecialchars( $json_dagdelenkaart, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore ?>' >
						<td><?php echo esc_html( $registratie['is_abonnee'] ); ?></td>
						<td><?php echo esc_html( $registratie['is_dagdelenkaart'] ); ?></td>
						<td><?php echo esc_html( $registratie['is_cursist'] ); ?></td>
						<td><?php echo esc_html( $registratie['achternaam'] ); ?></td>
						<td><?php echo esc_html( $registratie['voornaam'] ); ?></td>
						<td><?php echo esc_html( $registratie['email'] ); ?></td>
						<td><?php echo esc_html( $registratie['telnr'] ); ?></td>
					</tr>
					<?php endforeach ?>
			</tbody>
		</table>
		<div class="kleistad-row" style="padding-top:20px;" >
			<button type="button" class="kleistad-download-link" data-actie="cursisten" >Download Cursisten</button>
			<button type="button" class="kleistad-download-link" data-actie="abonnees" >Download Abonnees</button>
			<button type="button" class="kleistad-download-link" data-actie="dagdelenkaarten" >Download Dagdelenkaarten</button>
		</div>
		<?php
		return $this;
	}

}
