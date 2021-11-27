<?php
/**
 * Toon het oven reservering formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de reservering formulier.
 */
class Public_Reservering_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() {
		$stokers_json = wp_json_encode( $this->data['stokers'] );
		if ( false === $stokers_json ) {
			return;
		}
		?>
		<h2>Reserveringen voor de <?php echo esc_html( $this->data['oven']['naam'] ); ?></h2>
		<div id="kleistad_geen_ie" style="display:none">
			<strong>Helaas wordt Internet Explorer niet meer ondersteund voor deze functionaliteit, gebruik bijvoorbeeld Chrome of Edge</strong>
		</div>
		<table id="kleistad_reserveringen" class="kleistad-reserveringen"
			data-maand="<?php echo esc_attr( date( 'n' ) ); ?>"
			data-jaar="<?php echo esc_attr( date( 'Y' ) ); ?>"
			data-oven-naam="<?php echo esc_attr( $this->data['oven']['naam'] ); ?>"
			data-stokers='<?php echo $stokers_json; // phpcs:ignore ?>'
			data-override="<?php echo (int) current_user_can( OVERRIDE ); ?>" >
			<thead>
				<tr>
					<th>
						<button class="kleistad-button kleistad_periode" type="button" value="-1" >eerder</button
					></th>
					<th colspan="2" style="text-align:center;"><strong><span id="kleistad_periode" style="font-size:medium;"></span></strong></th>
					<th style="text-align:right" >
						<button class="kleistad-button kleistad_periode" type="button" value="1" >later</button>
					</th>
				</tr>
				<tr>
					<th>Dag</th>
					<th>Wie?</th>
					<th>Soort stook</th>
					<th style="text-align:right">Temp</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th colspan="4">de reserveringen worden opgehaald...</th>
				</tr>
			</tbody>
		</table>

		<div id ="kleistad_reservering" style="display:none" >
		<form method="POST" autocomplete="off" >
			<input id="kleistad_oven_id" type="hidden" value="<?php echo esc_attr( $this->data['oven']['id'] ); ?>" >
			<input id="kleistad_dag" type="hidden" >
			<input id="kleistad_maand" type="hidden" >
			<input id="kleistad_jaar" type="hidden" >
			<table class="kleistad-form kleistad-reservering-form">
				<thead>
				</thead>
				<tbody>
				</tbody>
				<tfoot>
					<tr>
						<td><button class="kleistad-button" style="font-size:16px;border-radius:25%;width:40px;text-align:center;padding:0;" id="kleistad_stoker_toevoegen"><span class="dashicons dashicons-plus"></span></button></td>
						<td>&nbsp;</td><td>&nbsp;</td>
					</tr>
					<tr>
						<th colspan="3">
							<span id="kleistad_tekst"></span>
						</th>
					</tr>
					<tr>
						<td colspan="3">
							<button class="kleistad-button" type="button" id="kleistad_voegtoe" style="float:left;width:30%;margin-right:5%;">Voeg toe</button>
							<button class="kleistad-button" type="button" id="kleistad_muteer" style="float:left;width:30%;margin-right:5%;">Wijzig</button>
							<button class="kleistad-button" type="button" id="kleistad_verwijder" style="width:30%;margin:0 auto;">Verwijder</button>
							<button class="kleistad-button" type="button" id="kleistad_sluit" style="float:right;width:30%;margin-left:5%;" >Sluit</button>
						</td>
					</tr>
				</tfoot>
			</table>
		</form>
		</div>
		<?php
	}

}
