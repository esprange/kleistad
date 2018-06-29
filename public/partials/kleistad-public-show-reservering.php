<?php
/**
 * Toon de reservering, wordt vanuit AJAX call opgebouwd.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
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

