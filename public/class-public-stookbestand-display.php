<?php
/**
 * Toon het stookbestand formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het stookbestand formulier.
 */
class Public_Stookbestand_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() {
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
			<button class="kleistad-button kleistad-download-link" type="button" data-actie="stook" >Download stookbestand</button>
		</div>
		<?php
	}

}
