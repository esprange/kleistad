<?php
/**
 * Toon het cursus verbruik formulier
 *
 * @link       https://www.kleistad.nl
 * @since      7.4.0
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het cursus verbruik formulier.
 */
class Public_Cursus_Verbruik_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 */
	protected function cursisten() {
		$this->form( 'form_cursisten' );
	}

	/**
	 * Render de cursussen
	 */
	protected function overzicht() {
		?>
		<table class="kleistad-datatable display" id="kleistad_cursussen" data-order='[[ 0, "desc" ]]'>
			<thead>
			<tr>
				<th>Code</th>
				<th>Naam</th>
				<th>Docent</th>
				<th>Start</th>
				<th data-orderable="false"></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $this->data['cursus_info'] as $cursus_id => $cursus_info ) :
				?>
				<tr>
					<td data-sort="<?php echo esc_attr( $cursus_id ); ?>"><?php echo esc_html( $cursus_info['code'] ); ?></td>
					<td><?php echo esc_html( $cursus_info['naam'] ); ?></td>
					<td><?php echo esc_html( $cursus_info['docent'] ); ?></td>
					<td data-sort="<?php echo esc_attr( $cursus_info['start_dt'] ); ?>"><?php echo esc_html( $cursus_info['start_datum'] ); ?></td>
					<td>
						<a href="#" title="toon cursisten" class="kleistad-view kleistad-edit-link"	data-id="<?php echo esc_attr( $cursus_id ); ?>" data-actie="cursisten" >
							&nbsp;
						</a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render het formulier
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function form_cursisten() {
		?>
		<strong><?php echo esc_html( $this->data['cursus']['code'] . ' ' . $this->data['cursus']['naam'] ); ?></strong>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $this->data['cursus']['id'] ); ?>">
		<input type="hidden" id="materiaalprijs" value="<?php echo esc_attr( opties()['materiaalprijs'] ); ?>" >
		<table class="kleistad-datatable display" data-paging="false" data-searching="false">
			<thead>
			<tr>
				<th>Naam</th>
				<th>Huidig saldo</th>
				<th>Verbruik historie</th>
				<th>Verbruik in gram</th>
				<th>Kosten</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['cursisten'] as $cursist ) : ?>
				<tr>
					<td><?php echo esc_html( $cursist['naam'] ); ?>
						<input type="hidden" name="cursist_id[]" value="<?php echo esc_attr( $cursist['id'] ); ?>" ></td>
					<td>&euro; <?php echo esc_html( number_format_i18n( $cursist['saldo'], 2 ) ); ?></td>
					<td><label><select readonly="readonly">
							<?php foreach ( $cursist['verbruiken'] as $verbruik ) : ?>
							<option>
								<?php
								echo esc_html(
									date( 'd-m-Y', strtotime( $verbruik['datum'] ) ) . ' : ' .
									$verbruik['gewicht'] . ' gram, &euro; ' . number_format_i18n( -$verbruik['prijs'], 2 )
								);
								?>
							</option>
							<?php endforeach; ?>
						</select></label></td>
					<td><label><input type="number" style="width:5rem;" name="verbruik[]" min="0" size="4"></label></td>
					<td>€ <span></span></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<br/>
		<button class="kleistad-button" type="submit" name="kleistad_submit_cursus_verbruik" value="verbruik" data-confirm="Cursisten|weet je zeker dat de ingevoerde verbruiken correct zijn" >Bewaren</button>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
	}

}
