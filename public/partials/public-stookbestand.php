<?php
/**
 * Toon het stookbestand formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

?>

<div class="kleistad_row">
	<div class="kleistad_col_3">
		<label class="kleistad_label" for="kleistad_vanaf_datum" >Vanaf</label>
	</div>
	<div class="kleistad_col_7">
		<input type="text" name="vanaf_datum" id="kleistad_vanaf_datum" class="kleistad_datum" value="<?php echo esc_attr( date( '01-01-Y' ) ); ?>"  readonly="readonly" />
	</div>
</div>
<div class="kleistad_row" >
	<div class="kleistad_col_3">
		<label class="kleistad_label" for="kleistad_tot_datum" >Tot</label>
	</div>
	<div class="kleistad_col_7">
		<input type="text" name="tot_datum" id="kleistad_tot_datum" class="kleistad_datum" value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
	</div>
</div>
<div class="kleistad_row" style="padding-top:20px;" >
	<button type="button" class="kleistad_download_link" data-actie="stook" >Download stookbestand</button>
</div>
