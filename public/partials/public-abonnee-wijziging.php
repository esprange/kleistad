<?php
/**
 * Toon het abonnee wijziging formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

namespace Kleistad;

$in_startperiode   = strtotime( 'today' ) < $data['abonnement']->start_eind_datum;
$per_datum         = $in_startperiode ? $data['abonnement']->start_eind_datum : strtotime( 'first day of next month 00:00' );
$per               = date( 'j', $per_datum ) . strftime( ' %B %Y', $per_datum );
$extra_beschikbaar = false;
foreach ( $this->options['extra'] as $extra ) :
	$extra_beschikbaar = $extra_beschikbaar || ( 0 < $extra['prijs'] );
endforeach;
?>
<p>Abonnement status per <?php echo esc_html( $per ); ?> :</p>
<div class="kleistad-row">
	<div class="kleistad-col-3">
		<label class="kleistad-label">Abonnement soort</label>
	</div>
	<div class="kleistad-col-3">
	<?php
		echo esc_html(
			$data['abonnement']->soort .
			( 'beperkt' === $data['abonnement']->soort ? ' (' . $data['abonnement']->dag . ')' : '' )
		);
		?>
	</div>
</div>
<div class="kleistad-row">
	<div class="kleistad-col-3">
		<label class="kleistad-label">Abonnement status</label>
	</div>
	<div class="kleistad-col-3">
		<?php echo esc_html( $data['abonnement']->geef_statustekst( true ) ); ?>
	</div>
</div>
	<?php if ( false !== $extra_beschikbaar ) : ?>
<div class="kleistad-row">
	<div class="kleistad-col-3">
		<label class="kleistad-label">Extra's</label>
	</div>
	<div class="kleistad-col-3">
		<?php echo 0 < count( $data['abonnement']->extras ) ? implode( '<br/>', $data['abonnement']->extras ) : 'geen'; // phpcs:ignore ?>
	</div>
</div>
	<?php endif ?>

<?php $this->form(); ?>
	<input type="hidden" name="abonnee_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" >
	<input type="hidden" name="per_datum" value="<?php echo esc_attr( $per_datum ); ?>" >
	<?php if ( ! $in_startperiode ) : ?>
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
				<?php if ( 'onbeperkt' === $data['abonnement']->soort ) : ?>
				<input name="soort" type="hidden" value="beperkt" >
				<p><strong>Je wilt per <?php echo esc_html( $per ); ?> wijzigen van een onbeperkt naar een beperkt abonnement.</strong></p><p>Kies de dag waarop je van een beperkt abonnement gebruikt gaat maken</p>
				<?php else : ?>
				<input name="soort" type="hidden" value="onbeperkt" >
				<p><strong>Je wilt per <?php echo esc_html( $per ); ?> wijzigen van een beperkt naar een onbeperkt abonnement.</strong></p>
				<?php endif ?>
			</div>
		</div>
		<?php
		if ( 'onbeperkt' === $data['abonnement']->soort ) :
			?>
		<div class="kleistad-row" >
			<div class="kleistad-col-3">
				&nbsp;
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_dag_keuze">Dag</label>
			</div>
			<div class="kleistad-col-4">
				<select class="kleistad-input" name="dag" id="kleistad_dag_keuze" >
					<option value="maandag" <?php selected( $data['abonnement']->dag, 'maandag' ); ?> >Maandag</option>
					<option value="dinsdag" <?php selected( $data['abonnement']->dag, 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $data['abonnement']->dag, 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $data['abonnement']->dag, 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $data['abonnement']->dag, 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</div>
		</div>
			<?php
		endif;
		?>
	</div>
	<?php endif // Niet in start periode. ?>

	<?php if ( $extra_beschikbaar && ! $in_startperiode ) : ?>
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
				<p><strong>Je wilt per <?php echo esc_html( $per ); ?> een wijziging doorvoeren van de extra opties bij het abonnement.</strong></p>
			</div>
		</div>
		<?php
		$i = 0;
		foreach ( $this->options['extra'] as $extra ) :
			if ( 0 < $extra['prijs'] ) :
				$i++;
				?>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				&nbsp;
			</div>
			<div class="kleistad-col-4">
				<input name="extras[]" id="extras_<?php echo esc_attr( $i ); ?>" type="checkbox"
					<?php checked( false !== array_search( $extra['naam'], $data['abonnement']->extras, true ) ); ?>
					value="<?php echo esc_attr( $extra['naam'] ); ?>" />
				<label for="extras_<?php echo esc_attr( $i ); ?>" ><?php echo esc_html( $extra['naam'] ); ?></label>
			</div>
			<div class="kleistad-col-3">
				<?php echo esc_html( ' (€ ' . number_format_i18n( $extra['prijs'], 2 ) . ' p.m.)' ); ?>
			</div>
		</div>
				<?php
			endif;
		endforeach;
		?>
	</div>
	<?php endif // Extras en niet in 3 maand periode. ?>
	<?php if ( 'beperkt' === $data['abonnement']->soort ) : ?>
	<div class="kleistad-row"> <!-- dag -->
		<div class="kleistad-col-6">
			<input type="radio" name="wijziging" id="kleistad_abo_dag" class="kleistad_abo_optie kleistad-input_cbr" value="dag" >
			<label for="kleistad_abo_dag">Abonnement werkdag wijzigen</label>
		</div>
	</div>
	<div class="kleistad_abo_dag kleistad_abo_veld" style="display:none" >
		<div class="kleistad-row">
			<div class="kleistad-col-3" >
				&nbsp;
			</div>
			<div class="kleistad-col-7" >
				<p><strong>Je hebt een beperkt abonnement en gaat Kleistad voortaan op een andere dag bezoeken. De wijziging gaat per direct in.</strong></p>
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-3">
				&nbsp;
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_dag_keuze2">Dag</label>
			</div>
			<div class="kleistad-col-4">
				<select class="kleistad-input" name="dag" id="kleistad_dag_keuze2" >
					<option value="maandag" <?php selected( $data['abonnement']->dag, 'maandag' ); ?> >Maandag</option>
					<option value="dinsdag" <?php selected( $data['abonnement']->dag, 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $data['abonnement']->dag, 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $data['abonnement']->dag, 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $data['abonnement']->dag, 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</div>
		</div>
	</div>
	<?php endif // Wijzig beperkt. ?>
	<?php if ( ! $in_startperiode ) : ?>
	<div class="kleistad-row"> <!-- pauze -->
		<div class="kleistad-col-6">
			<input type="radio" name="wijziging" id="kleistad_abo_pauze" class="kleistad_abo_optie kleistad-input_cbr" value="pauze" >
			<label for="kleistad_abo_pauze" class="kleistad-label_cbr">Abonnement pauzeren</label>
		</div>
	</div>
	<div class="kleistad_abo_pauze kleistad_abo_veld"  style="display:none" >
		<?php
		if ( $data['abonnement']->is_gepauzeerd() ) :
			if ( $data['abonnement']->herstart_datum >= $per_datum ) :
				?>
		<div class="kleistad-row">
			<div class="kleistad-col-3" >
				&nbsp;
			</div>
			<div class="kleistad-col-7" >
				<p><strong>Je wilt je gepauzeerde abonnement hervatten</strong></p>
				<p>Je kan de datum dat je abonnement hervat wordt wel aanpassen maar niet eerder dan per eerstvolgende maand en de maximale pauze is <?php echo esc_html( Abonnement::MAX_PAUZE_WEKEN ); ?> weken.</p>
				<input name="pauze_datum" id="kleistad_pauze_datum" type="hidden" value="<?php echo esc_attr( date( 'd-m-Y', $data['abonnement']->pauze_datum ) ); ?>"
					data-min_pauze="<?php echo esc_attr( max( ( $per_datum - $data['abonnement']->pauze_datum ) / DAY_IN_SECONDS, Abonnement::MIN_PAUZE_WEKEN * 7 ) ); ?>"
					data-max_pauze="<?php echo esc_attr( Abonnement::MAX_PAUZE_WEKEN * 7 ); ?>">
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
					value="<?php echo esc_attr( date( 'd-m-Y', $data['abonnement']->herstart_datum ) ); ?>"
					readonly="readonly" >
			</div>
		</div>
			<?php else : ?>
		<div class="kleistad-row">
			<p>Je abonnement staat al gepauzeerd en wordt per <?php echo esc_html( date( 'd-m-Y', $data['abonnement']->herstart_datum ) ); ?> hervat.</p>
		</div>
			<?php endif // Er wordt deze maand of per eerste komende maand hervat. ?>
		<?Php else : // Pauze is nog wel mogelijk. ?>
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
					value="<?php echo esc_attr( date( 'd-m-Y', $per_datum ) ); ?>"
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
					value="<?php echo esc_attr( date( 'd-m-Y', strtotime( '+' . Abonnement::MIN_PAUZE_WEKEN . 'weeks', $per_datum ) ) ); ?>"
					readonly="readonly" >
			</div>
		</div>
		<?php endif // Pauze is nog wel mogelijk. ?>
	</div>
	<?php endif // Niet in drie maand periode. ?>
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
			<p><strong>Je wilt je abonnement per <?php echo esc_html( $per ); ?> stoppen</strong></p>
		</div>
	</div>
	<?php if ( ! $in_startperiode ) : ?>
	<div class="kleistad-row"> <!-- betaalwijze -->
		<div class="kleistad-col-6">
			<input type="radio" name="wijziging" id="kleistad_abo_betaalwijze" class="kleistad_abo_optie kleistad-input_cbr" value="betaalwijze" >
			<label for="kleistad_abo_betaalwijze" >Abonnement betaalwijze</label>
		</div>
	</div>
	<div class="kleistad_abo_betaalwijze kleistad_abo_veld" style="display:none" >
		<?php
		if ( ! $data['incasso_actief'] ) :
			?>
		<div class="kleistad-row" >
			<div class="kleistad-col-3" >
				&nbsp;
			</div>
			<div class="kleistad-col-7 kleistad-label" >
				<p><strong>Je wilt je huidige betaalwijze (betaling per bank) voor je abonnement per <?php echo esc_html( $per ); ?> wijzigen naar automatische incasso.
				Je betaalt per iDeal € 0.01 en machtigt daarmee Kleistad om in het vervolg het abonnementsgeld maandelijks per SEPA incasso automatisch af te schrijven van jouw bankrekening.</strong></p>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				&nbsp;<input type="hidden" name="betaal" value="ideal" />
			</div>
			<div class="kleistad-col-7">
				<?php Betalen::issuers(); ?>
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
				<p><strong>Je wilt je huidige betaalwijze (automatische sepa-incasso) per <?php echo esc_html( $per ); ?> wijzigen naar overschrijving per bank.</strong></p>
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
	<?php endif // Niet in 3 maanden periode. ?>
	<div class="kleistad-row" style="padding-top:20px;">
		<div class="kleistad-col-10">
			<button name="kleistad_submit_abonnee_wijziging" type="submit" id="kleistad_submit_abonnee_wijziging" disabled >Bevestigen</button>
		</div>
	</div>
</form>
