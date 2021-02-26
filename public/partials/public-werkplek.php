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

$huidige_gebruiker = wp_get_current_user();

?>
<div id="kleistad_geen_ie" style="display:none">
	<strong>Helaas wordt Internet Explorer niet meer ondersteund voor deze functionaliteit, gebruik bijvoorbeeld Chrome of Edge</strong>
</div>

<div id="kleistad_meester">
	<?php if ( current_user_can( BESTUUR ) ) : ?>
	<select id="kleistad_meester_selectie" >
		<option value="0" >...</option>
		<?php foreach ( $data['meesters'] as $meester ) : ?>
		<option value="<?php echo esc_attr( $meester->ID ); ?>" ><?php echo esc_html( $meester->display_name ); ?></option>
		<?php endforeach ?>
	</select>
<?php endif ?>
</div>

<div id="kleistad_gebruiker" title="Reserveer een werkplek voor ...">
	<?php if ( current_user_can( BESTUUR ) || current_user_can( DOCENT ) ) : ?>
	<select id="kleistad_gebruiker_selectie" >
		<option value="<?php echo esc_attr( $huidige_gebruiker->ID ); ?>" selected ><?php echo esc_html( $huidige_gebruiker->display_name ); ?></option>
			<?php foreach ( $data['cursisten'] as $cursist ) : ?>
		<option value="<?php echo esc_attr( $cursist['id'] ); ?>" ><?php echo esc_html( $cursist['naam'] ); ?></option>
			<?php endforeach ?>
	</select>
	<?php endif ?>
</div>

<h2 id="kleistad_datum_titel"></h2>
<div class="kleistad-row">
	<div style="float:left;margin-bottom:10px">
		<input type="hidden" name="datum" id="kleistad_datum" class="kleistad-datum" readonly="readonly" >
		<button type="button" id="kleistad_eerder" style="width:3em" ><span class="dashicons dashicons-controls-back"></span></button>
		<button type="button" id="kleistad_kalender"  style="width:3em" ><span class="dashicons dashicons-calendar"></span></button>
		<button type="button" id="kleistad_later" style="width:3em" ><span class="dashicons dashicons-controls-forward"></span></button>
	</div>
	<?php if ( current_user_can( BESTUUR ) || current_user_can( DOCENT ) ) : ?>
	<div style="float:right;" >
		<button id="kleistad_wijzig_gebruiker" ><?php echo esc_html( $huidige_gebruiker->display_name ); ?></button>
	</div>
	<?php endif ?>
</div>
<div id="kleistad_werkplek"
	data-datums='<?php echo esc_attr( wp_json_encode( $data['datums'] ) ?: '[]' ); ?>'
	data-id="<?php echo esc_attr( $huidige_gebruiker->ID ); ?>" >
</div>
