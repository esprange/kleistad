<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! Kleistad_Roles::reserveer() ) : ?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>
<p>Stookrapport voor <?php echo esc_html( $data['naam'] ); ?> (je huidig saldo is &euro; <?php echo esc_html( $data['saldo'] ); ?>)</p>

<table class="kleistad_rapport" data-order='[[ 1, "asc" ]]' >
	<thead>
		<tr>
			<th>Datum</th>
			<th>SDatum</th>
			<th>Oven</th>
			<th>Stoker</th>
			<th>Stook</th>
			<th>Temp</th>
			<th>Prog</th>
			<th>%</th>
			<th>Kosten</th>
			<th>Voorlopig</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $data['items'] as $item ) : ?>
		  <tr>
			  <td><?php echo esc_html( $item['datum'] ); ?></td>
			  <td><?php echo esc_html( - $item['sdatum'] ); ?></td>
			  <td><?php echo esc_html( $item['oven'] ); ?></td>
			  <td><?php echo esc_html( $item['stoker'] ); ?></td>
			  <td><?php echo esc_html( $item['stook'] ); ?></td>
			  <td><?php echo esc_html( $item['temp'] ); ?></td>
			  <td><?php echo esc_html( $item['prog'] ); ?></td>
			  <td><?php echo esc_html( $item['perc'] ); ?></td>
			  <td>&euro; <?php echo esc_html( $item['kosten'] ); ?></td>
			  <td><span class="<?php echo esc_attr( $item['voorlopig'] ); ?>"></span></td>
		  </tr>
		<?php endforeach ?>
	</tbody>
</table>

<?php endif ?>
