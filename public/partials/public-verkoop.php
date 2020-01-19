<?php
/**
 * Toon het verkoop formulier
 *
 * @link       https://www.kleistad.nl
 * @since      6.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

$this->form();
?>
<div class="kleistad_row">
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_klant">Klant naam</label>
	</div>
	<div class="kleistad_col_7">
		<input class="kleistad_input" type="text" size="20" name="klant" required
			value="<?php echo esc_attr( $data['input']['klant'] ); ?>"/>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_3 kleistad_label">
		<label for="kleistad_email">Email adres</label>
	</div>
	<div class="kleistad_col_7">
		<input class="kleistad_input" type="email" name="email" required
			value="<?php echo esc_attr( $data['input']['email'] ); ?>"/>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_6 kleistad_label">
		<label>Omschrijving</label>
	</div>
	<div class="kleistad_col_2 kleistad_label">
		<label>Stuksprijs</label>
	</div>
	<div class="kleistad_col_2 kleistad_label">
		<label>Aantal</label>
	</div>
</div>
	<?php
		$index = 0;
		$count = count( $data['input']['omschrijving'] );
	do {
		?>
<div class="kleistad_row">
	<div class="kleistad_col_6">
		<input type="text" name="omschrijving[]" value="<?php echo esc_attr( $data['input']['omschrijving'][ $index ] ); ?>" >
	</div>
	<div class="kleistad_col_2">
		<input type="number" step="0.01" name="prijs[]" value="<?php echo esc_attr( $data['input']['prijs'][ $index ] ); ?>" >
	</div>
	<div class="kleistad_col_2">
		<input type="number" step="0.01" name="aantal[]" value="<?php echo esc_attr( $data['input']['aantal'][ $index ] ); ?>" >
	</div>
</div>
		<?php
		$index++;
	} while ( $index < $count );
	?>
<div class="kleistad_row">
	<button class="extra_regel ui-button ui-widget ui-corner-all" ><span class="dashicons dashicons-plus"></span></button><br/>&nbsp;
</div>
<div class="kleistad_row">
	<button type="submit" name="kleistad_submit_verkoop" value="verzenden">Verzenden</button>
</div>
</form>
