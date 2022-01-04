<?php
/**
 * Toon het docent formulier
 *
 * @link       https://www.kleistad.nl
 * @since      7.0.0
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de reservering formulier.
 */
class Public_Docent_Display extends Public_Shortcode_Display {

	/**
	 * Het overzicht van de beschikbaarheid docenten
	 */
	protected function overzicht() {
		$this->tabelframe( 'kleistad_overzicht' );
	}

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function planning() {
		$this->tabelframe( 'kleistad_planning' );
		?>
		<div style="float: left">
			<button type="button" id="kleistad_default" class="kleistad-button">Standaard planning opslaan</button>
		</div>
		<div style="float: right">
			<button type="button" id="kleistad_bewaren" class="kleistad-button">Bewaren</button>
		</div>
		<?php
	}

	/**
	 * Render het frame
	 *
	 * @param string $tabel Het type tabel.
	 * @return void
	 */
	private function tabelframe( string $tabel ) {
		$maandag = date( 'd-m-Y', strtotime( 'Monday this week' ) );
		?>
		<div id="kleistad_geen_ie" style="display:none">
			<strong>Helaas wordt Internet Explorer niet meer ondersteund voor deze functionaliteit, gebruik bijvoorbeeld Chrome of Edge</strong>
		</div>
		<div class="kleistad-row" style="float:left;margin-bottom:10px">
			<input type="hidden" id="kleistad_plandatum" class="kleistad-datum" value="<?php echo esc_attr( $maandag ); ?>" >
			<button class="kleistad-button" type="button" id="kleistad_eerder" style="width:3em" ><span class="dashicons dashicons-controls-back"></span></button>
			<button class="kleistad-button" type="button" id="kleistad_kalender"  style="width:3em" ><span class="dashicons dashicons-calendar"></span></button>
			<button class="kleistad-button" type="button" id="kleistad_later" style="width:3em" ><span class="dashicons dashicons-controls-forward"></span></button>
		</div>
		<table id="<?php echo esc_attr( $tabel ); ?>" class="<?php echo esc_attr( str_replace( '_', '-', $tabel ) ); ?>" >
			<tr><th>Gegevens worden geladen...<th></tr>
		</table>
		<?php
	}


}
