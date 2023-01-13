<?php
/**
 * Toon het workshop aanvraag formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het workshop aanvraag formulier.
 */
class Public_Workshop_Aanvraag_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		$this->form(
			function() {
				?>
		<div class="kleistad-tab"><?php $this->aanvraag(); ?></div>
		<div class="kleistad-tab"><?php $this->planning(); ?></div>
		<div class="kleistad-tab"><?php $this->contactinfo()->email()->telnr(); ?></div>
		<div class="kleistad-tab"><?php $this->opmerking( 'Heb je nog nadere vragen, stel ze gerust. Of laat hier opmerkingen achter die van belang zouden kunnen zijn voor Kleistad' ); ?></div>
		<div class="kleistad-tab"><?php $this->bevestiging(); ?></div>
				<?php
			}
		);
	}

	/**
	 * Render het formulier
	 */
	private function aanvraag() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-6">
				<h3>Beantwoord onderstaande vragen en druk dan op <span style="font-style: italic;">Verder</span></h3>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-5">
				<label class="kleistad-label">Wat voor activiteit wil je uitvoeren ?</label>
			</div>
		</div>
		<?php foreach ( opties()['activiteit'] as $activiteit ) : ?>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-5 kleistad-label" >
				<input class="kleistad-radio" name="naam" id="kleistad_<?php echo esc_attr( sanitize_title( $activiteit['naam'] ) ); ?>" type="radio" required value="<?php echo esc_attr( $activiteit['naam'] ); ?>" <?php checked( $this->data['input']['naam'], $activiteit['naam'] ); ?> >
				<label for="kleistad_<?php echo esc_attr( sanitize_title( $activiteit['naam'] ) ); ?>" ><?php echo esc_html( ucfirst( $activiteit['naam'] ) ); ?></label>
			</div>
		</div>
		<?php endforeach; ?>
		<div class="kleistad-row" >
			<div class="kleistad-col-3">
				<label class="kleistad-label" for="kleistad_aantal">Hoeveel deelnemers verwacht je ?</label>
			</div>
			<div class="kleistad-col-1">
				<input name="aantal" id="kleistad_aantal" type="number" required min="1" max="99" value="<?php echo esc_attr( $this->data['input']['aantal'] ); ?>">
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<label class="kleistad-label">Weet je wat al je zou willen doen?</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-1">
			</div>
			<?php foreach ( [ 'Draaien', 'Handvormen' ] as $techniek ) : ?>
			<div class="kleistad-col-3" >
			<span>
				<input type="checkbox" id="kleistad_<?php echo esc_attr( strtolower( $techniek ) ); ?>" class="kleistad-checkbox" name="technieken[]" value="<?php echo esc_attr( $techniek ); ?>" <?php checked( in_array( $techniek, $this->data['input']['technieken'], true ) ); ?> >
				<label class="kleistad-label" for="kleistad_<?php echo esc_attr( strtolower( $techniek ) ); ?>" ><?php echo esc_html( $techniek ); ?></label>
			</span>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="kleistad-row kleistad-tab-footer" >
			<div class="kleistad-col-10">
				Het definitieve aantal deelnemer graag uiterlijk 2 weken vooraf doorgeven.<br/>Vanaf 12 deelnemers kan er gekozen worden voor twee technieken.
			</div>
		</div>

		<?php
	}

	/**
	 * Render het formulier
	 *
	 * @return Public_Workshop_Aanvraag_Display
	 */
	private function contactinfo() : Public_Workshop_Aanvraag_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10 kleistad-label">
				<label>Wat zijn je contact gegevens ?</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_contact">Naam</label>
			</div>
			<div class="kleistad-col-4">
				<input class="kleistad-input" name="contact" id="kleistad_contact" type="text"
				required maxlength="25" placeholder="naam" title="Vul s.v.p. je naam in"
				value="<?php echo esc_attr( $this->data['input']['contact'] ); ?>" autocomplete="given-name" />
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het formulier
	 */
	private function planning() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<h3>Geef aan op welke dag en dagdeel je de activiteit wilt plannen en druk dan op <span style="font-style: italic;">Verder</span></h3>.
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-3">
				<label for="kleistad_datum" class="kleistad-label">Wanneer moet het plaatsvinden ?</label>
			</div>
			<div class="kleistad-col-3">
				<input class="kleistad-datum" type="text" name="datum" id="kleistad_datum" required="required" readonly="readonly" >
			</div>
		</div>
		<?php foreach ( Workshopplanning::WORKSHOP_DAGDEEL as $dagdeel ) : ?>
		<div id="kleistad-dagdeel-<?php echo esc_attr( strtolower( $dagdeel ) ); ?>" class="kleistad-row" >
			<div class="kleistad-col-3" >
			</div>
			<div class="kleistad-col-3 kleistad-label" >
				<input class="kleistad-radio" name="dagdeel" id="kleistad_<?php echo esc_attr( strtolower( $dagdeel ) ); ?>" type="radio" required
					value="<?php echo esc_attr( strtolower( $dagdeel ) ); ?>" <?php checked( $this->data['input']['dagdeel'], $dagdeel ); ?> >
				<label for="kleistad_<?php echo esc_attr( strtolower( $dagdeel ) ); ?>" ><?php echo esc_html( $dagdeel ); ?></label>
			</div>
		</div>
		<?php endforeach; ?>
		<div class="kleistad-row kleistad-tab-footer" >
			<div class="kleistad-col-10">
				Je kan alleen een datum selecteren die past binnen de huidige planning van Kleistad van de komende 3 maanden.<br/>Wil je later een workshop plannen neem dan <?php $this->contact(); ?> op.
			</div>
		</div>
		<?php
	}

	/**
	 * Bevestig ingevoerde gegevens
	 */
	private function bevestiging() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<label class="kleistad-label">Overzicht ingevoerde gegevens</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
			Het betreft de aanvraag voor een <strong><span id="bevestig_naam" style="text-transform: lowercase;" ></span></strong> voor <strong><span id="bevestig_aantal"></span></strong> deelnemers
			in de <strong><span id="bevestig_dagdeel" style="text-transform: lowercase;" ></span></strong> op <strong><span id="bevestig_datum"></span></strong>.
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				Aangevraagd door <strong><span id="bevestig_contact"></span></strong>, met emailadres <strong><span id="bevestig_user_email"></span></strong> en telefoonnummer <strong><span id="bevestig_telnr"></span></strong>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				Speciale wensen en/of mededeling :
			</div>
			<div class="kleistad-col-7">
				<span id="bevestig_opmerking"></span>
			</div>
		</div>
		<div class="kleistad-row kleistad-tab-footer">
			<div class="kleistad-col-10">
				Als het bovenstaande correct is dan kan de aanvraag verzonden worden. Er wordt binnen een week contact opgenomen. De optie voor deze datum heeft een duur van 1 week. We proberen in die tijd de optie op deze datum om te zetten in een reservering. Reserveringen kunnen tot 2 weken voor de datum worden aangepast
			</div>
		</div>
		<?php
	}
}
