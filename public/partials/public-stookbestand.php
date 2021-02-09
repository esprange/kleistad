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

namespace Kleistad;

?>

<div class="kleistad-row">
	<div class="kleistad-col-3">
		<label class="kleistad-label" for="kleistad_vanaf_datum" >Vanaf</label>
	</div>
	<div class="kleistad-col-7">
		<input type="text" name="vanaf_datum" id="kleistad_vanaf_datum" class="kleistad-datum" value="<?php echo esc_attr( date( '01-01-Y' ) ); ?>"  readonly="readonly" />
	</div>
</div>
<div class="kleistad-row" >
	<div class="kleistad-col-3">
		<label class="kleistad-label" for="kleistad_tot_datum" >Tot</label>
	</div>
	<div class="kleistad-col-7">
		<input type="text" name="tot_datum" id="kleistad_tot_datum" class="kleistad-datum" value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
	</div>
</div>
<div class="kleistad-row" style="padding-top:20px;" >
	<button type="button" class="kleistad-download-link" data-actie="stook" >Download stookbestand</button>
</div>
