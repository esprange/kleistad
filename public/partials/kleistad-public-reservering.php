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

if ( ! Kleistad_Roles::reserveer() ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	$stokers_json = wp_json_encode( $data['stokers'] );
	if ( false === $stokers_json ) {
		return;
	}
	?>
	<h1>Reserveringen voor de <?php echo esc_html( $data['oven']['naam'] ); ?></h1>
	<table id="kleistad_reserveringen" class="kleistad_reserveringen"
		data-maand="<?php echo esc_attr( date( 'n' ) ); ?>"
		data-jaar="<?php echo esc_attr( date( 'Y' ) ); ?>"
		data-oven-naam="<?php echo esc_attr( $data['oven']['naam'] ); ?>"
		data-stokers='<?php echo $stokers_json; // phpcs:ignore ?>'
		data-override="<?php echo Kleistad_Roles::override() ? 1 : 0; ?>" >
		<tr>
			<th>de reserveringen worden opgehaald...</th>
		</tr>
	</table>
	<div id ="kleistad_reservering" class="kleistad_form_popup">
	<form method="POST" autocomplete="off" >
		<input id="kleistad_oven_id" type="hidden" value="<?php echo esc_attr( $data['oven']['id'] ); ?>" >
		<table class="kleistad_form">
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
						<input type ="hidden" id="kleistad_dag">
						<input type ="hidden" id="kleistad_maand">
						<input type ="hidden" id="kleistad_jaar">
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
<?php endif ?>
