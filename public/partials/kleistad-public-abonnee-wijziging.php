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

if ( ! is_user_logged_in() ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	$in_driemaandperiode = strtotime( 'today' ) < $data['driemaand_datum'];
	$per_datum           = $in_driemaandperiode ? $data['driemaand_datum'] : mktime( 0, 0, 0, intval( date( 'n' ) ) + 1, 1, intval( date( 'Y' ) ) );
	$per                 = date( 'j', $per_datum ) . strftime( ' %B %Y', $per_datum );

	$extra_beschikbaar = false;
	if ( ! $in_driemaandperiode ) :
		foreach ( $this->options['extra'] as $extra ) :
			$extra_beschikbaar = $extra_beschikbaar || ( 0 < $extra['prijs'] );
		endforeach;
	endif;
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
		<tr><td>Abonnement status</td><td>
		<?php
		$vandaag = strtotime( 'today' );
		if ( ! $data['abonnement']->geannuleerd ) :
			if ( $data['abonnement']->gepauzeerd ) :
				if ( $data['abonnement']->eind_datum > $vandaag ) :
					echo esc_html(
						'gepauzeerd sinds ' . strftime( '%x', $data['abonnement']->pauze_datum ) .
						' beëindigen per ' . strftime( '%x', $data['abonnement']->eind_datum )
					);
				else :
					echo esc_html(
						'gepauzeerd sinds ' . strftime( '%x', $data['abonnement']->pauze_datum ) .
						' tot ' . strftime( '%x', $data['abonnement']->herstart_datum )
					);
				endif;
			elseif ( $data['abonnement']->eind_datum > $vandaag ) :
				echo esc_html( 'actief, beëindigen per ' . strftime( '%x', $data['abonnement']->eind_datum ) );
			elseif ( $data['abonnement']->pauze_datum > $vandaag ) :
				echo esc_html(
					'actief, pauzeren per ' . strftime( '%x', $data['abonnement']->pauze_datum ) .
					' tot ' . strftime( '%x', $data['abonnement']->herstart_datum )
				);
			elseif ( $data['abonnement']->eind_datum > $vandaag ) :
				echo esc_html(
					'actief, beëindigen per ' . strftime( '%x', $data['abonnement']->eind_datum ) .
					' tot ' . strftime( '%x', $data['abonnement']->herstart_datum )
				);
			elseif ( false !== $data['abonnement']->herstart_datum ) :
				echo esc_html( 'actief, herstart sinds ' . strftime( '%x', $data['abonnement']->herstart_datum ) );
			elseif ( $data['abonnement']->start_datum > $vandaag ) :
				echo esc_html( 'nog niet actief' );
			else :
				echo esc_html( 'actief' );
			endif;
		else :
			echo 'geannuleerd sinds ' . esc_html( strftime( '%x', $data['abonnement']->eind_datum ) );
		endif
		?>
		</td></tr>
		<?php if ( false !== $extra_beschikbaar ) : ?>
		<tr><td>Extra's</td><td>
			<?php echo esc_html( 0 < count( $data['abonnement']->extras ) ? implode( ', ', $data['abonnement']->extras ) : 'geen' ); ?>
		</td></tr>
		<?php endif ?>
	</table>

	<form method="POST" id="kleistad_abonnee_wijziging">
		<?php wp_nonce_field( 'kleistad_abonnee_wijziging' ); ?>
		<input type="hidden" name="abonnee_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" >
		<input type="hidden" name="per_datum" value="<?php echo esc_attr( $per_datum ); ?>" >
		<div class="kleistad_row"> <!-- soort -->
			<div class="kleistad_col_6">
				<input type="radio" name="actie" id="kleistad_abo_wijziging" class="kleistad_abo_optie kleistad_input_cbr" value="wijziging" >
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
		<?php
		if ( $extra_beschikbaar ) :
			?>
		<div class="kleistad_row"> <!-- extras -->
			<div class="kleistad_col_6">
				<input type="radio" name="actie" id="kleistad_abo_extras" class="kleistad_abo_optie kleistad_input_cbr" value="extras" >
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
			<?php
		endif;
		if ( 'beperkt' === $data['input']['soort'] ) :
			?>
		<div class="kleistad_row"> <!-- dag -->
			<div class="kleistad_col_6">
				<input type="radio" name="actie" id="kleistad_abo_dag" class="kleistad_abo_optie kleistad_input_cbr" value="dag" >
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
			<?php
		endif;
		if ( ! $in_driemaandperiode ) :
			if ( $data['input']['actief'] ) :
				?>
		<div class="kleistad_row"> <!-- pauze -->
			<div class="kleistad_col_6">
				<input type="radio" name="actie" id="kleistad_abo_pauze" class="kleistad_abo_optie kleistad_input_cbr" value="pauze" >
				<label for="kleistad_abo_pauze" class="kleistad_label_cbr">Abonnement pauzeren</label>
			</div>
		</div>
		<div class="kleistad_abo_pauze kleistad_abo_veld"  style="display:none" >
			<div class="kleistad_row" >
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_col_7 kleistad_label" >
					<p><strong>Je wilt je abonnement per <?php echo esc_html( $per ); ?> tijdelijk pauzeren</strong></p>
				</div>
			</div>
			<div class="kleistad_row" >
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_col_4 kleistad_label" >
					<label for="kleistad_pauze_maanden">aantal maanden pauze</label>
				</div>
				<div class="kleistad_col_3">
					<input name="pauze_maanden" id="kleistad_pauze_maanden" value="1" />
				</div>
			</div>
		</div>
				<?php
			else : // Abonnement niet actief.
				?>
		<div class="kleistad_row"> <!-- start -->
			<div class="kleistad_col_6">
				<input type="radio" name="actie" id="kleistad_abo_start" class="kleistad_abo_optie kleistad_input_cbr" value="herstart" >
				<label for ="kleistad_abo_start" class="kleistad_label_cbr">Abonnement hervatten</label>
			</div>
		</div>
		<div class="kleistad_abo_start kleistad_abo_veld" style="display:none" >
			<div class="kleistad_row" >
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_col_7 kleistad_label" >
					<p><strong>Je wilt je gepauzeerde abonnement hervatten</strong></p>
				</div>
			</div>
			<div class="kleistad_row" >
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_col_4 kleistad_label" >
					<label for="kleistad_startdatum">per</label>
				</div>
				<div class="kleistad_col_3">
					<select name="startdatum" id="kleistad_startdatum" >
					<?php
					for ( $i = 1; $i <= 3; $i++ ) :
						$datum = mktime( 0, 0, 0, intval( date( 'n' ) ) + $i, 1, intval( date( 'Y' ) ) );
						?>
							<option value="<?php echo esc_attr( $datum ); ?>"><?php echo esc_html( strftime( '%B %Y', $datum ) ); ?></option>
						<?php
					endfor
					?>
					</select>
				</div>
			</div>
		</div>
				<?php
			endif; // Abonnement niet actief.
		endif // Niet in drie maand periode.
		?>
		<div class="kleistad_row"> <!-- einde -->
			<div class="kleistad_col_6">
				<input type="radio" name="actie" id="kleistad_abo_einde" class="kleistad_abo_optie kleistad_input_cbr" value="einde" >
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
		<?php
		if ( ! $in_driemaandperiode ) :
			?>
		<div class="kleistad_row"> <!-- betaalwijze -->
			<div class="kleistad_col_6">
				<input type="radio" name="actie" id="kleistad_abo_betaalwijze" class="kleistad_abo_optie kleistad_input_cbr" value="betaalwijze" >
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
					<?php Kleistad_Betalen::issuers(); ?>
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
			<?php
		endif; // Niet in 3 maanden periode.
		?>
		<div class="kleistad_row" style="padding-top:20px;">
			<div class="kleistad_col_10">
				<input type="hidden" name="kleistad_submit_abonnee_wijziging" value="0" >
				<button name="kleistad_check_abonnee_wijziging" id="kleistad_check_abonnee_wijziging" >Bevestigen</button>
			</div>
		</div>
	</form>
	<div id="kleistad_confirm" title="Abonnement wijziging">
	</div>
<?php endif ?>
