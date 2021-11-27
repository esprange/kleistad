<?php
/**
 * Toon het rapport formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de email formulier.
 */
class Public_Rapport_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() {
		?>
		<p>Stookrapport voor <?php echo esc_html( $this->data['naam'] ); ?> (je huidig saldo is &euro; <?php echo esc_html( $this->data['saldo'] ); ?>)</p>
		<table class="kleistad-datatable display compact" data-order= '[[ 0, "desc" ]]' >
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
				<?php foreach ( $this->data['items'] as $item ) : ?>
				<tr>
					<td data-sort=<?php echo esc_attr( $item['datum'] ); ?> ><?php echo esc_html( date( 'd-m-Y', $item['datum'] ) ); ?></td>
					<td><?php echo esc_html( $item['oven'] ); ?></td>
					<td><?php echo esc_html( $item['stoker'] ); ?></td>
					<td><?php echo esc_html( $item['stook'] ); ?></td>
					<td><?php echo esc_html( $item['temp'] ); ?></td>
					<td><?php echo esc_html( $item['prog'] ); ?></td>
					<td><?php echo esc_html( $item['perc'] ); ?></td>
					<td>&euro; <?php echo esc_html( $item['kosten'] ); ?></td>
					<td data-sort="<?php echo (int) $item['voorlopig']; ?>"><span <?php echo $item['voorlopig'] ? 'class="dashicons dashicons-yes"' : ''; ?> ></span></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		<?php
	}

}
