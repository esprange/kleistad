<?php
/**
 * Toon het dagdelenkaart inschrijving formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de dagdelenkaart inschrijving formulier.
 */
class Public_Dagdelenkaart_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		$this->form(
			function() {
				?>
		<div class="kleistad-tab"><?php $this->dagdelenkaart_info(); ?></div>

				<?php if ( is_super_admin() ) : ?>
			<div class="kleistad-tab"><?php $this->gebruiker_selectie( 'Abonnee' ); ?></div>
		<?php elseif ( is_user_logged_in() ) : ?>
			<div class="kleistad-tab"><?php	$this->gebruiker_logged_in(); ?></div>
		<?php else : ?>
			<div class="kleistad-tab"><?php $this->gebruiker(); ?></div>
		<?php endif ?>

		<div class="kleistad-tab"><?php $this->opmerking()->nieuwsbrief(); ?></div>
		<div class="kleistad-tab"><?php $this->bevestiging(); ?></div>
		<div class="kleistad-tab"><?php $this->betaal_info(); ?></div>
				<?php
			}
		);
	}

	/**
	 * Render de dagdelenkaart info
	 *
	 * @return void
	 */
	private function dagdelenkaart_info() : void {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-6">
				<h3>Beantwoord onderstaande vraag en druk dan op <span style="font-style: italic;">Verder</span></h3>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_start_datum">Start per</label>
			</div>
			<div class="kleistad-col-3 kleistad-input">
				<input type="hidden" id="kleistad_kosten_kaart" value="<?php echo esc_attr( opties()['dagdelenkaart'] ); ?>" >
				<input class="kleistad-datum kleistad-input" name="start_datum" id="kleistad_start_datum" type="text" required value="<?php echo esc_attr( wp_date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
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
				Het betreft een dagdelenkaart met een geldigheidsduur van <?php echo esc_attr( Dagdelenkaart::KAART_DUUR ); ?> maanden welke start per <strong><span id="bevestig_start_datum"></span></strong>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				Kaartgebruiker gegevens:
			</div>
			<div class="kleistad-col-7">
				<strong><span id="bevestig_first_name"></span> <span id="bevestig_last_name"></span><br/>
					<span id="bevestig_straat"></span> <span id="bevestig_huisnr"></span><br/>
					<span id="bevestig_pcode"></span> <span id="bevestig_plaats"></span><br/>
					<span id="bevestig_telnr"></span><br/>
					<span id="bevestig_user_email"></span><br/>
				</strong>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">Speciale wensen en/of mededeling:</div>
			<div class="kleistad-col-7"><span id="bevestig_opmerking"></span></div>
		</div>
		<?php $this->verklaring(); ?>
		<div class="kleistad-row kleistad-tab-footer">
			<div class="kleistad-col-10">
				Als het bovenstaande correct is, druk dan op verder.
			</div>
		</div>
		<?php
	}

}
