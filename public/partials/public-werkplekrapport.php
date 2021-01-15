<?php
/**
 * Toon een werkplek rapport
 *
 * @link       https://www.kleistad.nl
 * @since      6.12.1
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

namespace Kleistad;

if ( ! isset( $data['rapport'] ) ) :
	?>

<form method="GET" action="<?php echo esc_attr( get_permalink() ); ?>">

<div class="kleistad_row">
	<div class="kleistad_col_3">
		<label class="kleistad_label" for="kleistad_vanaf_datum" >Vanaf</label>
	</div>
	<div class="kleistad_col_3">
		<input type="text" name="vanaf_datum" id="kleistad_vanaf_datum" class="kleistad_datum" value="<?php echo esc_attr( date( 'd-m-Y', strtotime( '-2 week' ) ) ); ?>"  readonly="readonly" />
	</div>
</div>
<div class="kleistad_row" >
	<div class="kleistad_col_3">
		<label class="kleistad_label" for="kleistad_tot_datum" >Tot</label>
	</div>
	<div class="kleistad_col_3">
		<input type="text" name="tot_datum" id="kleistad_tot_datum" class="kleistad_datum" value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
	</div>
</div>
	<?php if ( 'individueel' === $data['actie'] ) : ?>
<div class="kleistad_row" >
	<div class="kleistad_col_3">
		<label class="kleistad_label" for="kleistad_gebruiker" >Gebruiker</label>
	</div>
	<div class="kleistad_col_3">
		<select name="gebruiker_id" >
		<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
			<option value="<?php echo esc_attr( $gebruiker['ID'] ); ?>" ><?php echo esc_html( $gebruiker['display_name'] ); ?></option>
		<?php endforeach ?>
		</select>
	</div>
</div>
<?php endif ?>
<div class="kleistad_row" style="padding-top:20px;" >
	<button type="submit" >Rapport</button>
</div>
</form>
	<?php
	return;
endif;

if ( 'individueel' === $data['actie'] ) :
	?>
<h2>Overzicht werkplekgebruik vanaf <?php echo esc_html( date( 'd-m-Y', $data['vanaf_datum'] ) ); ?> tot <?php echo esc_html( date( 'd-m-Y', $data['tot_datum'] ) ); ?> door <?php echo esc_html( get_user_by( 'id', $data['gebruiker_id'] )->display_name ); ?></h2>
<table class="kleistad_datatable display compact" data-order= '[[ 0, "desc" ]]' >
<thead>
		<tr>
			<th>Datum</th>
			<th>Dagdeel</th>
			<th>Activiteit</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $data['rapport'] as $datum => $regel ) :
		foreach ( $regel as $dagdeel => $activiteit ) :
			?>
		<tr>
			<td data-sort="<?php echo esc_attr( $datum ); ?>" ><?php echo esc_html( date( 'd-m-Y', $datum ) ); ?></td>
			<td><?php echo esc_html( $dagdeel ); ?></td>
			<td><?php echo esc_html( $activiteit ); ?></td>
		</tr>
					<?php
	endforeach;
endforeach
	?>
	</tbody>
</table>

	<?php
	return;
endif;
?>
<h2>Overzicht werkplekgebruik vanaf <?php echo esc_html( date( 'd-m-Y', $data['vanaf_datum'] ) ); ?> tot <?php echo esc_html( date( 'd-m-Y', $data['tot_datum'] ) ); ?></h2>
<table class="kleistad_datatable display compact" data-order= '[[ 0, "desc" ]]' >
	<thead>
		<tr>
			<th>Datum</th>
			<th>Dagdeel</th>
			<th>Activiteit</th>
			<th>Naam</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $data['rapport'] as $datum => $regel ) :
		foreach ( $regel as $dagdeel => $activiteiten ) :
			foreach ( $activiteiten as $activiteit => $gebruiker_ids ) :
				foreach ( $gebruiker_ids as $gebruiker_id ) :
					?>
		<tr>
			<td data-sort="<?php echo esc_attr( $datum ); ?>" ><?php echo esc_html( date( 'd-m-Y', $datum ) ); ?></td>
			<td><?php echo esc_html( $dagdeel ); ?></td>
			<td><?php echo esc_html( $activiteit ); ?></td>
			<td><?php echo esc_html( get_user_by( 'id', $gebruiker_id )->display_name ); ?></td>
		</tr>
					<?php
	endforeach;
	endforeach;
endforeach;
endforeach
	?>
	</tbody>
</table>
