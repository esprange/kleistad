<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! is_user_logged_in() ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	$in_driemaandperiode = time() < $data['driemaand_datum'];
	$per_datum = $in_driemaandperiode ? $data['driemaand_datum'] : mktime( 0, 0, 0, date( 'n' ) + 1, 1, date( 'Y' ) );
	$per = date( 'j', $per_datum ) . strftime( ' %B %Y', $per_datum );
	?>

	<form action="<?php echo esc_url( get_permalink() ); ?>" method="POST" id="kleistad_abonnee_wijziging">
		<?php wp_nonce_field( 'kleistad_abonnee_wijziging' ); ?>
		<input type="hidden" name="abonnee_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" >
		<input type="hidden" name="per_datum" value="<?php echo esc_attr( $per_datum ); ?>" >
		<?php
		if ( ! $in_driemaandperiode ) :
			?>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_4">
				<label for="kleistad_abo_wijziging">Abonnement wijzigen</label>
			</div>
			<div class="kleistad_col_6">
				<input name="actie" id="kleistad_abo_wijziging" class="kleistad_abo_optie" type="checkbox" value="wijziging" >
			</div>
		</div>
		<div class="kleistad_abo_wijziging" style="display:none" >
			<div class="kleistad_row">
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_col_7" >
				<?php
				if ( 'onbeperkt' === $data['input']['soort'] ) :
					?>
					<input name="soort" type="hidden" value="beperkt" >
					<p><strong>Je wilt per <?php echo esc_html( $per ); ?> wijzigen van een onbeperkt naar een beperkt abonnement. Kies de dag waarop je van een beperkt abonnement gebruikt gaat maken</strong></p>
					<?php
					else :
						?>
					<input name="soort" type="hidden" value="onbeperkt" >
					<p><strong>Je wilt per <?php echo esc_html( $per ); ?> wijzigen van een beperkt naar een onbeperkt abonnement.</strong></p>
						<?php
					endif;
					?>
				</div>
			</div>
				<?php
				if ( 'onbeperkt' === $data['input']['soort'] ) :
					?>
			<div class="kleistad_row" >
				<div class="kleistad_label kleistad_col_3">
					<label for="kleistad_dag_keuze">Dag</label>
				</div>
				<div class ="kleistad_col_7">
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
			if ( $data['input']['actief'] ) :
				?>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_4">
				<label for="kleistad_abo_pauze">Abonnement pauzeren</label>
			</div>
			<div class="kleistad_col_6">
				<input name="actie" id="kleistad_abo_pauze" class="kleistad_abo_optie" type="checkbox" value="pauze" >
			</div>
		</div>
		<div class="kleistad_abo_pauze"  style="display:none" >
			<div class="kleistad_row" >
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_label kleistad_col_7" >
					<p><strong>Je wilt je abonnement per <?php echo esc_html( $per ); ?> tijdelijk pauzeren</strong></p>
				</div>
			</div>
			<div class="kleistad_row" >
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_label kleistad_col_4" >
					<label for="kleistad_pauze_maanden">aantal maanden pauze</label>
				</div>
				<div class="kleistad_col_3">
					<input name="pauze_maanden" id="kleistad_pauze_maanden" value="1" />
				</div>
			</div>
		</div>
				<?php
		else :
			?>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_4">
				<label for ="kleistad_abo_start">Abonnement hervatten</label>
			</div>
		<div class="kleistad_col_6">
				<input name="actie" id="kleistad_abo_start" class="kleistad_abo_optie" type="checkbox" value="herstart" >
			</div>
		</div>
		<div class="kleistad_abo_start" style="display:none" >
			<div class="kleistad_row" >
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_label kleistad_col_4" >
					<label for="kleistad_startdatum">per</label>
				</div>
				<div class="kleistad_col_3">
					<select naam="startdatum" >
			<?php
			for ( $i = 1; $i <= 3; $i++ ) :
				$datum = mktime( 0, 0, 0, date( 'n' ) + $i, 1, date( 'Y' ) );
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
		endif;
		endif;
		?>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_4">
				<label for="kleistad_abo_einde">Abonnement beëindigen</label>
			</div>
			<div class="kleistad_col_6">
				<input name="actie" id="kleistad_abo_einde" class="kleistad_abo_optie" type="checkbox" value="einde" >
			</div>
		</div>
		<div class="kleistad_row kleistad_abo_einde" style="display:none" >
			<div class="kleistad_col_3" >
				&nbsp;
			</div>
			<div class="kleistad_label kleistad_col_7" >
				<p><strong>Je wilt je abonnement per <?php echo esc_html( $per ); ?> beëindigen</strong></p>
			</div>
		</div>
		<?php
		if ( ! $in_driemaandperiode ) :
			?>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_4">
				<label for="kleistad_abo_betaalwijze">Abonnement betaalwijze</label>
			</div>
			<div class="kleistad_col_6">
				<input name="actie" id="kleistad_abo_betaalwijze" class="kleistad_abo_optie" type="checkbox" value="betaalwijze" >
			</div>
		</div>
		<div class="kleistad_abo_betaalwijze" style="display:none" >
			<div class ="kleistad_row">
				<div class="kleistad_col_10">
					<input type="radio" name="betaal" id="kleistad_betaal_ideal" class="kleistad_input_cbr" value="ideal" checked />
					<label class="kleistad_label_cbr" for="kleistad_betaal_ideal">Ik betaal € 0.01 en machtig daarmee Kleistad om voortaan het abonnement maandelijks af te schrijven van mijn bankrekening</label>
				</div>
			</div>
			<div class="kleistad_row">
				<div class="kleistad_col_10">
					<?php Kleistad_Betalen::issuers(); ?>
				</div>
			</div>
			<div class ="kleistad_row">
				<div class="kleistad_col_10">
					<input type="radio" name="betaal" id="kleistad_betaal_stort" class="kleistad_input_cbr" required value="stort" />
					<label class="kleistad_label_cbr" for="kleistad_betaal_stort">Ik ga voortaan via een bank storting betalen.</label>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<div class="kleistad_row">
			<div class="kleistad_col_10">
				<input type="hidden" name="kleistad_submit_abonnee_wijziging" value="0" >
				<button name="kleistad_check_abonnee_wijziging" id="kleistad_check_abonnee_wijziging" >Aanpassen</button>
			</div>
		</div>
	</form>
	<div id="kleistad_confirm" title="Abonnement wijziging">
	</div>
		
<?php endif ?>