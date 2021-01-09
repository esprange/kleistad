<?php
/**
 * Toon het werkplek reservering formulier
 *
 * @link       https://www.kleistad.nl
 * @since      6.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

namespace Kleistad;

$this->form();
$huidige_gebruiker = wp_get_current_user();

if ( 'gebruikers' === $data['actie'] ) : ?>
<div class="kleistad_row" style="padding-top:20px;" >
	<select name="gebruiker" id="kleistad_gebruiker" >
	<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
		<option <?php selected( $gebruiker->ID, $data['id'] ); ?> value="<?php echo esc_attr( $gebruiker->ID ); ?>"><?php echo esc_html( $gebruiker->display_name ); ?></option>
	<?php endforeach ?>
	</select>
</div>
<table class="kleistad_datatable display compact nowrap" >
	<thead>
		<tr>
			<th>Datum</th>
			<th>Tijd</th>
			<th>Gebruik</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$werk = [
		'H' => 'Handvormen',
		'D' => 'Draaien',
		'B' => 'Bovenruimte',
	];
	foreach ( $data['gebruik'] as $datum => $tijden ) :
		foreach ( $tijden as $tijd => $gebruik ) :
			?>
		<tr>
			<td data-sort="<?php echo esc_attr( $datum ); ?>" ><?php echo esc_html( date( 'd-m-Y', $datum ) ); ?></td>
			<td><?php echo esc_html( $tijd ); ?></td>
			<td><?php echo esc_html( $werk[ $gebruik ] ); ?></td>
		</tr>
			<?php
			endforeach;
		endforeach
	?>
	</tbody>
</table>

<button type="button" class="kleistad_download_link" data-actie="gebruiker" >Download</button>
	<?php
elseif ( 'overzicht' === $data['actie'] ) :
	$key = 0;
	foreach ( $data['overzicht'] as $datum => $tijden ) {
		if ( $datum < strtotime( 'today' ) ) :
			$key += count( $tijden );
		else :
			break;
		endif;
	}
	?>
<table class="kleistad_datatable display compact nowrap" data-display-start="<?php echo esc_attr( $key ); ?>">
	<thead>
		<tr>
			<th>Datum</th>
			<th>Tijd</th>
			<th>Handvormen</th>
			<th>Draaien</th>
			<th>Boven</th>
			<th>Totaal</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $data['overzicht'] as $datum => $tijden ) :
		foreach ( $tijden as $tijd => $gebruik ) :
			$totaal = $gebruik['H'] + $gebruik['D'] + $gebruik['B'];
			?>
		<tr>
			<td data-sort="<?php echo esc_attr( $datum ); ?>" ><?php echo esc_html( date( 'd-m-Y', $datum ) ); ?></td>
			<td><?php echo esc_html( $tijd ); ?></td>
			<td><?php echo esc_html( $gebruik['H'] ); ?></td>
			<td><?php echo esc_html( $gebruik['D'] ); ?></td>
			<td><?php echo esc_html( $gebruik['B'] ); ?></td>
			<td><strong><?php echo esc_html( $totaal ); ?></strong></td>
		</tr>
			<?php
			endforeach;
		endforeach
	?>
	</tbody>
</table>
<div class="kleistad_row" style="padding-top:20px;" >
	<button type="button" class="kleistad_download_link" data-actie="overzicht" >Download</button>
</div>
<?php else : ?>
	<?php if ( current_user_can( BESTUUR ) || current_user_can( DOCENT ) ) : ?>
	<div class="kleistad_row" id="kleistad_selectie_blok">
		<div class="kleistad_col_3">
			<input type="radio" name="gebruik" id="kleistad_corona_zelf" checked class="kleistad_select_gebruiker kleistad_input_cbr" value="zelf" >
			<label class="kleistad_label_cbr" for="kleistad_corona_zelf">Reserveren voor jezelf</label>
		</div>
		<div class="kleistad_col_3">
			<input type="radio" name="gebruik" id="kleistad_corona_ander" class="kleistad_select_gebruiker kleistad_input_cbr" value="ander" >
			<label class="kleistad_label_cbr" for="kleistad_corona_ander">Reserveren voor een ander</label>
		</div>
		<div class="kleistad_col_4" id="kleistad_select_ander" style="display:none" >
			<select name="id" id="kleistad_select_gebruiker" >
				<option value="<?php echo esc_attr( $huidige_gebruiker->ID ); ?>" <?php selected( $huidige_gebruiker->ID, $data['input']['id'] ); ?> ><?php echo esc_html( $huidige_gebruiker->display_name ); ?></option>
			<?php foreach ( $data['cursisten_za'] as $cursist_za ) : ?>
				<option value="<?php echo esc_attr( $cursist_za['id'] ); ?>" <?php selected( $cursist_za['id'], $data['input']['id'] ); ?> ><?php echo esc_html( $cursist_za['naam'] ); ?></option>
			<?php endforeach ?>
			</select>
		</div>
	</div>
	<div class="kleistad_row" id="kleistad_gebruiker_blok" > 
	</div>
	<?php else : ?>
	<input type="hidden" name="id" value="<?php echo esc_attr( $data['input']['id'] ); ?>">
	<?php endif ?>
<input type="hidden" id="kleistad_naam" value="<?php echo esc_attr( $data['input']['naam'] ); ?>">

<div id="kleistad_meester">
	<?php if ( current_user_can( BESTUUR ) ) : ?>
<select id="kleistad_meester_selectie" name="meester" >
		<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
	<option value="<?php echo esc_attr( $gebruiker->ID ); ?>" ><?php echo esc_html( $gebruiker->display_name ); ?></option>
		<?php endforeach ?>
</select>g
<input type="checkbox" id="kleistad_meester_standaard" >standaard</input>
<?php endif ?>
</div>

<div class="kleistad_row">
	<div style="float:left;margin-bottom:10px">
	<input type=text name="datum" id="kleistad_datum" class="kleistad_datum" data-datums='<?php echo esc_attr( wp_json_encode( $data['datums'] ) ?: '[]' ); ?>'
		value="<?php echo esc_attr( date( 'd-m-Y', $data['input']['datum'] ) ); ?>" readonly="readonly" >
	<button type="button" id="kleistad_eerder" ><span class="dashicons dashicons-controls-back"></span></button>
	<button type="button" id="kleistad_later" ><span class="dashicons dashicons-controls-forward"></span></button>
	</div>
	<div style="float:right;margin-bottom:10px">
		<button name="kleistad_submit_corona" id=kleistad_submit_corona" type="submit" >Bevestigen</button>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_2"><strong>beheerder</strong></div>
	<?php
	foreach ( $data['beschikbaarheid'] as $index => $beschikbaarheid ) :
		$meester      = get_user_by( 'id', $beschikbaarheid['M']['id'] );
		$meester_naam = false === $meester ? '' : $meester->display_name;
		?>
		<div class="kleistad_row kleistad_corona_tijden" >
			<strong><?php echo esc_html( $beschikbaarheid['T'] ); ?></strong>
		</div>
		<div class="kleistad_col_2">
			<?php if ( current_user_can( BESTUUR ) ) : ?>
				<input type="hidden" value="<?php echo esc_attr( $beschikbaarheid['M']['s'] ); ?>" name="<?php echo esc_attr( "standaard[$index]" ); ?>" id="<?php echo esc_attr( "standaard{$index}" ); ?>" >
				<label for="<?php echo esc_attr( "meester{$index}" ); ?>" style="width:100%" ><?php echo esc_html( $meester_naam ?: '---' ); ?></label>
				<input type="checkbox" class="kleistad_meester" value="<?php echo esc_attr( $beschikbaarheid['M']['id'] ); ?>"
					name="<?php echo esc_attr( "meester[$index]" ); ?>" id="<?php echo esc_attr( "meester{$index}" ); ?>"
					data-tijd="<?php echo esc_html( $beschikbaarheid['T'] ); ?>"
					data-blokdeel="<?php echo esc_attr( $index ); ?>" />
			<?php else : ?>
				<?php echo esc_html( $meester_naam ); ?>
			<?php endif ?>
		</div>
	<?php endforeach ?>
</div>
	<?php
	foreach ( [
		'H' => [
			'titel' => 'handvormen',
			'kleur' => 'rgb( 255, 229, 153 )',
		],
		'D' => [
			'titel' => 'draaien',
			'kleur' => 'rgb( 247, 202, 172 )',
		],
		'B' => [
			'titel' => 'bovenruimte',
			'kleur' => 'rgb( 217, 217, 217 )',
		],
	] as $werk => $opmaak ) :
		?>
<div class="kleistad_row" style="background:<?php echo esc_attr( $opmaak['kleur'] ); ?>">
	<div class="kleistad_col_2">
		<strong><?php echo esc_html( $opmaak['titel'] ); ?></strong>
	</div>
		<?php foreach ( $data['beschikbaarheid'] as $index => $beschikbaarheid ) : ?>
	<div class="kleistad_col_2">
		<table>
			<tr>
				<th><?php echo esc_html( $beschikbaarheid['T'] ); ?></th>
			</tr>
			<?php
				$button = false;
			for ( $plek = 0; $plek < $beschikbaarheid[ $werk ]; $plek++ ) :
				?>
			<tr>
				<td>
				<?php if ( isset( $data['reserveringen'][ $index ][ $werk ]['namen'][ $plek ] ) ) : ?>
					<span style="font-size:x-small"><?php echo esc_html( $data['reserveringen'][ $index ][ $werk ]['namen'][ $plek ] ); ?></span>
					<?php
					elseif ( ! $button ) :
						$button   = true;
						$aanwezig = $data['reserveringen'][ $index ][ $werk ]['aanwezig'] ?? false;
						?>
					<label for="<?php echo esc_attr( "res{$index}_{$werk}" ); ?>" style="width:100%" >
						<?php echo esc_html( $aanwezig ? $data['input']['naam'] : 'reserveren' ); ?>
					</label>
					<input type="checkbox" name="<?php echo esc_attr( "res[$index][$werk]" ); ?>" id="<?php echo esc_attr( "res{$index}_{$werk}" ); ?>"
						<?php checked( $aanwezig ); ?> class="kleistad_corona" >
				<?php else : ?>
					&nbsp;
				<?php endif ?>
				</td>
			</tr>
			<?php endfor ?>
		</table>
	</div>
	<?php endforeach ?>
</div>
	<?php endforeach ?>
<div class="kleistad_row">
	<div style="float:right;margin-top:10px">
		<button name="kleistad_submit_corona" type="submit" id="kleistad_submit">Bevestigen</button>
	</div>
</div>
<?php endif ?>
</form>
