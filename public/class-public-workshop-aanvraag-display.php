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
	protected function overzicht() {
		$this->form();
	}

	/**
	 * Maak de formulier inhoud aan
	 */
	protected function form_content() {
		?>
		<div class="kleistad-tab"><?php $this->aanvraag(); ?></div>
		<div class="kleistad-tab"><?php $this->planning(); ?></div>
		<div class="kleistad-tab"><?php $this->contactinfo()->email()->telnr(); ?></div>
		<div class="kleistad-tab"><?php $this->commentaar(); ?></div>
		<div class="kleistad-tab"><?php $this->bevestiging(); ?></div>
		<?php
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
				<input name="naam" id="kleistad_<?php echo esc_attr( sanitize_title( $activiteit['naam'] ) ); ?>" type="radio" required value="<?php echo esc_attr( $activiteit['naam'] ); ?>" <?php checked( $this->data['input']['naam'], $activiteit['naam'] ); ?> >
				<label for="kleistad_<?php echo esc_attr( sanitize_title( $activiteit['naam'] ) ); ?>" ><?php echo esc_html( ucfirst( $activiteit['naam'] ) ); ?></label>
			</div>
		</div>
		<?php endforeach; ?>
		<div class="kleistad-row" >
			<div class="kleistad-col-10">
				<label class="kleistad-label">Hoeveel deelnemers verwacht je ?</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-4 kleistad-label" >
				<input name="omvang" id="kleistad_klein" type="radio" required data-limiet="6" value="6 of minder" <?php checked( $this->data['input']['omvang'], '6 of minder' ); ?> >
				<label for="kleistad_klein" >6 of minder</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-4 kleistad-label" >
				<input name="omvang" id="kleistad_middel" type="radio" required data-limiet="12" value="tussen 7 en 12" <?php checked( $this->data['input']['omvang'], 'tussen 7 en 12' ); ?> >
				<label for="kleistad_middel" >tussen 7 en 12</label>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-1" >
			</div>
			<div class="kleistad-col-4 kleistad-label" >
				<input name="omvang" id="kleistad_groot" type="radio" required data-limiet="20" value="13 of meer" <?php checked( $this->data['input']['omvang'], 'meer dan 12' ); ?> >
				<label for="kleistad_groot" >meer dan 12</label>
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
				<input type="checkbox" id="kleistad_<?php echo esc_attr( strtolower( $techniek ) ); ?>" name="technieken[]" value="<?php echo esc_attr( $techniek ); ?>" <?php checked( in_array( $techniek, $this->data['input']['technieken'], true ) ); ?> >
				<label class="kleistad-label" for="kleistad_<?php echo esc_attr( strtolower( $techniek ) ); ?>" ><?php echo esc_html( $techniek ); ?></label>
			</span>
			</div>
			<?php endforeach; ?>
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
				<label for="kleistad_plandatum" class="kleistad-label">Wanneer moet het plaatsvinden ?</label>
			</div>
			<div class="kleistad-col-3">
				<input class="kleistad-datum" type="text" name="plandatum" id="kleistad_plandatum" required="required">
			</div>
		</div>
		<?php foreach ( Workshopplanning::WORKSHOP_DAGDEEL as $dagdeel ) : ?>
		<div class="kleistad-dagdeel-<?php echo esc_attr( strtolower( $dagdeel ) ); ?> kleistad-row">
			<div class="kleistad-col-3" >
			</div>
			<div class="kleistad-col-3 kleistad-label" >
				<input name="dagdeel" id="kleistad_<?php echo esc_attr( strtolower( $dagdeel ) ); ?>" type="radio" required
					value="<?php echo esc_attr( $dagdeel ); ?>" <?php checked( $this->data['input']['dagdeel'], $dagdeel ); ?> >
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
	 * Afsluitende vraag
	 */
	private function commentaar() {
		?>
		<div class ="kleistad-row" title="Heb je nog nadere vragen, stel ze gerust. Of laat hier opmerkingen achter die van belang zouden kunnen zijn voor Kleistad" >
			<div class="kleistad-col-3" >
				<label class="kleistad-label" for="kleistad_vraag">Heb je nog speciale wensen of wil je iets delen ?</label>
			</div>
			<div class="kleistad-col-7 kleistad-input">
				<textarea class="kleistad-input" name="vraag" id="kleistad_vraag" maxlength="1000" rows="5" cols="50"><?php echo esc_textarea( $this->data['input']['vraag'] ); ?></textarea>
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
			Het betreft de aanvraag voor een <strong><span id="bevestig_naam" style="text-transform: lowercase;" ></span></strong> voor <strong><span id="bevestig_omvang"></span></strong> deelnemers
			in de <strong><span id="bevestig_dagdeel" style="text-transform: lowercase;" ></span></strong> op <strong><span id="bevestig_plandatum"></span></strong>.
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
				<span id="bevestig_vraag"></span>
			</div>
		</div>
		<div class="kleistad-row kleistad-tab-footer">
			<div class="kleistad-col-10">
				Als het bovenstaande correct is dan kan de aanvraag verzonden worden. Er wordt binnen een week contact opgenomen.
			</div>
		</div>
		<?php
	}
}
