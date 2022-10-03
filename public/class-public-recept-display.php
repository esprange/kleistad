<?php
/**
 * Toon het recept formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de email formulier.
 */
class Public_Recept_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		?>
		<div class="kleistad-row" style="padding-bottom:15px;">
			<div class="kleistad-col-2">
				<label for="kleistad_zoek" >Zoek een recept</label>
			</div>
			<div class="kleistad-col-4" style="position: relative;">
				<input type="search" id="kleistad_zoek" style="height:40px;" placeholder="zoeken..." value="" >
				<button class="kleistad-button" type="button" id="kleistad_zoek_icon" style="height:40px;position:absolute;right:0;z-index:2;"><span class="dashicons dashicons-search"></span></button>
			</div>
			<div class="kleistad-col-2" style="text-align:right;">
				<label for="kleistad_sorteer" >Sorteer op</label>
			</div>
			<div class="kleistad-col-2">
				<select id="kleistad_sorteer" >
					<option value="titel">Titel</option>
					<?php if ( function_exists( 'the_ratings' ) ) : ?>
					<option value="waardering">Waardering</option>
					<?php endif ?>
					<option value="nieuwste" selected>Nieuwste</option>
				</select>
			</div>
		</div>
		<div class="kleistad-row">
			<button class="kleistad-button" type="button" id="kleistad_filter_btn"></button>
		</div>
		<div class="kleistad_recepten" id="kleistad_recepten">
			de recepten worden opgehaald...
		</div>
		<?php
	}

}
