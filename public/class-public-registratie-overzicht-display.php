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
	 * Toon het overzicht van cursussen
	 */
	protected function overzicht() {
		?>
		<div id="kleistad_deelnemer_info" style="font-size: small">
		</div>
		<p><label for="kleistad_deelnemer_selectie">Selectie</label>
			<select class="kleistad-select" id="kleistad_deelnemer_selectie" name="selectie" >
				<option value="*" >&nbsp;</option>
				<option value="A" >Abonnees</option>
				<option value="K" >Dagdelenkaart gebruikers</option>
				<?php foreach ( $this->data['cursussen'] as $cursus ) : ?>
				<option value="<?php echo esc_attr( $cursus['code'] ); ?>" ><?php echo esc_html( "{$cursus['code']} {$cursus['naam']}" ); ?></option>
				<?php endforeach; ?>
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
				foreach ( $this->data['registraties'] as $id => $registratie ) :
					?>
					<tr data-id="<?php echo esc_attr( $id ); ?>">
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
			<button class="kleistad-button kleistad-download-link" type="button" data-actie="cursisten" >Download Cursisten</button>
			<button class="kleistad-button kleistad-download-link" type="button" data-actie="abonnees" >Download Abonnees</button>
			<button class="kleistad-button kleistad-download-link" type="button" data-actie="dagdelenkaarten" >Download Dagdelenkaarten</button>
		</div>
		<?php
	}

}
