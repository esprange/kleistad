<?php
/**
 * Toon het (dynamische) werkplek reservering formulier
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

namespace Kleistad;

?>
<div id="kleistad_geen_ie" style="display:none">
	<strong>Helaas wordt Internet Explorer niet meer ondersteund voor deze functionaliteit, gebruik bijvoorbeeld Chrome of Edge</strong>
</div>
<h2 id="kleistad_datum_titel"></h2>
<div class="kleistad_row">
	<div style="float:left;margin-bottom:10px">
		<input type=text name="datum" id="kleistad_datum" class="kleistad_datum" readonly="readonly" >
		<button type="button" id="kleistad_eerder" ><span class="dashicons dashicons-controls-back"></span></button>
		<button type="button" id="kleistad_later" ><span class="dashicons dashicons-controls-forward"></span></button>
	</div>
</div>
<div class="kleistad_row">
	<div id="kleistad_werkplek" data-datums='<?php echo esc_attr( wp_json_encode( $data['datums'] ) ?: '[]' ); ?>' >
	</div>
</div>
