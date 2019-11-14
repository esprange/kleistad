<?php
/**
 * Toon het omzet rapportage formulier
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

$select_maand = (int) date( 'm', $data['periode'] );
$select_jaar  = (int) date( 'Y', $data['periode'] );
?>

<div class="kleistad_row">
	<div class="kleistad_col_3">
		<label class="kleistad_label" for="kleistad_maand" >Maand</label>
	</div>
	<div class="kleistad_col_3">
		<select name="maand" id="kleistad_maand" >
			<option value="1" <?php selected( 1, $select_maand ); ?> >januari</option>
			<option value="2" <?php selected( 2, $select_maand ); ?>>februari</option>
			<option value="3" <?php selected( 3, $select_maand ); ?>>maart</option>
			<option value="4" <?php selected( 4, $select_maand ); ?>>april</option>
			<option value="5" <?php selected( 5, $select_maand ); ?>>mei</option>
			<option value="6" <?php selected( 6, $select_maand ); ?>>juni</option>
			<option value="7" <?php selected( 7, $select_maand ); ?>>juli</option>
			<option value="8" <?php selected( 8, $select_maand ); ?>>augustus</option>
			<option value="9" <?php selected( 9, $select_maand ); ?>>september</option>
			<option value="10" <?php selected( 10, $select_maand ); ?>>oktober</option>
			<option value="11" <?php selected( 11, $select_maand ); ?>>november</option>
			<option value="12" <?php selected( 12, $select_maand ); ?>>december</option>
		</select>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3">
		<label class="kleistad_label" for="kleistad_jaar" >Jaar</label>
	</div>
	<div class="kleistad_col_3">
		<select name="jaar" id="kleistad_jaar">
			<?php
				$huidig_jaar = (int) date( 'Y' );
				$jaar        = 2019;
			while ( $jaar <= $huidig_jaar ) :
				?>
				<option value="<?php echo esc_attr( $jaar ); ?>" <?php selected( $jaar, $select_jaar ); ?> ><?php echo esc_html( $jaar++ ); ?></option>
				<?php endwhile ?>
		</select>
	</div>
</div>
<button type="button" id="kleistad_rapport" class="kleistad_edit_link" style="display:none" data-id="<?php echo esc_attr( "$select_jaar-$select_maand" ); ?>" data-actie="rapport" >Toon omzet</button>
<br/><br/>
<div>
	<table class="kleistad_datatable display compact nowrap" data-paging="false" data-searching="false" >
		<thead>
			<tr>
				<th>Omzet</th>
				<th>Bedrag</th>
				<th>BTW</th>
			</tr>
		</thead>
		<tbody>
	<?php foreach ( $data['omzet'] as $naam => $omzet ) : ?>
		<tr>
			<td><?php echo esc_html( $naam ); ?></td>
			<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $omzet['netto'], 2 ) ); ?></td>
			<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $omzet['btw'], 2 ) ); ?></td>
		</tr>
	<?php endforeach ?>
		</tbody>
	</table>
</div>
