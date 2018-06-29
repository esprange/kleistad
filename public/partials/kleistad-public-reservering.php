<?php
/**
 * Toon het (dynamische) oven reservering formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.92
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

if ( ! Kleistad_Roles::reserveer() ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

	<h1 id="<?php echo esc_attr( 'kleistad' . $data['oven']->id ); ?>">Reserveringen voor de <?php echo esc_html( $data['oven']->naam ); ?></h1>
	<table id="reserveringen<?php echo esc_attr( $data['oven']->id ); ?>" class="kleistad_reserveringen"
		data-oven_id="<?php echo esc_attr( $data['oven']->id ); ?>"
		data-oven-naam="<?php echo esc_attr( $data['oven']->naam ); ?>"
		data-maand="<?php echo esc_attr( date( 'n' ) ); ?>"
		data-jaar="<?php echo esc_attr( date( 'Y' ) ); ?>" >
		<tr>
			<th>de reserveringen worden opgehaald...</th>
		</tr>
	</table>

	<div id ="kleistad_oven" class="kleistad_form_popup">
	<form action="#" method="post">
		<input id="kleistad_oven_id" type="hidden" >
		<input type ="hidden" id="kleistad_gebruiker_id" >
		<table class="kleistad_form">
			<thead>
				<tr>
					<th colspan="3">Reserveer de oven op <span id="kleistad_wanneer"></span></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><label>Soort stook</label></td>
					<td colspan="2" >
						<select id="kleistad_soortstook">
							<option value="Biscuit" selected>Biscuit</option>
							<option value="Glazuur" >Glazuur</option>
							<option value="Overig" >Overig</option>
						</select>
					</td>
				</tr>
				<tr>
					<td><label>Temperatuur</label></td>
					<td colspan="2"><input type="number" min="0" max="1300" id="kleistad_temperatuur"></td>
				</tr>
				<tr>
					<td><label>Programma</label></td>
					<td colspan="2"><input type="number" min="0" max="99" id="kleistad_programma"></td>
				</tr>
				<tr id="kleistad_stoker_row" >
					<td><label>Stoker</label></td>
					<td><span id="kleistad_stoker"><?php echo esc_html( $data['huidige_gebruiker']->display_name ); ?></span><input type="hidden" name="kleistad_stoker_id" id="kleistad_1e_stoker" value="<?php echo esc_attr( $data['huidige_gebruiker']->ID ); ?>" /></td>
					<td><input type="number" name="kleistad_stoker_perc" readonly /> %</td>
				</tr>
				<tr class="kleistad_medestoker_row" >
					<td><label>Stoker</label></td>
					<td>
						<select name="kleistad_stoker_id" class="kleistad_verdeel" >
							<option value="0" >&nbsp;</option>
							<?php
							foreach ( $data['gebruikers'] as $gebruiker ) :
								if ( Kleistad_Roles::reserveer( $gebruiker->id ) && ( $gebruiker->id !== $data['huidige_gebruiker']->ID || Kleistad_Roles::override() ) ) :
									?>
							<option value="<?php echo esc_attr( $gebruiker->id ); ?>"><?php echo esc_html( $gebruiker->display_name ); ?></option>
										<?php
									endif;
								endforeach;
							?>
						</select>
					</td>
					<td><input type="number" class="kleistad_verdeel" name="kleistad_stoker_perc" min="0" max="100" > %</td>
				</tr>
				<tr>
					<td><button style="font-size:16px;border-radius:25%;width:40px;text-align:center;padding:0px;" id="kleistad_stoker_toevoegen"><span class="genericon genericon-plus"></span></button></td>
					<td colspan="2"></td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="3">
						<input type ="hidden" id="kleistad_dag">
						<input type ="hidden" id="kleistad_maand">
						<input type ="hidden" id="kleistad_jaar">
						<span id="kleistad_tekst"></span>
					</th>
				</tr>
				<tr>
					<th><button type="button" id="kleistad_muteer" class="kleistad_muteer" >Wijzig</button></th>
					<th><button type="button" id="kleistad_verwijder" class="kleistad_verwijder" >Verwijder</button></th>
					<th><button type="button" id="kleistad_sluit" class="kleistad_sluit" >Sluit</button></th>
				</tr>
			</tfoot>
		</table>
	</form>
</div>
<?php endif ?>
