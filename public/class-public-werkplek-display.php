<?php
/**
 * Toon het werkplek formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het werkplek formulier.
 */
class Public_Werkplek_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		$this->werkplek();
	}

	/**
	/**
	 * Render het werkplek formulier
	 *
	 * @return Public_Werkplek_Display
	 */
	private function werkplek() {
		$huidige_gebruiker = wp_get_current_user();
		?>
		<div id="kleistad_geen_ie" style="display:none">
			<strong>Helaas wordt Internet Explorer niet meer ondersteund voor deze functionaliteit, gebruik bijvoorbeeld Chrome of Edge</strong>
		</div>

		<div id="kleistad_meester">
			<?php if ( current_user_can( BESTUUR ) ) : ?>
			<select id="kleistad_meester_selectie" >
				<option value="0" >...</option>
				<?php foreach ( $this->data['meesters'] as $meester ) : ?>
				<option value="<?php echo esc_attr( $meester->ID ); ?>" ><?php echo esc_html( $meester->display_name ); ?></option>
				<?php endforeach ?>
			</select>
		<?php endif ?>
		</div>

		<div id="kleistad_gebruiker" title="Reserveer een werkplek voor ...">
			<?php if ( current_user_can( BESTUUR ) || current_user_can( DOCENT ) ) : ?>
			<select id="kleistad_gebruiker_selectie" >
				<option value="<?php echo esc_attr( $huidige_gebruiker->ID ); ?>" selected ><?php echo esc_html( $huidige_gebruiker->display_name ); ?></option>
					<?php foreach ( $this->data['cursisten'] as $cursist ) : ?>
				<option value="<?php echo esc_attr( $cursist['id'] ); ?>" ><?php echo esc_html( $cursist['naam'] ); ?></option>
					<?php endforeach ?>
			</select>
			<?php endif ?>
		</div>

		<h2 id="kleistad_datum_titel"></h2>
		<div class="kleistad-row">
			<div style="float:left;margin-bottom:10px">
				<input type="hidden" name="datum" id="kleistad_datum" class="kleistad-datum" >
				<button class="kleistad-button" type="button" id="kleistad_eerder" style="width:3em" ><span class="dashicons dashicons-controls-back"></span></button>
				<button class="kleistad-button" type="button" id="kleistad_kalender"  style="width:3em" ><span class="dashicons dashicons-calendar"></span></button>
				<button class="kleistad-button" type="button" id="kleistad_later" style="width:3em" ><span class="dashicons dashicons-controls-forward"></span></button>
			</div>
			<?php if ( current_user_can( BESTUUR ) || current_user_can( DOCENT ) ) : ?>
			<div style="float:right;" >
				<button class="kleistad-button" id="kleistad_wijzig_gebruiker" ><?php echo esc_html( $huidige_gebruiker->display_name ); ?></button>
			</div>
			<?php endif ?>
		</div>
		<div id="kleistad_werkplek"
			data-datums='<?php echo esc_attr( wp_json_encode( $this->data['datums'] ) ?: '[]' ); ?>'
			data-id="<?php echo esc_attr( $huidige_gebruiker->ID ); ?>" >
		</div>
		<?php
		return $this;
	}

}

