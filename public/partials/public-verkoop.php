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
<input type="hidden" name="klant_type" id="kleistad_klant_type" value="nieuw">
<div id="kleistad_tabs" class="ui-tabs uit-widget">
	<ul class="ui-tabs-nav">
		<li><a href="#tab_nieuwe_klant">Losse verkoop</a></li>
		<li><a href="#tab_vaste_klant">Bestaande klant</a></li>
	</ul>
	<div id="tab_nieuwe_klant">
		<div class="kleistad_row">
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_klant">Klant naam</label>
			</div>
			<div class="kleistad_col_7">
				<input class="kleistad_input" id="kleistad_klant" type="text" size="20" name="klant" required
					value="<?php echo esc_attr( $data['input']['klant'] ); ?>"/>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_email">Email adres</label>
			</div>
			<div class="kleistad_col_7">
				<input class="kleistad_input" id="kleistad_email" type="email" name="email" required
					value="<?php echo esc_attr( $data['input']['email'] ); ?>"/>
			</div>
		</div>
	</div>
	<div id="tab_vaste_klant" style="display:none;">
		<div class="kleistad_row">
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_klant_id">Klant naam</label>
			</div>
			<div class="kleistad_col_7">
				<select name="klant_id" id="kleistad_klant_id" required >
					<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
						<option value="<?php echo esc_attr( $gebruiker->id ); ?>" <?php selected( $gebruiker->id, $data['input']['klant_id'] ); ?> ><?php echo esc_html( $gebruiker->display_name ); ?></option>
					<?php endforeach ?>
				</select>
			</div>
		</div>
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
		<input class="kleistad_input" type="text" name="omschrijving[]" required value="<?php echo esc_attr( $data['input']['omschrijving'][ $index ] ); ?>" >
	</div>
	<div class="kleistad_col_2">
		<input class="kleistad_input" type="number" step="0.01" name="prijs[]" required value="<?php echo esc_attr( $data['input']['prijs'][ $index ] ); ?>" >
	</div>
	<div class="kleistad_col_2">
		<input class="kleistad_input" type="number" step="0.01" name="aantal[]" required value="<?php echo esc_attr( $data['input']['aantal'][ $index ] ); ?>" >
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
	<button type="submit" name="kleistad_submit_verkoop" id="kleistad_submit_verkoop" value="verzenden">Verzenden</button>
</div>
</form>
