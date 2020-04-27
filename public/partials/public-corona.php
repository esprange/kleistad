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

$this->form();

if ( 'gebruikers' === $data['actie'] ) : ?>
<div class="kleistad_row" style="padding-top:20px;" >
	<select name="gebruiker" id="kleistad_gebruiker" >
	<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
		<option <?php echo selected( $gebruiker->ID, $data['id'] ); ?> value="<?php echo esc_attr( $gebruiker->ID ); ?>"><?php echo esc_html( $gebruiker->display_name ); ?></option>
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
		if ( $datum < strtotime( 'today 00:00' ) ) :
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
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $data['overzicht'] as $datum => $tijden ) :
		foreach ( $tijden as $tijd => $gebruik ) :
			?>
		<tr>
			<td data-sort="<?php echo esc_attr( $datum ); ?>" ><?php echo esc_html( date( 'd-m-Y', $datum ) ); ?></td>
			<td><?php echo esc_html( $tijd ); ?></td>
			<td><?php echo esc_html( $gebruik['H'] ); ?></td>
			<td><?php echo esc_html( $gebruik['D'] ); ?></td>
			<td><?php echo esc_html( $gebruik['B'] ); ?></td>
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
<input type="hidden" name="id" value="<?php echo esc_attr( $data['input']['id'] ); ?>">
<input type="hidden" id="kleistad_naam" value="<?php echo esc_attr( $data['input']['naam'] ); ?>">
<div class="kleistad_row">
	<div style="float:left;margin-bottom:10px">
	<input type=text name="datum" id="kleistad_datum" class="kleistad_datum" data-datums='<?php echo esc_attr( wp_json_encode( $data['datums'] ) ?: '[]' ); ?>'
		value="<?php echo esc_attr( date( 'd-m-Y', $data['input']['datum'] ) ); ?>" readonly="readonly" >
	</div>
	<div style="float:right;margin-bottom:10px">
		<button name="kleistad_submit_corona" type="submit" >Bevestigen</button>
	</div>
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
		<button name="kleistad_submit_corona" type="submit" >Bevestigen</button>
	</div>
</div>
<?php endif ?>
</form>
