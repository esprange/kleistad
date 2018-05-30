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

?>
<thead>
	<tr>
		<th>
			<button type="button" class="kleistad_periode" 
					data-oven_id="<?php echo esc_attr( $oven_id ); ?>" 
					data-maand="<?php echo esc_attr( $vorige_maand ); ?>" 
					data-jaar="<?php echo esc_attr( $vorige_maand_jaar ); ?>" >eerder
				</button
		></th>
		<th colspan="2" ><strong><?php echo esc_html( $maandnaam[ $maand ] . '-' . $jaar ); ?></strong></th>
		<th style="text-align:right" >
			<button type="button" class="kleistad_periode" 
					data-oven_id="<?php echo esc_attr( $oven_id ); ?>" 
					data-maand="<?php echo esc_attr( $volgende_maand ); ?>" 
					data-jaar="<?php echo esc_attr( $volgende_maand_jaar ); ?>" >later
			</button>
		</th>
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
		echo $row; // WPCS: XSS ok.
	endforeach;
	?>
</tbody>
<tfoot>
	<tr>
		<th><button type="button" class="kleistad_periode" 
					data-oven_id="<?php echo esc_attr( $oven_id ); ?>" 
					data-maand="<?php echo esc_attr( $vorige_maand ); ?>" 
					data-jaar="<?php echo esc_attr( $vorige_maand_jaar ); ?>" >eerder</button></th>
		<th colspan="2"><strong><?php echo esc_html( $maandnaam[ $maand ] . '-' . $jaar ); ?></strong></th>
		<th style="text-align:right"><button type="button" class="kleistad_periode" 
					data-oven_id="<?php echo esc_attr( $oven_id ); ?>" 
					data-maand="<?php echo esc_attr( $volgende_maand ); ?>" 
					data-jaar="<?php echo esc_attr( $volgende_maand_jaar ); ?>" >later</button></th>
	</tr>
</tfoot>

