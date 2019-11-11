<?php
/**
 * Toon het stookoverzicht rapport
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

?>
<p>Stookrapport voor <?php echo esc_html( $data['naam'] ); ?> (je huidig saldo is &euro; <?php echo esc_html( $data['saldo'] ); ?>)</p>

<table class="kleistad_datatable display compact" data-order= '[[ 0, "desc" ]]' >
	<thead>
		<tr>
			<th>Datum</th>
			<th>Oven</th>
			<th>Stoker</th>
			<th>Stook</th>
			<th data-class-name="dt-body-right">Temp</th>
			<th data-class-name="dt-body-right">Prog</th>
			<th data-class-name="dt-body-right">%</th>
			<th data-class-name="dt-body-right">Kosten</th>
			<th data-class-name="dt-body-center">Voorlopig</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $data['items'] as $item ) : ?>
		<tr>
			<td data-sort=<?php echo esc_attr( $item['datum'] ); ?> ><?php echo esc_html( date( 'd-m-Y', $item['datum'] ) ); ?></td>
			<td><?php echo esc_html( $item['oven'] ); ?></td>
			<td><?php echo esc_html( $item['stoker'] ); ?></td>
			<td><?php echo esc_html( $item['stook'] ); ?></td>
			<td><?php echo esc_html( $item['temp'] ); ?></td>
			<td><?php echo esc_html( $item['prog'] ); ?></td>
			<td><?php echo esc_html( $item['perc'] ); ?></td>
			<td>&euro; <?php echo esc_html( $item['kosten'] ); ?></td>
			<td data-sort="<?php echo (int) $item['voorlopig']; ?>"><span <?php echo $item['voorlopig'] ? 'class="genericon genericon-checkmark"' : ''; ?> ></span></td>
		</tr>
		<?php endforeach ?>
	</tbody>
</table>
