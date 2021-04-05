<?php
/**
 * Toon het abonnee inschrijving formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de abonnee inschrijving formulier.
 */
class Public_Cursus_Inschrijving_Display extends ShortcodeDisplay {

	/**
	 * Render het formulier
	 *
	 * @return void
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function html() {
		$this->form();
		if ( 'indelen_na_wachten' === $this->data['actie'] ) {
			$this->indelen_na_wachten()->form_end( 'Betalen' );
			return;
		}
		if ( 'stop_wachten' === $this->data['actie'] ) {
			$this->stop_wachten()->form_end( 'Afmelden' );
			return;
		}
		if ( 'inschrijven' === $this->data['actie'] ) {
			$this->cursus_info()->techniek_keuze();
			if ( is_super_admin() ) {
				$this->aantal( 1 )->gebruiker_selectie();
			} elseif ( is_user_logged_in() ) {
				$this->aantal( 1 )->gebruiker_logged_in()->opmerking()->nieuwsbrief();
			} else {
				$this->aantal()->gebruiker()->opmerking()->nieuwsbrief();
			}
			$this->betaal_info()->form_end( 'Inschrijven' );
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
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function cursus_info() : Public_Cursus_Inschrijving_Display {
		?>
		<div id="kleistad_cursussen" >
		<?php
		$geselecteerd = false;
		foreach ( $this->data['open_cursussen'] as $cursus_id => $cursus ) {
			$json_cursus = wp_json_encode(
				[
					'technieken' => $cursus->technieken,
					'meer'       => $cursus->meer,
					'ruimte'     => min( $cursus->ruimte(), 4 ),
					'bedrag'     => $cursus->bedrag(),
					'lopend'     => $cursus->is_lopend(),
					'vol'        => $cursus->vol,
				]
			);
			$tooltip     = 0 < $cursus->inschrijfkosten ?
				sprintf( 'cursus %s start per %s|%d lessen', $cursus->naam, strftime( '%x', $cursus->start_datum ), count( $cursus->lesdatums ) ) :
				sprintf( 'workshop op %s', strftime( '%x', $cursus->start_datum ) );
			$tooltip    .=
				sprintf( '|docent is %s|kosten &euro;%01.2f p.p.', $cursus->docent_naam(), $cursus->inschrijfkosten + $cursus->cursuskosten );
			if ( $cursus->vervallen ) {
				$selecteerbaar = false;
				$style         = 'color: gray;';
				$naam          = "$cursus->naam VERVALLEN";
			} elseif ( $cursus->vol ) {
				if ( $cursus->is_wachtbaar() ) {
					$selecteerbaar = true;
					$style         = '';
				}
				$naam = "$cursus->naam VOL";
			} else {
				$selecteerbaar = true;
				$style         = '';
				$naam          = $cursus->naam;
			}
			?>
			<div class="kleistad-row" style="overflow-x:auto;white-space:nowrap;">
				<input name="cursus_id" id="kleistad_cursus_<?php echo esc_attr( $cursus_id ); ?>" type="radio" value="<?php echo esc_attr( $cursus_id ); ?>"
					data-cursus='<?php echo $json_cursus; // phpcs:ignore ?>' <?php disabled( ! $selecteerbaar ); ?> <?php checked( $selecteerbaar && ! $geselecteerd ); ?> />
				<label title="<?php echo $tooltip; // phpcs:ignore ?>" for="kleistad_cursus_<?php echo esc_attr( $cursus_id ); ?>">
					<span style="<?php echo esc_attr( $style ); ?>"><?php echo esc_html( $naam ); ?></span></label>
			</div>
			<?php
			$geselecteerd = $geselecteerd || $selecteerbaar;
		}
		?>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de techniek keuze. Javascript bepaalt of dit wordt getoond.
	 *
	 * @return Public_Cursus_Inschrijving_Display
	 */
	private function techniek_keuze() : Public_Cursus_Inschrijving_Display {
		?>
		<div id="kleistad_cursus_technieken" style="visibility: hidden;padding-bottom: 20px;" >
			<div class="kleistad-row" >
				<div class="kleistad-col-10">
					<label class="kleistad-label">kies de techniek(en) die je wilt oefenen</label>
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
		return $this;
	}

	/**
	 * Render de cursist_info
	 *
	 * @param int|null $aantal Het aantal, als null dan tonen we het.
	 * @return Public_Cursus_Inschrijving_Display
	 */
	private function aantal( ?int $aantal = null ) : Public_Cursus_Inschrijving_Display {
		if ( $aantal ) {
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
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" <?php checked( $this->data['input']['betaal'], 'ideal' ); ?> />
				<label for="kleistad_betaal_ideal" style="max-width:80%" ></label>
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
				<label for="kleistad_betaal_stort" style="max-width:80%" ></label>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de afronding van het formulier
	 *
	 * @param string $buttontekst De tekst op de button.
	 * @return Public_Cursus_Inschrijving_Display
	 */
	private function form_end( string $buttontekst ) : Public_Cursus_Inschrijving_Display {
		?>
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-10">
				<button name="kleistad_submit_cursus_inschrijving" id="kleistad_submit" value="<?php echo esc_attr( $this->data['actie'] ); ?>" type="submit" ><?php echo esc_html( $buttontekst ); ?></button>
			</div>
		</div>
		</form>
		<?php
		return $this;
	}
}
