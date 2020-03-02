<?php
/**
 * Toon het betaling formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( 'betalen' === $data['actie'] ) :
	$this->form();
	?>

	<input type="hidden" name="order_id" value="<?php echo esc_attr( $data['order_id'] ); ?>" />
	<input type="hidden" name="artikel_type" value="<?php echo esc_attr( $data['artikel_type'] ); ?>" />
	<input type="hidden" name="betaal" value="ideal" />
	<h2>Overzicht betaling <?php echo esc_html( $data['betreft'] ); ?> </h2>

	<div class="kleistad_row">
		<div class="kleistad_col_3">
			<p>Voor</p>
		</div>
		<div class="kleistad_col_7">
			<p><?php echo esc_html( $data['klant'] ); ?></p>
		</div>
	</div>
	<table class="kleistad_datatable  display compact nowrap" data-paging="false" data-searching="false" data-ordering="false" data-info="false" >
		<thead>
			<tr><th>Aantal</th><th>Omschrijving</th><th>Stuksprijs</th><th>Prijs</th>
		</thead>
		<tbody>
	<?php
	foreach ( $data['regels']  as $regel ) :
		?>
		<tr>
			<td style="text-align:right" ><?php echo esc_html( $regel['aantal'] ); ?></td>
			<td><?php echo esc_html( $regel['artikel'] ); ?></td>
			<td style="text-align:right" >&euro; <?php echo esc_html( number_format_i18n( $regel['prijs'] + $regel['btw'], 2 ) ); ?></td>
			<td style="text-align:right" >&euro; <?php echo esc_html( number_format_i18n( $regel['aantal'] * ( $regel['prijs'] + $regel['btw'] ), 2 ) ); ?></td>
		</tr>
		<?php
		endforeach
	?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="3" style="text-align:right">Reeds betaald</td>
			<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $data['reeds_betaald'], 2 ) ); ?></td>
		</tr>
		<tr>
			<td colspan="3" style="text-align:right">Totale kosten</td>
			<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $data['reeds_betaald'] + $data['openstaand'], 2 ) ); ?></td>
		</tr>
		<tr>
			<td colspan="3" style="text-align:right">Saldo</td>
			<td style="text-align:right"><strong>&euro; <?php echo esc_html( number_format_i18n( $data['openstaand'], 2 ) ); ?></strong></td>
		</tr>
		</tfoot>
	</table>
	<?php if ( 0 < $data['openstaand'] ) : ?>
	<div class ="kleistad_row">
		<div class="kleistad_col_10">
			<?php \Kleistad\Betalen::issuers(); ?>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_10" style="padding-top: 20px;">
			<button type="submit" name="kleistad_submit_betaling" id="kleistad_submit">Betalen</button><br />
		</div>
	</div>
	<?php elseif ( 0 > $data['openstaand'] ) : ?>
	<div class="kleistad_row">
		<div class="kleistad_col_10" style="padding-top: 20px;">
			Het nog openstaande bedrag zal zo spoedig mogelijk teruggestort worden
			<?php echo $this->goto_home(); // phpcs:ignore ?>
		</div>
	</div>
	<?php else : ?>
	<div class="kleistad_row">
		<div class="kleistad_col_10" style="padding-top: 20px;">
			Er is geen verdere actie nodig
			<?php echo $this->goto_home(); // phpcs:ignore ?>
		</div>
	</div>
	<?php endif ?>
</form>
<?php endif ?>
