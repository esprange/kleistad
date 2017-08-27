<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! Kleistad_Roles::reserveer() ) : ?>
<p>Geen toegang tot dit formulier</p>
<?php
else :
	extract( $data );
?>
<p>Stookrapport voor <?php echo $naam; ?> (je huidig saldo is &euro; <?php echo $saldo; ?>)</p>

<table class="kleistad_rapport" data-order='[[ 1, "asc" ]]' >
	<thead>
		<tr>
			<th>Datum</th>
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
		<?php foreach ( $items as $item ) : ?>
		  <tr>
			  <td><span style="display:none"><?php echo $item['sdatum']; ?></span> <?php echo $item['datum']; ?></td>
			  <td><?php echo $item['oven']; ?></td>
			  <td><?php echo $item['stoker']; ?></td>
			  <td><?php echo $item['stook']; ?></td>
			  <td><?php echo $item['temp']; ?></td>
			  <td><?php echo $item['prog']; ?></td>
			  <td><?php echo $item['perc']; ?></td>
			  <td>&euro; <?php echo $item['kosten']; ?></td>
			  <td style="text-align:center"><?php echo $item['voorlopig']; ?></td>
		  </tr>
		<?php endforeach ?>
	</tbody>
</table>

<?php endif ?>
