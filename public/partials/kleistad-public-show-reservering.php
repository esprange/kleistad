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

?>
<thead>
	<tr>
		<th><button type="button" class="kleistad_periode" 
					data-oven_id="<?php echo $oven_id; ?>" 
					data-maand="<?php echo $vorige_maand; ?>" 
					data-jaar="<?php echo $vorige_maand_jaar; ?>" >eerder</button></th>
		<th colspan="2" ><strong><?php echo $maandnaam[ $maand ] . '-' . $jaar; ?></strong></th>
		<th style="text-align:right" ><button type="button" class="kleistad_periode" 
											  data-oven_id="<?php echo $oven_id; ?>" 
											  data-maand="<?php echo $volgende_maand; ?>" 
											  data-jaar="<?php echo $volgende_maand_jaar; ?>" >later</button></th>
	</tr>
	<tr>
		<th>Dag</th>
		<th>Wie?</th>
		<th>Soort stook</th>
		<th data-align="right">Temp</th>
		<!-- <th>Tijdstip stoken</th> -->
	</tr>
</thead>
<tbody>
	<?php
	foreach ( $rows as $row ) :
		echo $row;
	endforeach;
	?>
</tbody>
<tfoot>
	<tr>
		<th><button type="button" class="kleistad_periode" 
					data-oven_id="<?php echo $oven_id; ?>" 
					data-maand="<?php echo $vorige_maand; ?>" 
					data-jaar="<?php echo $vorige_maand_jaar; ?>" >eerder</button></th>
		<th colspan="2"><strong><?php echo $maandnaam[ $maand ] . '-' . $jaar; ?></strong></th>
		<th style="text-align:right"><button type="button" class="kleistad_periode" 
					data-oven_id="<?php echo $oven_id; ?>" 
					data-maand="<?php echo $volgende_maand; ?>" 
					data-jaar="<?php echo $volgende_maand_jaar; ?>" >later</button></th>
	</tr>
</tfoot>

