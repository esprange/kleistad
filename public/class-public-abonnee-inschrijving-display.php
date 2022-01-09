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
	protected function overzicht() {
		$this->form();
	}

	/**
	 * De formulier inhoud
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public function form_content() {
		?>
		<div class="kleistad-tab"><?php $this->abonnement_info(); ?></div>
		<?php if ( is_super_admin() ) : ?>
		<div class="kleistad-tab"><?php $this->gebruiker_selectie( 'Abonnee' ); ?></div>
			<?php
		else :
			if ( is_user_logged_in() ) :
				?>
		<div class="kleistad-tab"><?php	$this->gebruiker_logged_in(); ?></div>
			<?php else : ?>
		<div class="kleistad-tab"><?php $this->gebruiker(); ?></div>
			<?php endif ?>
		<div class="kleistad-tab"><?php $this->opmerking()->nieuwsbrief(); ?></div>
		<div class="kleistad-tab"><?php $this->bevestiging(); ?></div>
		<?php endif ?>
		<div class="kleistad-tab"><?php $this->betaal_info(); ?></div>
		<?php
	}

	/**
	 * Render het abonnement velden
	 */
	private function abonnement_info() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-6">
				<h3>Beantwoord onderstaande vragen en druk dan op <span style="font-style: italic;">Verder</span></h3>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label">Keuze abonnement</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-1">
			</div>
			<div class="kleistad-col-6">
				<input name="abonnement_keuze" id="kleistad_onbeperkt" type="radio" checked required
					data-bedrag="<?php echo esc_attr( 3 * opties()['onbeperkt_abonnement'] ); ?>"
					data-bedragtekst="= 3 termijnen"
					value="onbeperkt" <?php checked( 'onbeperkt', $this->data['input']['abonnement_keuze'] ); ?> />
				<label for="kleistad_onbeperkt" >
					Onbeperkte toegang (€ <?php echo esc_html( number_format_i18n( opties()['onbeperkt_abonnement'], 2 ) ); ?> p.m.)
				</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-1">
			</div>
			<div class="kleistad-col-6">
				<input name="abonnement_keuze" id="kleistad_beperkt" type="radio" required
					data-bedrag="<?php echo esc_attr( 3 * opties()['beperkt_abonnement'] ); ?>"
					data-bedragtekst="= 3 termijnen"
					value="beperkt" <?php checked( 'beperkt', $this->data['input']['abonnement_keuze'] ); ?> />
				<label for="kleistad_beperkt">
					Beperkte toegang, 1 dagdeel per week (€ <?php echo esc_html( number_format_i18n( opties()['beperkt_abonnement'], 2 ) ); ?> p.m.)
				</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-4">
				<label class="kleistad-label" for="kleistad_start_datum">Het abonnement moet ingaan per</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-1">
			</div>
			<div class="kleistad-col-3 kleistad-input">
				<input class="kleistad-datum kleistad-input" name="start_datum" id="kleistad_start_datum" type="text" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
			</div>
		</div>
		<?php
	}

	/**
	 * Render de betaal sectie
	 */
	private function betaal_info() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<label class="kleistad-label">Bepaal de wijze van betalen.</label>
			</div>
		</div>
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
				Het betreft de start van een <strong><span id="bevestig_abonnement_keuze" style="text-transform: lowercase;" ></span></strong> abonnement dat ingaat per <strong><span id="bevestig_start_datum"></span></strong>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				Abonnee gegevens:
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
