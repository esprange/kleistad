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
	 * Of de abonnee nog in de start periode is
	 *
	 * @var bool $in_startperiode;
	 */
	private bool $in_startperiode;

	/**
	 * Of er extras beschikbaar zijn
	 *
	 * @var bool $extra_beschikbaar De vlag.
	 */
	private bool $extra_beschikbaar = false;

	/**
	 * Constructor
	 *
	 * @param array $data De formulier data.
	 */
	public function __construct( array $data ) {
		parent::__construct( $data );
		$this->in_startperiode = strtotime( 'today' ) < $this->data['abonnement']->start_eind_datum;
		$this->per_datum       = $this->in_startperiode ? $this->data['abonnement']->start_eind_datum : strtotime( 'first day of next month 00:00' );
		foreach ( opties()['extra'] as $extra ) {
			$this->extra_beschikbaar = $this->extra_beschikbaar || ( 0 < $extra['prijs'] );
		}
	}

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		$this->abonnement_info()->abonnement_extra_info();
		if ( $this->in_startperiode ) {
			$this->form()->werkdag()->eindigen()->form_end();
			return;
		}
		$this->form()->abonnement_soort()->abonnement_extra()->werkdag()->pauze()->eindigen()->betaalwijze()->form_end();
	}

	/**
	 * Geformatteerde per datum
	 *
	 * @return string
	 */
	private function per() : string {
		return date( 'j', $this->per_datum ) . strftime( ' %B %Y', $this->per_datum );
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
			<?php
				echo esc_html(
					$this->data['abonnement']->soort .
					( 'beperkt' === $this->data['abonnement']->soort ? ' (' . $this->data['abonnement']->dag . ')' : '' )
				);
			?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label">Abonnement status</label>
			</div>
			<div class="kleistad-col-3">
				<?php echo esc_html( $this->data['abonnement']->geef_statustekst( true ) ); ?>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de huidige extra's
	 *
	 * @return Public_Abonnee_Wijziging_Display
	 */
	private function abonnement_extra_info() : Public_Abonnee_Wijziging_Display {
		if ( $this->extra_beschikbaar ) {
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
		return $this;
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
				<input type="radio" name="wijziging" id="kleistad_abo_wijziging" class="kleistad_abo_optie kleistad-input_cbr" value="soort" >
				<label for="kleistad_abo_wijziging">Abonnement soort wijzigen</label>
			</div>
		</div>
		<div class="kleistad_abo_wijziging kleistad_abo_veld" style="display:none" >
			<div class="kleistad-row">
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-7" >
					<?php if ( 'onbeperkt' === $this->data['abonnement']->soort ) : ?>
					<input name="soort" type="hidden" value="beperkt" >
					<p><strong>Je wilt per <?php echo esc_html( $this->per() ); ?> wijzigen van een onbeperkt naar een beperkt abonnement.</strong></p><p>Kies de dag waarop je van een beperkt abonnement gebruikt gaat maken</p>
					<?php else : ?>
					<input name="soort" type="hidden" value="onbeperkt" >
					<p><strong>Je wilt per <?php echo esc_html( $this->per() ); ?> wijzigen van een beperkt naar een onbeperkt abonnement.</strong></p>
					<?php endif ?>
				</div>
			</div>
			<?php
			if ( 'onbeperkt' === $this->data['abonnement']->soort ) {
				$this->werkdagen();
			}
			?>
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
				<input type="radio" name="wijziging" id="kleistad_abo_extras" class="kleistad_abo_optie kleistad-input_cbr" value="extras" >
				<label for="kleistad_abo_extras">Abonnement extras wijzigen</label>
			</div>
		</div>
		<div class="kleistad_abo_extras kleistad_abo_veld" style="display:none" >
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
					<input name="extras[]" id="extras_<?php echo esc_attr( $index ); ?>" type="checkbox"
						<?php checked( false !== array_search( $extra['naam'], $this->data['abonnement']->extras, true ) ); ?>
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
	 * Render de wijziging werkdag optie, alleen als het een beperkt abonnement betreft.
	 *
	 * @return Public_Abonnee_Wijziging_Display
	 */
	private function werkdag() : Public_Abonnee_Wijziging_Display {
		if ( 'beperkt' === $this->data['abonnement']->soort ) {
			?>
		<div class="kleistad-row"> <!-- dag -->
			<div class="kleistad-col-6">
				<input type="radio" name="wijziging" id="kleistad_abo_dag" class="kleistad_abo_optie kleistad-input_cbr" value="dag" >
				<label for="kleistad_abo_dag">Abonnement werkdag wijzigen</label>
			</div>
		</div>
		<div class="kleistad_abo_dag kleistad_abo_veld" style="display:none" >
			<?php $this->werkdagen() ?>
		</div>
			<?php
		}
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
				<input type="radio" name="wijziging" id="kleistad_abo_pauze" class="kleistad_abo_optie kleistad-input_cbr" value="pauze" >
				<label for="kleistad_abo_pauze" class="kleistad-label_cbr">Abonnement pauzeren</label>
			</div>
		</div>
		<div class="kleistad_abo_pauze kleistad_abo_veld"  style="display:none" >
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
					<p>Je kan de datum dat je abonnement hervat wordt wel aanpassen maar niet eerder dan per eerstvolgende maand en de maximale pauze is <?php echo esc_html( Abonnement::MAX_PAUZE_WEKEN ); ?> weken.</p>
					<input name="pauze_datum" id="kleistad_pauze_datum" type="hidden" value="<?php echo esc_attr( date( 'd-m-Y', $this->data['abonnement']->pauze_datum ) ); ?>"
						data-min_pauze="<?php echo esc_attr( max( ( $this->per_datum - $this->data['abonnement']->pauze_datum ) / DAY_IN_SECONDS, Abonnement::MIN_PAUZE_WEKEN * 7 ) ); ?>"
						data-max_pauze="<?php echo esc_attr( Abonnement::MAX_PAUZE_WEKEN * 7 ); ?>">
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3" >
					&nbsp;
				</div>
]				<div class="kleistad-col-4 kleistad-label" >
					<label for="kleistad_herstart_datum">Tot</label>
				</div>
				<div class="kleistad-col-3">
					<input name="herstart_datum" id="kleistad_herstart_datum" class="kleistad-datum" type="text"
						value="<?php echo esc_attr( date( 'd-m-Y', $this->data['abonnement']->herstart_datum ) ); ?>"
						readonly="readonly" >
				</div>
			</div>
				<?php else : ?>
			<div class="kleistad-row">
			<div class="kleistad-col-3" >
					&nbsp;
				</div>
				<div class="kleistad-col-7" >
					<p><strong>Je abonnement staat al gepauzeerd en wordt per <?php echo esc_html( date( 'd-m-Y', $this->data['abonnement']->herstart_datum ) ); ?> hervat.</strong></p>
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
					<p>Er kan maar één pauze tegelijk ingepland worden van minimaal <?php echo esc_html( Abonnement::MIN_PAUZE_WEKEN ); ?> weken. Per kalender jaar mag er in totaal maximaal <?php echo esc_html( Abonnement::MAX_PAUZE_WEKEN ); ?> weken gepauzeerd worden.</p>
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
						value="<?php echo esc_attr( date( 'd-m-Y', $this->per_datum ) ); ?>"
						data-min_pauze="<?php echo esc_attr( Abonnement::MIN_PAUZE_WEKEN * 7 ); ?>"
						data-max_pauze="<?php echo esc_attr( Abonnement::MAX_PAUZE_WEKEN * 7 ); ?>"
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
						value="<?php echo esc_attr( date( 'd-m-Y', strtotime( '+' . Abonnement::MIN_PAUZE_WEKEN . 'weeks', $this->per_datum ) ) ); ?>"
						readonly="readonly" >
				</div>
			</div>
			<?php endif // Pauze is nog wel mogelijk. ?>
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
				<input type="radio" name="wijziging" id="kleistad_abo_einde" class="kleistad_abo_optie kleistad-input_cbr" value="einde" >
				<label for="kleistad_abo_einde" class="kleistad-label_cbr">Abonnement beëindigen</label>
			</div>
		</div>
		<div class="kleistad-row kleistad_abo_einde kleistad_abo_veld" style="display:none" >
			<div class="kleistad-col-3" >
				&nbsp;
			</div>
			<div class="kleistad-col-7 kleistad-label" >
				<p><strong>Je wilt je abonnement per <?php echo esc_html( $this->per() ); ?> stoppen</strong></p>
			</div>
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
				<input type="radio" name="wijziging" id="kleistad_abo_betaalwijze" class="kleistad_abo_optie kleistad-input_cbr" value="betaalwijze" >
				<label for="kleistad_abo_betaalwijze" >Abonnement betaalwijze</label>
			</div>
		</div>
		<div class="kleistad_abo_betaalwijze kleistad_abo_veld" style="display:none" >
			<?php
			if ( ! $this->data['incasso_actief'] ) :
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
				<?php
			endif; // Incasso is actief.
			?>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de formulier afsluiting
	 *
	 * @return Public_Abonnee_Wijziging_Display
	 */
	protected function form_end() : Public_Abonnee_Wijziging_Display {
		?>
		<input type="hidden" name="abonnee_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" >
		<input type="hidden" name="per_datum" value="<?php echo esc_attr( $this->per_datum ); ?>" >
		<div class="kleistad-row" style="padding-top:20px;">
			<div class="kleistad-col-10">
				<button class="kleistad-button" name="kleistad_submit_abonnee_wijziging" type="submit" id="kleistad_submit_abonnee_wijziging" disabled >Bevestigen</button>
			</div>
		</div>
		</form>
		<?php
		return $this;
	}

	/**
	 * Display de werkdag keuze.
	 */
	private function werkdagen() {
		?>
		<div class="kleistad-row" >
			<div class="kleistad-col-3">
				&nbsp;
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_dag_keuze2">Dag</label>
			</div>
			<div class="kleistad-col-4">
				<select class="kleistad-input" name="dag" id="kleistad_dag_keuze2" >
					<option value="maandag" <?php selected( $this->data['abonnement']->dag, 'maandag' ); ?> >Maandag</option>
					<option value="dinsdag" <?php selected( $this->data['abonnement']->dag, 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $this->data['abonnement']->dag, 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $this->data['abonnement']->dag, 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $this->data['abonnement']->dag, 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</div>
		</div>
		<?php
	}
}
