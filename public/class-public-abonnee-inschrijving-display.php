<?php
/**
 * Toon het abonnee inschrijving formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

namespace Kleistad;

/**
 * Render van de abonnee inschrijving formulier.
 */
class Public_Abonnee_Inschrijving_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function html() {
		$this->form()->abonnement_info();
		if ( is_super_admin() ) {
			$this->gebruiker_selectie( 'Abonnee' );
		} elseif ( is_user_logged_in() ) {
			$this->gebruiker_logged_in()->opmerking()->verklaring()->nieuwsbrief();
		} else {
			$this->gebruiker()->opmerking()->verklaring()->nieuwsbrief();
		}
		$this->betaal_info()->form_end();
	}

	/**
	 * Render het abonnement velden
	 */
	private function abonnement_info() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label">Keuze abonnement</label>
			</div>
			<div class="kleistad-col-3">
				<input name="abonnement_keuze" id="kleistad_onbeperkt" type="radio" checked required
					data-bedrag="<?php echo esc_attr( 3 * opties()['onbeperkt_abonnement'] ); ?>"
					data-bedragtekst="= 3 termijnen"
					value="onbeperkt" <?php checked( 'onbeperkt', $this->data['input']['abonnement_keuze'] ); ?> />
				<label for="kleistad_onbeperkt" >
					Onbeperkt<br/>(€ <?php echo esc_html( number_format_i18n( opties()['onbeperkt_abonnement'], 2 ) ); ?> p.m.)
				</label>
			</div>
			<div class="kleistad-col-1">
			</div>
			<div class="kleistad-col-3">
				<input name="abonnement_keuze" id="kleistad_beperkt" type="radio" required
					data-bedrag="<?php echo esc_attr( 3 * opties()['beperkt_abonnement'] ); ?>"
					data-bedragtekst="= 3 termijnen"
					value="beperkt" <?php checked( 'beperkt', $this->data['input']['abonnement_keuze'] ); ?> />
				<label for="kleistad_beperkt">
					Beperkt<br/>(€ <?php echo esc_html( number_format_i18n( opties()['beperkt_abonnement'], 2 ) ); ?> p.m.)
				</label>
			</div>
		</div>
		<div class="kleistad-row" id="kleistad_dag" style="visibility:hidden" title="kies de dag dat je van jouw beperkt abonnement gebruikt gaat maken" >
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_dag_keuze">Dag</label>
			</div>
			<div class="kleistad-col-7">
				<select class="kleistad-input" name="dag" id="kleistad_dag_keuze" >
					<option value="maandag" <?php selected( $this->data['input']['dag'], 'maandag' ); ?> >Maandag</option>
					<option value="dinsdag" <?php selected( $this->data['input']['dag'], 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $this->data['input']['dag'], 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $this->data['input']['dag'], 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $this->data['input']['dag'], 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_start_datum">Start per</label>
			</div>
			<div class="kleistad-col-3 kleistad-input">
				<input class="kleistad-datum kleistad-input" name="start_datum" id="kleistad_start_datum" type="text" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
			</div>
		</div>
		<?php
	}

	/**
	 * Render de betaal sectie
	 *
	 * @return Public_Abonnee_Inschrijving_Display
	 */
	private function betaal_info() : Public_Abonnee_Inschrijving_Display {
		?>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" <?php checked( $this->data['input']['betaal'], 'ideal' ); ?> />
				<label for="kleistad_betaal_ideal"></label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<?php $this->ideal(); ?>
			</div>
		</div>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input type="radio" name="betaal" id="kleistad_betaal_stort" required value="stort" <?php checked( $this->data['input']['betaal'], 'stort' ); ?> />
				<label for="kleistad_betaal_stort"></label>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de afronding van het formulier
	 *
	 * @return Public_Abonnee_Inschrijving_Display
	 */
	protected function form_end() : Public_Abonnee_Inschrijving_Display {
		?>
		<div class="kleistad-row" style="padding-top: 20px;">
			<div class="kleistad-col-10">
				<button class="kleistad-button" name="kleistad_submit_abonnee_inschrijving" id="kleistad_submit" type="submit" <?php disabled( ! is_super_admin() && '' !== $this->data['verklaring'] ); ?>>Betalen</button>
			</div>
		</div>
		</form>
		<?php
		return $this;
	}
}
