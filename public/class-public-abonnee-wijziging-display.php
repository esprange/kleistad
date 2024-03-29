<?php
/**
 * Toon het abonnement overzicht formulier
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
class Public_Abonnee_Wijziging_Display extends Public_Shortcode_Display {

	/**
	 * De start datum waarmee wijzingen ingaan
	 *
	 * @var int $per_datum De datum
	 */
	private int $per_datum;

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		$in_startperiode = strtotime( 'today' ) < $this->data['abonnement']->start_eind_datum;
		$this->per_datum = $in_startperiode ? $this->data['abonnement']->start_eind_datum : strtotime( 'first day of next month 00:00' );
		$this->abonnement_info()->abonnement_extra_info();
		$this->form(
			function() {
				$in_startperiode = strtotime( 'today' ) < $this->data['abonnement']->start_eind_datum;
				$this->per_datum = $in_startperiode ? $this->data['abonnement']->start_eind_datum : strtotime( 'first day of next month 00:00' );
				if ( $in_startperiode ) {
					$this->abonnement_soort()->eindigen()->submit();
					return;
				}
				if ( $this->data['abonnement']->eind_datum ) {
					?>
					<br>
					<p>Omdat een beëindiging van dit abonnement gepland is zijn er nu geen wijzigingen meer mogelijk</p>
					<?php
					return;
				}
				$this->abonnement_soort()->abonnement_extra()->pauze()->eindigen()->betaalwijze()->submit();
			}
		);
	}

	/**
	 * Geformatteerde per datum
	 *
	 * @return string
	 */
	private function per() : string {
		return wp_date( 'j F Y', $this->per_datum );
	}

	/**
	 * Render de huidige abonnement info
	 *
	 * @return Public_Abonnee_Wijziging_Display
	 */
	private function abonnement_info() : Public_Abonnee_Wijziging_Display {
		?>
		<p>Abonnement status per <?php echo esc_html( $this->per() ); ?> :</p>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label">Abonnement soort</label>
			</div>
			<div class="kleistad-col-3">
			<?php echo esc_html( $this->data['abonnement']->soort ); ?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label">Abonnement status</label>
			</div>
			<div class="kleistad-col-3">
				<?php echo esc_html( $this->data['abonnement']->get_statustekst( true ) ); ?>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de huidige extra's
	 */
	private function abonnement_extra_info() {
		$extra_beschikbaar = false;
		foreach ( opties()['extra'] as $extra ) {
			$extra_beschikbaar = $extra_beschikbaar || ( 0 < $extra['prijs'] );
		}
		if ( $extra_beschikbaar ) {
			?>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label">Extra's</label>
			</div>
			<div class="kleistad-col-3">
				<?php echo 0 < count( $this->data['abonnement']->extras ) ? implode( '<br/>', $this->data['abonnement']->extras ) : 'geen'; // phpcs:ignore ?>
			</div>
		</div>
			<?php
		}
	}

	/**
	 * Render de abonnement soort keuze
	 *
	 * @return Public_Abonnee_Wijziging_Display
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function abonnement_soort() : Public_Abonnee_Wijziging_Display {
		?>
		<div class="kleistad-row"> <!-- soort -->
			<div class="kleistad-col-6">
				<input type="radio" name="wijziging" id="kleistad_abo_soort" class="kleistad-radio" value="soort" >
				<label for="kleistad_abo_soort">Abonnement soort wijzigen</label>
			</div>
		</div>
		<div id="kleistad_optie_soort" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-7" >
					<?php if ( 'onbeperkt' === $this->data['abonnement']->soort ) : ?>
					<input name="soort" type="hidden" value="beperkt" >
					<p><strong>Je wilt per <?php echo esc_html( $this->per() ); ?> wijzigen van een onbeperkt naar een beperkt abonnement.</strong></p>
					<?php else : ?>
					<input name="soort" type="hidden" value="onbeperkt" >
					<p><strong>Je wilt per <?php echo esc_html( $this->per() ); ?> wijzigen van een beperkt naar een onbeperkt abonnement.</strong></p>
					<?php endif ?>
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de abonnement extra keuzes
	 *
	 * @return Public_Abonnee_Wijziging_Display
	 */
	private function abonnement_extra() : Public_Abonnee_Wijziging_Display {
		?>
		<div class="kleistad-row"> <!-- extras -->
			<div class="kleistad-col-6">
				<input type="radio" name="wijziging" id="kleistad_abo_extras" class="kleistad-radio" value="extras" >
				<label for="kleistad_abo_extras">Abonnement extras wijzigen</label>
			</div>
		</div>
		<div id="kleistad_optie_extras" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-3">
					&nbsp;
				</div>
				<div class="kleistad-col-7">
					<p><strong>Je wilt per <?php echo esc_html( $this->per() ); ?> een wijziging doorvoeren van de extra opties bij het abonnement.</strong></p>
				</div>
			</div>
			<?php
			$index = 0;
			foreach ( opties()['extra'] as $extra ) :
				if ( 0 < $extra['prijs'] ) :
					$index++;
					?>
			<div class="kleistad-row">
				<div class="kleistad-col-3">
					&nbsp;
				</div>
				<div class="kleistad-col-4">
					<input name="extras[]" id="extras_<?php echo esc_attr( $index ); ?>" type="checkbox" class="kleistad-checkbox"
						<?php checked( in_array( $extra['naam'], $this->data['abonnement']->extras, true ) ); ?>
						value="<?php echo esc_attr( $extra['naam'] ); ?>" />
					<label for="extras_<?php echo esc_attr( $index ); ?>" ><?php echo esc_html( $extra['naam'] ); ?></label>
				</div>
				<div class="kleistad-col-3">
					(&euro;<?php echo esc_html( number_format_i18n( $extra['prijs'], 2 ) ); ?> p.m.)
				</div>
			</div>
					<?php
				endif;
			endforeach;
			?>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de pauzeren optie
	 *
	 * @return Public_Abonnee_Wijziging_Display
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function pauze() : Public_Abonnee_Wijziging_Display {
		?>
		<div class="kleistad-row"> <!-- pauze -->
			<div class="kleistad-col-6">
				<input type="radio" name="wijziging" id="kleistad_abo_pauze" class="kleistad-radio" value="pauze" >
				<label for="kleistad_abo_pauze" class="kleistad-label_cbr">Abonnement pauzeren</label>
			</div>
		</div>
		<div id="kleistad_optie_pauze" style="display:none" >
			<?php
			if ( $this->data['abonnement']->is_gepauzeerd() ) :
				if ( $this->data['abonnement']->herstart_datum >= $this->per_datum ) :
					?>
			<div class="kleistad-row">
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-7" >
					<p><strong>Je wilt je gepauzeerde abonnement hervatten</strong></p>
					<p>Je kan de datum dat je abonnement hervat wordt wel aanpassen maar niet eerder dan per eerstvolgende maand en de maximale pauze is <?php echo esc_html( opties()['max_pauze_weken'] ); ?> weken.</p>
					<input name="pauze_datum" id="kleistad_pauze_datum" type="hidden" value="<?php echo esc_attr( wp_date( 'd-m-Y', $this->data['abonnement']->pauze_datum ) ); ?>"
						data-min_pauze="<?php echo esc_attr( max( ( $this->per_datum - $this->data['abonnement']->pauze_datum ) / DAY_IN_SECONDS, opties()['min_pauze_weken'] * 7 ) ); ?>"
						data-max_pauze="<?php echo esc_attr( opties()['max_pauze_weken'] * 7 ); ?>">
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-4 kleistad-label" >
					<label for="kleistad_herstart_datum">Tot</label>
				</div>
				<div class="kleistad-col-3">
					<input name="herstart_datum" id="kleistad_herstart_datum" class="kleistad-datum" type="text"
						value="<?php echo esc_attr( wp_date( 'd-m-Y', $this->data['abonnement']->herstart_datum ) ); ?>"
						readonly="readonly" >
				</div>
			</div>
				<?php else : ?>
			<div class="kleistad-row">
			<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-7" >
					<p><strong>Je abonnement staat al gepauzeerd en wordt per <?php echo esc_html( wp_date( 'd-m-Y', $this->data['abonnement']->herstart_datum ) ); ?> hervat.</strong></p>
				</div>
			</div>
				<?php endif // Er wordt deze maand of per eerste komende maand hervat. ?>
			<?php else : // Pauze is nog wel mogelijk. ?>
			<div class="kleistad-row" >
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-7" >
					<p><strong>Je wilt het abonnement pauzeren</strong></p>
					<p>Er kan maar één pauze tegelijk ingepland worden van minimaal <?php echo esc_html( opties()['min_pauze_weken'] ); ?> weken. Per kalender jaar mag er in totaal maximaal <?php echo esc_html( opties()['max_pauze_weken'] ); ?> weken gepauzeerd worden.</p>
					<p>Tijdens de pauze periode zijn er geen ovenreserveringen mogelijk. Bestaande reserveringen worden automatisch geannuleerd</p>
				</div>
			</div>
			<div class="kleistad-row" >
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-4 kleistad-label" >
					<label for="kleistad_pauze_datum">Vanaf</label>
				</div>
				<div class="kleistad-col-3">
					<input name="pauze_datum" id="kleistad_pauze_datum" class="kleistad-datum" type="text"
						value="<?php echo esc_attr( wp_date( 'd-m-Y', $this->per_datum ) ); ?>"
						data-min_pauze="<?php echo esc_attr( opties()['min_pauze_weken'] * 7 ); ?>"
						data-max_pauze="<?php echo esc_attr( opties()['max_pauze_weken'] * 7 ); ?>"
						readonly="readonly" >
				</div>
			</div>
			<div class="kleistad-row" >
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-4 kleistad-label" >
					<label for="kleistad_herstart_datum">Tot</label>
				</div>
				<div class="kleistad-col-3">
					<input name="herstart_datum" id="kleistad_herstart_datum" class="kleistad-datum" type="text"
						value="<?php echo esc_attr( wp_date( 'd-m-Y', strtotime( '+' . opties()['min_pauze_weken'] . 'weeks', $this->per_datum ) ) ); ?>"
						readonly="readonly" >
				</div>
			</div>
			<?php endif // Pauze is nog wel mogelijk. ?>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de betaalwijze wijziging
	 *
	 * @return Public_Abonnee_Wijziging_Display
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function betaalwijze() : Public_Abonnee_Wijziging_Display {
		?>
		<div class="kleistad-row"> <!-- betaalwijze -->
			<div class="kleistad-col-6">
				<input type="radio" name="wijziging" id="kleistad_abo_betaalwijze" class="kleistad-radio" value="betaalwijze" >
				<label for="kleistad_abo_betaalwijze" >Abonnement betaalwijze</label>
			</div>
		</div>
		<div id="kleistad_optie_betaalwijze" style="display:none" >
			<?php
			if ( ! $this->data['abonnement']->betaling->incasso_actief() ) :
				?>
			<div class="kleistad-row" >
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-7 kleistad-label" >
					<p><strong>Je wilt je huidige betaalwijze (betaling per bank) voor je abonnement per <?php echo esc_html( $this->per() ); ?> wijzigen naar automatische incasso.
					Je betaalt per iDeal € 0.01 en machtigt daarmee Kleistad om in het vervolg het abonnementsgeld maandelijks per SEPA incasso automatisch af te schrijven van jouw bankrekening.</strong></p>
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3">
					&nbsp;<input type="hidden" name="betaal" value="ideal" />
				</div>
				<div class="kleistad-col-7">
					<?php $this->ideal(); ?>
				</div>
			</div>
				<?php
			else : // Incasso is actief.
				?>
			<div class="kleistad-row" >
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-7 kleistad-label" >
					<p><strong>Je wilt je huidige betaalwijze (automatische sepa-incasso) per <?php echo esc_html( $this->per() ); ?> wijzigen naar overschrijving per bank.</strong></p>
				</div>
			</div>
			<div class ="kleistad-row">
				<div class="kleistad-col-3">
					&nbsp;<input type="hidden" name="betaal" value="stort" />
				</div>
			</div>
			<?php endif ?>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het eindigen van het abonnement
	 *
	 * @return Public_Abonnee_Wijziging_Display
	 */
	private function eindigen() : Public_Abonnee_Wijziging_Display {
		?>
		<div class="kleistad-row"> <!-- einde -->
			<div class="kleistad-col-6">
				<input type="radio" name="wijziging" id="kleistad_abo_einde" class="kleistad-radio" value="einde" >
				<label for="kleistad_abo_einde" class="kleistad-label_cbr">Abonnement beëindigen</label>
			</div>
		</div>
		<div id="kleistad_optie_einde" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-7 kleistad-label" >
					<p><strong>Je wilt je abonnement per <?php echo esc_html( $this->per() ); ?> stoppen</strong></p>
					<p>Eventuele ovenstook reserveringen na deze datum worden automatisch geannuleerd.</p>
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de formulier afsluiting
	 */
	private function submit() {
		?>
		<input type="hidden" name="abonnee_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" >
		<input type="hidden" name="per_datum" value="<?php echo esc_attr( $this->per_datum ); ?>" >
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-10">
				<button class="kleistad-button" name="kleistad_submit_abonnee_wijziging" type="submit" id="kleistad_submit_abonnee_wijziging" disabled >Bevestigen</button>
			</div>
		</div>
		<?php
	}

}
