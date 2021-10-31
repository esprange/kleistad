<?php
/**
 * Toon het cursus inschrijving formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de cursus inschrijving formulier.
 */
class Public_Cursus_Inschrijving_Display extends Public_Shortcode_Display {

	/**
	 * De tekst op de submit button
	 *
	 * @var string $buttontekst De tekst.
	 */
	private string $buttontekst;

	/**
	 * Render het formulier
	 *
	 * @return void
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function html() {
		$this->form();
		if ( 'indelen_na_wachten' === $this->data['actie'] ) {
			$this->buttontekst = 'Betalen';
			$this->indelen_na_wachten()->form_end();
			return;
		}
		if ( 'stop_wachten' === $this->data['actie'] ) {
			$this->buttontekst = 'Afmelden';
			$this->stop_wachten()->form_end();
			return;
		}
		if ( 'inschrijven' === $this->data['actie'] ) {
			$this->cursus_info()->techniek_keuze();
			if ( is_super_admin() ) {
				$this->aantal( 1 )->gebruiker_selectie( 'Cursist' );
			} elseif ( is_user_logged_in() ) {
				$this->aantal( 1 )->gebruiker_logged_in()->opmerking()->nieuwsbrief();
			} else {
				$this->aantal()->gebruiker()->opmerking()->nieuwsbrief();
			}
			$this->buttontekst = 'Inschrijven';
			$this->betaal_info()->form_end();
		}
	}

	/**
	 * Render het formulier voor inschrijving na op de wachtlijst te hebben gestaan
	 *
	 * @return Public_Cursus_Inschrijving_Display
	 */
	private function indelen_na_wachten() : Public_Cursus_Inschrijving_Display {
		?>
		<h2><?php echo esc_html( $this->data['cursist_naam'] ); ?></h2>
		<strong>Aanmelding voor cursus <?php echo esc_html( $this->data['cursus_naam'] ); ?></strong>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $this->data['cursus_id'] ); ?>" />
		<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( $this->data['gebruiker_id'] ); ?>" />
		<input type="hidden" name="aantal" value="1" />
		<p>Door de betaling te doen voor deze cursus wordt je meteen ingedeeld</p>
		<div class ="kleistad-row">
			<div class ="kleistad-row">
				<div class="kleistad-col-10">
					<?php $this->ideal(); ?>
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het stop wachten formulier
	 *
	 * @return Public_Cursus_Inschrijving_Display
	 */
	private function stop_wachten() : Public_Cursus_Inschrijving_Display {
		?>
		<h2><?php echo esc_html( $this->data['cursist_naam'] ); ?></h2>
		<strong>Afmelden voor de wachtlijst van cursus <?php echo esc_html( $this->data['cursus_naam'] ); ?></strong>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $this->data['cursus_id'] ); ?>" />
		<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( $this->data['gebruiker_id'] ); ?>" />
		<input type="hidden" name="aantal" value="1" />
		<p>Door af te melden zal je geen email ontvangen als er een plaats vrijkomt voor deze cursus</p>
		<?php
		return $this;
	}

	/**
	 * Render het cursus velden
	 *
	 * @return Public_Cursus_Inschrijving_Display
	 */
	private function cursus_info() : Public_Cursus_Inschrijving_Display {
		?>
		<div id="kleistad_cursussen" >
		<?php
		foreach ( $this->data['open_cursussen'] as $cursus_data ) {
			$tooltip       = 0 < $cursus_data['cursus']->inschrijfkosten ?
				sprintf( 'cursus %s start per %s|%d lessen', $cursus_data['cursus']->naam, strftime( '%x', $cursus_data['cursus']->start_datum ), count( $cursus_data['cursus']->lesdatums ) ) :
				sprintf( 'workshop op %s', strftime( '%x', $cursus_data['cursus']->start_datum ) );
			$tooltip      .=
				sprintf( '|docent is %s|kosten &euro;%01.2f p.p.', $cursus_data['cursus']->docent_naam(), $cursus_data['cursus']->inschrijfkosten + $cursus_data['cursus']->cursuskosten );
			$selecteerbaar = $cursus_data['is_open'] && $cursus_data['json'];
			$style         = $selecteerbaar ? '' : 'color: gray;';
			$ruimte_tekst  = ", nog ruimte voor {$cursus_data['ruimte']} deelnemer" . ( $cursus_data['ruimte'] > 1 ? 's' : '' );
			$naam          = $cursus_data['cursus']->naam . ( $cursus_data['cursus']->vervallen ? ' VERVALLEN' : ( $cursus_data['cursus']->vol ? ' VOL' : $ruimte_tekst ) );
			?>
			<div class="kleistad-row" style="overflow-x:auto;white-space:nowrap;">
				<input name="cursus_id" id="kleistad_cursus_<?php echo esc_attr( $cursus_data['cursus']->id ); ?>" type="radio" value="<?php echo esc_attr( $cursus_data['cursus']->id ); ?>"
					data-cursus='<?php echo $cursus_data['json'] ?: ''; // phpcs:ignore ?>' <?php disabled( ! $selecteerbaar ); ?> <?php checked( $this->data['input']['cursus_id'], $cursus_data['cursus']->id ); ?> required />
				<label title="<?php echo $tooltip; // phpcs:ignore ?>" for="kleistad_cursus_<?php echo esc_attr( $cursus_data['cursus']->id ); ?>">
					<span style="<?php echo esc_attr( $style ); ?>"><?php echo esc_html( $naam ); ?></span></label>
			</div>
			<?php
		}
		?>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de techniek keuze. Javascript bepaalt of dit wordt getoond.
	 */
	private function techniek_keuze() {
		?>
		<div id="kleistad_cursus_technieken" style="visibility: hidden;padding-bottom: 20px;" >
			<div class="kleistad-row" >
				<div class="kleistad-col-10">
					<label class="kleistad-label">kies de techniek(en) die je wilt oefenen</label>
				</div>
			</div>3
			<div class="kleistad-row" >
				<div class="kleistad-col-1" >
				</div>
				<div class="kleistad-col-3 kleistad-label" id="kleistad_cursus_draaien" style="display: none" >
					<input name="technieken[]" id="kleistad_draaien" type="checkbox" value="Draaien" <?php checked( in_array( 'Draaien', $this->data['input']['technieken'], true ) ); ?> >
					<label for="kleistad_draaien" >Draaien</label>
				</div>
				<div class="kleistad-col-3 kleistad-label" id="kleistad_cursus_handvormen" style="display: none" >
					<input name="technieken[]" id="kleistad_handvormen" type="checkbox" value="Handvormen" <?php checked( in_array( 'Handvormen', $this->data['input']['technieken'], true ) ); ?> >
					<label for="kleistad_handvormen" >Handvormen</label>
				</div>
				<div class="kleistad-col-3 kleistad-label" id="kleistad_cursus_boetseren" style="display: none" >
					<input name="technieken[]" id="kleistad_boetseren" type="checkbox" value="Boetseren" <?php checked( in_array( 'Boetseren', $this->data['input']['technieken'], true ) ); ?> >
					<label for="kleistad_boetseren" >Boetseren</label>
				</div>
				<div class="kleistad-row" >
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render de cursist_info
	 *
	 * @param int|null $aantal Het aantal, als null dan tonen we het.
	 * @return Public_Cursus_Inschrijving_Display
	 */
	private function aantal( ?int $aantal = null ) : Public_Cursus_Inschrijving_Display {
		if ( 0 < $aantal ) {
			?>
			<input type="hidden" name="aantal" id="kleistad_aantal" value="1" />
			<?php
			return $this;
		}
		?>
		<div id="kleistad_cursus_aantal" style="visibility: hidden" >
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_aantal">Ik kom met </label>
				</div>
				<div class="kleistad-col-2">
					<input class="kleistad-input" type="number" name="aantal" id="kleistad_aantal" min="1" value="<?php echo esc_attr( $this->data['input']['aantal'] ); ?>" />
				</div>
				<div class="kleistad-col-2 kleistad-label">
					<label>deelnemers</label>
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de betaal sectie
	 *
	 * @return Public_Cursus_Inschrijving_Display
	 */
	private function betaal_info() : Public_Cursus_Inschrijving_Display {
		?>
		<div id="kleistad_cursus_betalen" style="display:none;">
			<div class="kleistad-row">
				<input type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" <?php checked( $this->data['input']['betaal'], 'ideal' ); ?> />
				<label for="kleistad_betaal_ideal" ></label>
			</div>
			<div class="kleistad-row">
				<?php $this->ideal(); ?>
			</div>
			<div class ="kleistad-row">
				<input type="radio" name="betaal" id="kleistad_betaal_stort" required value="stort" <?php checked( $this->data['input']['betaal'], 'stort' ); ?> />
				<label for="kleistad_betaal_stort" ></label>
			</div>
		</div>
		<div id="kleistad_cursus_lopend" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-10">
					<label class="kleistad-label">
					Deze cursus is reeds gestart. Bij inschrijving op deze cursus zal contact met je worden opgenomen en krijg je nadere instructie over de betaling.
					</label>
				</div>
			</div>
		</div>
		<div id="kleistad_cursus_vol" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-10">
					<label class="kleistad-label">
					Deze cursus is vol. Bij inschrijving op deze cursus kom je op een wachtlijst en zal contact met je worden opgenomen als er een plek vrijkomt.
					</label>
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de afronding van het formulier
	 *
	 * @return Public_Cursus_Inschrijving_Display
	 */
	protected function form_end() : Public_Cursus_Inschrijving_Display {
		?>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-10">
				<button class="kleistad-button" name="kleistad_submit_cursus_inschrijving" id="kleistad_submit" value="<?php echo esc_attr( $this->data['actie'] ); ?>" type="submit" ><?php echo esc_html( $this->buttontekst ); ?></button>
			</div>
		</div>
		</form>
		<?php
		return $this;
	}
}
