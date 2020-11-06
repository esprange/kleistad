<?php
/**
 * Toon het (dynamische) oven reservering formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.92
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

$stokers_json = wp_json_encode( $data['stokers'] );
if ( false === $stokers_json ) {
	return;
}
?>
<h1>Reserveringen voor de <?php echo esc_html( $data['oven']['naam'] ); ?></h1>
<div id="kleistad_geen_ie" style="display:none">
	<strong>Helaas wordt Internet Explorer niet meer ondersteund voor deze functionaliteit, gebruik bijvoorbeeld Chrome of Edge</strong>
</div>
<table id="kleistad_reserveringen" class="kleistad_reserveringen"
	data-maand="<?php echo esc_attr( date( 'n' ) ); ?>"
	data-jaar="<?php echo esc_attr( date( 'Y' ) ); ?>"
	data-oven-naam="<?php echo esc_attr( $data['oven']['naam'] ); ?>"
	data-stokers='<?php echo $stokers_json; // phpcs:ignore ?>'
	data-override="<?php echo (int) \Kleistad\Roles::override(); ?>" >
	<thead>
		<tr>
			<th>
				<button type="button" class="kleistad_periode" value="-1" >eerder</button
			></th>
			<th colspan="2" ><strong><span id="kleistad_periode"></span></strong></th>
			<th style="text-align:right" >
				<button type="button" class="kleistad_periode" value="1" >later</button>
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
			<th>de reserveringen worden opgehaald...</th>
		</tr>
	</tbody>
</table>

<div id ="kleistad_reservering" >
<form method="POST" autocomplete="off" >
	<input id="kleistad_oven_id" type="hidden" value="<?php echo esc_attr( $data['oven']['id'] ); ?>" >
	<input id="kleistad_dag" type="hidden" >
	<input id="kleistad_maand" type="hidden" >
	<input id="kleistad_jaar" type="hidden" >
	<table class="kleistad_form  \Kleistad\Reservering_form">
		<thead>
		</thead>
		<tbody>
		</tbody>
		<tfoot>
			<tr>
				<td><button style="font-size:16px;border-radius:25%;width:40px;text-align:center;padding:0px;" id="kleistad_stoker_toevoegen" class="kleistad_button"><span class="genericon genericon-plus"></span></button></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<th colspan="3">
					<span id="kleistad_tekst"></span>
				</th>
			</tr>
			<tr>
				<th>
					<button type="button" id="kleistad_voegtoe" class="kleistad_button" >Voeg toe</button>
					<button type="button" id="kleistad_muteer" class="kleistad_button" >Wijzig</button>
				</th>
				<th><button type="button" id="kleistad_verwijder" class="kleistad_button" >Verwijder</button></th>
				<th><button type="button" id="kleistad_sluit" >Sluit</button></th>
			</tr>
		</tfoot>
	</table>
</form>
</div>
