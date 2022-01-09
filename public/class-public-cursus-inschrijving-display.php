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
	 * Render het formulier
	 *
	 * @return void
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function inschrijven() {
		$this->form();
	}

	/**
	 * Maak de formulier inhoud
	 */
	protected function form_content() {
		?>
		<input type="hidden" id="kleistad_submit_value" value="<?php echo esc_attr( $this->display_actie ); ?>" >
		<input name="cursus_naam" type="hidden" id="kleistad_cursus_naam" value="<?php echo esc_attr( $this->data['open_cursussen'][0]['cursus']->naam ); ?>">
		<input name="cursus_technieklijst" type="hidden" id="kleistad_cursus_technieklijst" value="">
		<?php if ( isset( $this->data['verbergen'] ) ) : ?>
		<input name="cursus_id" type="hidden" value="<?php echo esc_attr( $this->data['open_cursussen'][0]['cursus']->id ); ?>"
			data-cursus='<?php echo $this->data['open_cursussen'][0]['json'] ?: ''; // phpcs:ignore ?>' />
		<?php else : ?>
		<div class="kleistad-tab"><?php $this->cursus_info(); ?></div>
		<?php endif ?>
		<div class="kleistad-tab"><?php $this->aantal( is_user_logged_in() ? 1 : 0 )->techniek_keuze(); ?></div>
		<?php if ( is_super_admin() ) : ?>
		<div class="kleistad-tab"><?php $this->gebruiker_selectie( 'Cursist' ); ?></div>
		<?php elseif ( is_user_logged_in() ) : ?>
		<div class="kleistad-tab"><?php $this->gebruiker_logged_in(); ?></div>
		<?php else : ?>
		<div class="kleistad-tab"><?php $this->gebruiker(); ?></div>
		<?php endif ?>
		<div class="kleistad-tab"><?php $this->opmerking()->nieuwsbrief(); ?></div>
		<?php if ( ! is_super_admin() ) : ?>
		<div class="kleistad-tab"><?php $this->bevestiging(); ?></div>
		<?php endif ?>
		<div class="kleistad-tab"><?php $this->betaal_info(); ?></div>
		<?php
	}

	/**
	 * Render het formulier voor inschrijving na op de wachtlijst te hebben gestaan
	 */
	protected function indelen_na_wachten() {
		$this->form( 'form_indelen_na_wachten' );
	}

	/**
	 * Maak het indelen na wachten formulier aan
	 */
	protected function form_indelen_na_wachten() {
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
		$this->submit( 'Betalen' );
	}

	/**
	 * Render het stop wachten formulier
	 */
	protected function stop_wachten() {
		$this->form( 'form_stop_wachten' );
	}

	/**
	 * Maak het stop wachten formulier aan.
	 */
	protected function form_stop_wachten() {
		?>
		<h2><?php echo esc_html( $this->data['cursist_naam'] ); ?></h2>
		<strong>Afmelden voor de wachtlijst van cursus <?php echo esc_html( $this->data['cursus_naam'] ); ?></strong>
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $this->data['cursus_id'] ); ?>" />
		<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( $this->data['gebruiker_id'] ); ?>" />
		<input type="hidden" name="aantal" value="1" />
		<p>Door af te melden zal je geen email ontvangen als er een plaats vrijkomt voor deze cursus</p>
		<?php
		$this->submit( 'Afmelden' );
	}

	/**
	 * Render het cursus velden
	 */
	private function cursus_info() {
		?>
		<div id="kleistad_cursussen">
			<div class="kleistad-row">
				<div class="kleistad-col-10">
					<label class="kleistad-label">Kies de cursus waarvoor je je wilt inschrijven</label>
				</div>
			</div>
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
			$checked       = $selecteerbaar && ( $this->data['input']['cursus_id'] === $cursus_data['cursus']->id || 1 === count( $this->data['open_cursussen'] ) );
			?>
			<div class="kleistad-row" style="overflow-x:auto;white-space:nowrap;">
				<div class="kleistad-col-10">
					<input name="cursus_id" id="kleistad_cursus_<?php echo esc_attr( $cursus_data['cursus']->id ); ?>" type="radio" value="<?php echo esc_attr( $cursus_data['cursus']->id ); ?>"
						data-cursus='<?php echo $cursus_data['json'] ?: ''; // phpcs:ignore ?>' <?php disabled( ! $selecteerbaar ); ?>
						<?php checked( $checked ); ?> required />
					<label title="<?php echo $tooltip; // phpcs:ignore ?>" for="kleistad_cursus_<?php echo esc_attr( $cursus_data['cursus']->id ); ?>">
						<span style="<?php echo esc_attr( $style ); ?>"><?php echo esc_html( $naam ); ?></span></label>
				</div>
			</div>
			<?php
		}
		?>
		</div>
		<?php
	}

	/**
	 * Render de techniek keuze. Javascript bepaalt of dit wordt getoond.
	 */
	private function techniek_keuze() {
		?>
		<div id="kleistad_cursus_technieken" style="display:none;" >
			<div class="kleistad-row" >
				<div class="kleistad-col-10">
					<label class="kleistad-label">Kies de techniek(en) die je wilt oefenen</label>
				</div>
			</div>
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
	 * Render het aantal
	 *
	 * @param int|null $aantal Het aantal, als null dan tonen we het.
	 * @return Public_Cursus_Inschrijving_Display
	 */
	private function aantal( ?int $aantal = null ) : Public_Cursus_Inschrijving_Display {
		if ( 0 < $aantal ) {
			?>
			<div style="display: none">
				<input type="hidden" name="aantal" id="kleistad_aantal" value="1" />
			</div>
			<?php
			return $this;
		}
		?>
		<div id="kleistad_cursus_aantal" style="display:none" >
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
	 */
	private function betaal_info() {
		?>
		<div id="kleistad_cursus_betalen" style="display:none;">
			<div class="kleistad-row">
				<div class="kleistad-col-10">
					<label class="kleistad-label">Bepaal de wijze van betalen. Pas na ontvangst van de betaling kan je worden ingedeeld !</label>
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-10">
					<input type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" <?php checked( $this->data['input']['betaal'], 'ideal' ); ?> />
					<label for="kleistad_betaal_ideal" ></label>
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
					<label for="kleistad_betaal_stort" ></label>
				</div>
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
	}

	/**
	 * Render de bevestiging sectie
	 */
	private function bevestiging() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<label class="kleistad-label">Overzicht ingevoerde gegevens</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				Het betreft de inschrijving voor de cursus <strong><span id="bevestig_cursus_naam" style="text-transform: lowercase;" ></span></strong> voor <strong><span id="bevestig_aantal"></span></strong> deelnemer(s) <strong><span id="bevestig_cursus_technieklijst"></span></strong>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">Cursist gegevens:</div>
			<div class="kleistad-col-7">
			<strong><span id="bevestig_first_name"></span> <span id="bevestig_last_name"></span><br/>
				<span id="bevestig_straat"></span> <span id="bevestig_huisnr"></span><br/>
				<span id="bevestig_pcode"></span> <span id="bevestig_plaats"></span><br/>
				<span id="bevestig_telnr"></span><br/>
				<span id="bevestig_user_email"></span>
			</strong>
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
		<div class="kleistad-row kleistad-tab-footer" >
			<div class="kleistad-col-10">
				Als het bovenstaande correct is, druk dan op verder.
			</div>
		</div>
		<?php
	}

	/**
	 * Render de afronding van het formulier
	 *
	 * @param string $buttontekst De tekst die op de submit button moet worden getoond.
	 */
	private function submit( string $buttontekst ) {
		?>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-10">
				<button class="kleistad-button" name="kleistad_submit_cursus_inschrijving" id="kleistad_submit" value="<?php echo esc_attr( $this->display_actie ); ?>" type="submit" ><?php echo esc_html( $buttontekst ); ?></button>
			</div>
		</div>
		<?php
	}
}
