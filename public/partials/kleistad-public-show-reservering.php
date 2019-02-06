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

$titel = strftime( '%B', mktime( 0, 0, 0, $maand, 1, $jaar ) ) . '-' . $jaar;

list( $volgende_maand, $volgende_maand_jaar ) = explode( ',', date( 'n,Y', mktime( 0, 0, 0, $maand + 1, 1, $jaar ) ) );
list( $vorige_maand, $vorige_maand_jaar )     = explode( ',', date( 'n,Y', mktime( 0, 0, 0, $maand - 1, 1, $jaar ) ) );

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
		<th colspan="2" ><strong><?php echo esc_html( $titel ); ?></strong></th>
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
		echo $row; // phpcs:ignore
	endforeach;
	?>
</tbody>
<tfoot>
	<tr>
		<th><button type="button" class="kleistad_periode"
					data-oven_id="<?php echo esc_attr( $oven_id ); ?>"
					data-maand="<?php echo esc_attr( $vorige_maand ); ?>"
					data-jaar="<?php echo esc_attr( $vorige_maand_jaar ); ?>" >eerder</button></th>
		<th colspan="2"><strong><?php echo esc_html( $titel ); ?></strong></th>
		<th style="text-align:right"><button type="button" class="kleistad_periode"
					data-oven_id="<?php echo esc_attr( $oven_id ); ?>"
					data-maand="<?php echo esc_attr( $volgende_maand ); ?>"
					data-jaar="<?php echo esc_attr( $volgende_maand_jaar ); ?>" >later</button></th>
	</tr>
</tfoot>

