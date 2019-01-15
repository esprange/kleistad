<?php
/**
 * Toon het kalender formulier
 *
 * @link       https://www.kleistad.nl
 * @since      5.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

if ( ! isset( $modus ) ) :
	$start_datum = wp_json_encode(
		[
			'dag'   => 1,
			'maand' => date( 'n' ),
			'jaar'  => date( 'Y' ),
		]
	);

	?>
	<div id="kleistad_event">
		<!-- popup dialog -->
	</div>
	<div>
		<table id="kleistad_kalender" data-datum ='<?php echo esc_attr( $start_datum ); ?>' data-modus ="maand" >
			<tr>
				<th>de kalender wordt opgehaald...</th>
			</tr>
		</table>
	</div>
	<?php
elseif ( 'maand' === $modus ) :
	list( $huidig_maand, $huidig_jaar, $vandaag ) = explode( ',', date( 'm,Y,j' ) );
	list( $maand, $jaar, $maandnaam, $weekdag )   = explode( ',', strftime( '%m,%Y,%B,%w', $datum ) );
	?>
	<caption style="text-align:center;">
		<span id="kleistad_prev" class="dashicons dashicons-arrow-left-alt"></span>
		<?php echo esc_html( "$maandnaam $jaar" ); ?>
		<span id="kleistad_next" class="dashicons dashicons-arrow-right-alt"></span>
	</caption>
	<tr>
	<?php
	for ( $n = 0, $t = 4 * 86400; $n < 7; $n++, $t += 86400 ) : // January 4, 1970 was a Sunday.
		?>
		<th><?php echo esc_html( strftime( '%A', $t ) ); ?></th>
		<?php
	endfor;
	?>
	</tr>
	<tr>
	<?php
	if ( $weekdag > 0 ) :
		for ( $i = 1; $i < $weekdag; $i++ ) :
			?>
		<td>&nbsp;</td>
			<?php
		endfor;
	endif;

	for ( $dag = 1, $dageninmaand = date( 't', $datum ); $dag <= $dageninmaand; $dag++, $weekdag++ ) :
		if ( 7 < $weekdag ) :
			$weekdag = 1;
			?>
	</tr>
	<tr>
			<?php
		endif;
		$kleur            = ( $jaar == $huidig_jaar && $maand == $huidig_maand && $dag == $vandaag ) ? 'lavender' : 'white'; // phpcs:ignore
		$event_text_kleur = ( $jaar <= $huidig_jaar && $maand <= $huidig_maand && $dag <= $vandaag ) ? 'gray' : 'black'; // phpcs:ignore
		?>
		<td class="kleistad_kalender_dag" style="background-color:<?php echo esc_attr( $kleur ); ?>;">
		<span><?php echo esc_html( $dag ); ?></span><br />
		<!--span class="kleistad_dag">< ?php echo esc_html( $dag ); ?></span><br / -->
		<?php
		if ( isset( $dagen[ $dag ] ) && is_array( $dagen[ $dag ] ) ) :
			?>
			<div class="kleistad_kalender_events" >
			<?php
			foreach ( $dagen[ $dag ] as $event ) :
				?>
				<span style="color:<?php echo esc_attr( $event['kleur'] ); ?>;">&#9679;</span>
				<span style="color:<?php echo esc_attr( $event_text_kleur ); ?>;" class="kleistad_event_info" data-event='<?php echo wp_json_encode( $event['info'] ); ?>' ><?php echo esc_html( $event['tekst'] ); ?></span>
				<br />
				<?php
			endforeach;
			?>
			</div>
			<?php
		endif;
		?>
		</div>
		</td>
		<?php
	endfor;

	if ( 7 !== $weekdag ) :
		for ( $i = $weekdag; $i <= 7; $i ++ ) :
			?>
		<td>&nbsp;</td>
			<?php
		endfor;
	endif;

	?>
	</tr>
	<?php
elseif ( 'dag' === $modus ) :
	list( $maandnaam, $dagnaam, $dag, $jaar ) = explode( ',', strftime( '%B,%A,%d,%Y', $datum ) );
	?>
	<caption style="text-align:center;">
		<span id="kleistad_prev" class="dashicons dashicons-arrow-left-alt"></span>
		<?php echo esc_html( "$dagnaam, $dag " ); ?><!--span class="kleistad_maand"--><?php echo esc_html( $maandnaam ); ?><!--/span --><?php echo esc_html( " $jaar" ); ?>
		<span id="kleistad_next" class="dashicons dashicons-arrow-right-alt"></span>
	</caption>
	<tr>
	<?php
	if ( isset( $dagen[ $dag ] ) && is_array( $dagen[ $dag ] ) ) :
		foreach ( $dagen[ $dag ] as $event ) :
			?>
			<tr>
			<td style="border:none"><?php echo esc_html( $event['tekst'] ); ?></td>
			</tr>
			<?php
		endforeach;
	endif;
	?>
	<tr>
	<?php
endif

?>
