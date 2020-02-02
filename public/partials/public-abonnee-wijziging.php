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

$in_driemaandperiode = strtotime( 'today' ) < $data['driemaand_datum'];
$per_datum           = $in_driemaandperiode ? $data['driemaand_datum'] : strtotime( 'first day of next month 00:00' );
$per                 = date( 'j', $per_datum ) . strftime( ' %B %Y', $per_datum );

$extra_beschikbaar = false;
foreach ( $this->options['extra'] as $extra ) :
	$extra_beschikbaar = $extra_beschikbaar || ( 0 < $extra['prijs'] );
endforeach;
?>
<p>Abonnement status per <?php echo esc_html( $per ); ?> :</p>
<table class="kleistad_form">
	<tr><td>Abonnement soort</td><td>
	<?php
		echo esc_html(
			$data['abonnement']->soort .
			( 'beperkt' === $data['abonnement']->soort ? ' (' . $data['abonnement']->dag . ')' : '' )
		);
		?>
	</td></tr>
	<tr><td>Abonnement start</td><td><?php echo esc_html( strftime( '%x', $data['abonnement']->start_datum ) ); ?></td></tr>
	<tr><td>Abonnement status</td><td><?php echo esc_html( $data['abonnement']->status( true ) ); ?></td></tr>
	<?php if ( false !== $extra_beschikbaar ) : ?>
	<tr><td>Extra's</td><td>
		<?php echo esc_html( 0 < count( $data['abonnement']->extras ) ? implode( ', ', $data['abonnement']->extras ) : 'geen' ); ?>
	</td></tr>
	<?php endif ?>
</table>

<?php $this->form(); ?>
	<input type="hidden" name="abonnee_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" >
	<input type="hidden" name="per_datum" value="<?php echo esc_attr( $per_datum ); ?>" >
	<?php if ( ! $in_driemaandperiode ) : ?>
	<div class="kleistad_row"> <!-- soort -->
		<div class="kleistad_col_6">
			<input type="radio" name="wijziging" id="kleistad_abo_wijziging" class="kleistad_abo_optie kleistad_input_cbr" value="soort" >
			<label class="kleistad_label_cbr" for="kleistad_abo_wijziging">Abonnement soort wijzigen</label>
		</div>
	</div>
	<div class="kleistad_abo_wijziging kleistad_abo_veld" style="display:none" >
		<div class="kleistad_row">
			<div class="kleistad_col_3" >
				&nbsp;
			</div>
			<div class="kleistad_col_7" >
				<?php if ( 'onbeperkt' === $data['input']['soort'] ) : ?>
				<input name="soort" type="hidden" value="beperkt" >
				<p><strong>Je wilt per <?php echo esc_html( $per ); ?> wijzigen van een onbeperkt naar een beperkt abonnement. Kies de dag waarop je van een beperkt abonnement gebruikt gaat maken</strong></p>
				<?php else : ?>
				<input name="soort" type="hidden" value="onbeperkt" >
				<p><strong>Je wilt per <?php echo esc_html( $per ); ?> wijzigen van een beperkt naar een onbeperkt abonnement.</strong></p>
				<?php endif ?>
			</div>
		</div>
		<?php
		if ( 'onbeperkt' === $data['input']['soort'] ) :
			?>
		<div class="kleistad_row" >
			<div class="kleistad_col_3">
				&nbsp;
			</div>
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_dag_keuze">Dag</label>
			</div>
			<div class="kleistad_col_4">
				<select class="kleistad_input" name="dag" id="kleistad_dag_keuze" >
					<option value="maandag" <?php selected( $data['input']['dag'], 'maandag' ); ?> >Maandag</option>
					<option value="dinsdag" <?php selected( $data['input']['dag'], 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $data['input']['dag'], 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $data['input']['dag'], 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $data['input']['dag'], 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</div>
		</div>
			<?php
		endif;
		?>
	</div>
	<?php endif // Niet in 3 maand periode. ?>

	<?php if ( $extra_beschikbaar && ! $in_driemaandperiode ) : ?>
	<div class="kleistad_row"> <!-- extras -->
		<div class="kleistad_col_6">
			<input type="radio" name="wijziging" id="kleistad_abo_extras" class="kleistad_abo_optie kleistad_input_cbr" value="extras" >
			<label class="kleistad_label_cbr" for="kleistad_abo_extras">Abonnement extras wijzigen</label>
		</div>
	</div>
	<div class="kleistad_abo_extras kleistad_abo_veld" style="display:none" >
		<?php
		$i = 0;
		foreach ( $this->options['extra'] as $extra ) :
			if ( 0 < $extra['prijs'] ) :
				$i++;
				?>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<label class="kleistad_label"><?php echo 1 === $i ? 'Extra\'s' : ''; ?></label>
			</div>
				<?php
				$label = false;
				?>
			<div class="kleistad_col_4">
				<input class="kleistad_input_cbr" name="extras[]" id="extras_<?php echo esc_attr( $i ); ?>" type="checkbox"
					<?php checked( false !== array_search( $extra['naam'], $data['input']['extras'], true ) ); ?>
					data-bedrag="<?php echo esc_attr( 3 * $extra['prijs'] ); ?>"
					value="<?php echo esc_attr( $extra['naam'] ); ?>" />
				<label class="kleistad_label_cbr" for="extras_<?php echo esc_attr( $i ); ?>" ><?php echo esc_html( $extra['naam'] ); ?></label>
			</div>
			<div class="kleistad_col_3">
				<label class="kleistad_label" ><?php echo esc_html( ' (€ ' . number_format_i18n( $extra['prijs'], 2 ) . ' p.m.)' ); ?></label>
			</div>
		</div>
				<?php
			endif;
		endforeach;
		?>
	</div>
	<?php endif // Extras en niet in 3 maand periode. ?>
	<?php if ( 'beperkt' === $data['input']['soort'] ) : ?>
	<div class="kleistad_row"> <!-- dag -->
		<div class="kleistad_col_6">
			<input type="radio" name="wijziging" id="kleistad_abo_dag" class="kleistad_abo_optie kleistad_input_cbr" value="dag" >
			<label class="kleistad_label_cbr" for="kleistad_abo_dag">Abonnement werkdag wijzigen</label>
		</div>
	</div>
	<div class="kleistad_abo_dag kleistad_abo_veld" style="display:none" >
		<div class="kleistad_row">
			<div class="kleistad_col_3" >
				&nbsp;
			</div>
			<div class="kleistad_col_7" >
				<p><strong>Je hebt een beperkt abonnement en gaat Kleistad voortaan op een andere dag bezoeken. De wijziging gaat per direct in.</strong></p>
			</div>
		</div>
		<div class="kleistad_row" >
			<div class="kleistad_col_3">
				&nbsp;
			</div>
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_dag_keuze2">Dag</label>
			</div>
			<div class="kleistad_col_4">
				<select class="kleistad_input" name="dag" id="kleistad_dag_keuze2" >
					<option value="maandag" <?php selected( $data['input']['dag'], 'maandag' ); ?> >Maandag</option>
					<option value="dinsdag" <?php selected( $data['input']['dag'], 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $data['input']['dag'], 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $data['input']['dag'], 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $data['input']['dag'], 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</div>
		</div>
	</div>
	<?php endif // Wijzig beperkt. ?>
	<?php if ( ! $in_driemaandperiode ) : ?>
	<div class="kleistad_row"> <!-- pauze -->
		<div class="kleistad_col_6">
			<input type="radio" name="wijziging" id="kleistad_abo_pauze" class="kleistad_abo_optie kleistad_input_cbr" value="pauze" >
			<label for="kleistad_abo_pauze" class="kleistad_label_cbr">Abonnement pauzeren</label>
		</div>
	</div>
	<div class="kleistad_abo_pauze kleistad_abo_veld"  style="display:none" >
		<div class="kleistad_row" >
			<div class="kleistad_col_3" >
				&nbsp;
			</div>
			<div class="kleistad_col_7" >
				<p><strong>Je wilt het abonnement pauzeren</strong></p>
				<p>Er kan maar één pauze tegelijk ingepland worden van minimaal <?php echo esc_html( \Kleistad\Abonnement::MIN_PAUZE_WEKEN ); ?> weken. Per kalender jaar mag er in totaal maximaal <?php echo esc_html( \Kleistad\Abonnement::MAX_PAUZE_WEKEN ); ?> weken gepauzeerd worden.</p>
			</div>
		</div>
		<?php if ( $data['gepauzeerd'] ) : ?>
		<div class="kleistad_row">
			<p>Je abonnement staat al gepauzeerd, pas nadat het weer actief is kan je een nieuwe periode inplannen</p>
		</div>
		<?Php else : // Pauze is nog wel mogelijk. ?>
		<div class="kleistad_row" >
			<div class="kleistad_col_3" >
				&nbsp;
			</div>
			<div class="kleistad_col_4 kleistad_label" >
				<label for="kleistad_pauze_datum">Vanaf</label>
			</div>
			<div class="kleistad_col_3">
				<input name="pauze_datum" id="kleistad_pauze_datum" class="kleistad_datum" type="text"
					value="<?php echo esc_attr( date( 'd-m-Y', $per_datum ) ); ?>"
					data-min_pauze="<?php echo esc_attr( \Kleistad\Abonnement::MIN_PAUZE_WEKEN ); ?>"
					data-max_pauze="<?php echo esc_attr( \Kleistad\Abonnement::MAX_PAUZE_WEKEN ); ?>">
			</div>
		</div>
		<div class="kleistad_row" >
			<div class="kleistad_col_3" >
				&nbsp;
			</div>
			<div class="kleistad_col_4 kleistad_label" >
				<label for="kleistad_herstart_datum">Tot</label>
			</div>
			<div class="kleistad_col_3">
				<input name="herstart_datum" id="kleistad_herstart_datum" class="kleistad_datum" type="text"
					value="<?php echo esc_attr( date( 'd-m-Y', strtotime( '+' . \Kleistad\Abonnement::MIN_PAUZE_WEKEN . 'weeks', $per_datum ) ) ); ?>">
			</div>
		</div>
		<?php endif // Pauze is nog wel mogelijk. ?>
	</div>
	<?php endif // Niet in drie maand periode. ?>
	<div class="kleistad_row"> <!-- einde -->
		<div class="kleistad_col_6">
			<input type="radio" name="wijziging" id="kleistad_abo_einde" class="kleistad_abo_optie kleistad_input_cbr" value="einde" >
			<label for="kleistad_abo_einde" class="kleistad_label_cbr">Abonnement beëindigen</label>
		</div>
	</div>
	<div class="kleistad_row kleistad_abo_einde kleistad_abo_veld" style="display:none" >
		<div class="kleistad_col_3" >
			&nbsp;
		</div>
		<div class="kleistad_col_7 kleistad_label" >
			<p><strong>Je wilt je abonnement per <?php echo esc_html( $per ); ?> stoppen</strong></p>
		</div>
	</div>
	<?php if ( ! $in_driemaandperiode ) : ?>
	<div class="kleistad_row"> <!-- betaalwijze -->
		<div class="kleistad_col_6">
			<input type="radio" name="wijziging" id="kleistad_abo_betaalwijze" class="kleistad_abo_optie kleistad_input_cbr" value="betaalwijze" >
			<label class="kleistad_label_cbr" for="kleistad_abo_betaalwijze" >Abonnement betaalwijze</label>
		</div>
	</div>
	<div class="kleistad_abo_betaalwijze kleistad_abo_veld" style="display:none" >
		<?php
		if ( ! $data['incasso_actief'] ) :
			?>
		<div class="kleistad_row" >
			<div class="kleistad_col_3" >
				&nbsp;
			</div>
			<div class="kleistad_col_7 kleistad_label" >
				<p><strong>Je wilt je huidige betaalwijze (betaling per bank) voor je abonnement wijzigen naar automatische incasso.
				Je betaalt per iDeal € 0.01 en machtigt daarmee Kleistad om in het vervolg het abonnementsgeld maandelijks per SEPA incasso automatisch af te schrijven van jouw bankrekening.</strong></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				&nbsp;<input type="hidden" name="betaal" value="ideal" />
			</div>
			<div class="kleistad_col_7">
				<?php \Kleistad\Betalen::issuers(); ?>
			</div>
		</div>
			<?php
		else : // Incasso is actief.
			?>
		<div class="kleistad_row" >
			<div class="kleistad_col_3" >
				&nbsp;
			</div>
			<div class="kleistad_col_7 kleistad_label" >
				<p><strong>Je wilt je huidige betaalwijze (automatische sepa-incasso) wijzigen naar overschrijving per bank.</strong></p>
			</div>
		</div>
		<div class ="kleistad_row">
			<div class="kleistad_col_3">
				&nbsp;<input type="hidden" name="betaal" value="stort" />
			</div>
		</div>
			<?php
		endif; // Incasso is actief.
		?>
	</div>
	<?php endif // Niet in 3 maanden periode. ?>
	<div class="kleistad_row" style="padding-top:20px;">
		<div class="kleistad_col_10">
			<button name="kleistad_submit_abonnee_wijziging" type="submit" id="kleistad_submit_abonnee_wijziging" disabled >Bevestigen</button>
		</div>
	</div>
</form>
