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

if ( isset( $data['actie'] ) ) :

	if ( Kleistad_Public_Betaling::ACTIE_RESTANT_CURSUS === $data['actie'] ) :
		$inschrijfkosten = $data['cursus']->inschrijfkosten * $data['inschrijving']->aantal;
		$restantkosten   = $data['cursus']->cursuskosten * $data['inschrijving']->aantal;
		$cursuskosten    = $restantkosten + $inschrijfkosten;
		?>

		<form action="<?php echo esc_url( get_permalink() ); ?>" method="POST">
			<?php wp_nonce_field( 'kleistad_betaling' ); ?>
		<input type="hidden" name="cursist_id" value="<?php echo esc_attr( $data['cursist']->ID ); ?>" />
		<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $data['cursus']->id ); ?>" />
		<input type="hidden" name="betaal" value="ideal" />
		<input type="hidden" name="actie" value="<?php echo esc_attr( $data['actie'] ); ?>" />
		<h2>Overzicht betaling cursuskosten</h2>

		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<p>Cursist</p>
			</div>
			<div class="kleistad_col_7">
				<p><?php echo esc_html( $data['cursist']->first_name . ' ' . $data['cursist']->last_name ); ?></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<p>Aantal personen</p>
			</div>
			<div class="kleistad_col_7">
				<p><?php echo esc_html( $data['inschrijving']->aantal ); ?></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<p>Reeds betaald</p>
			</div>
			<div class="kleistad_col_7">
				<p>&euro; <?php echo esc_html( number_format_i18n( $inschrijfkosten, 2 ) ); ?></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<p>Totale cursuskosten</p>
			</div>
			<div class="kleistad_col_7">
				<p>&euro; <?php echo esc_html( number_format_i18n( $cursuskosten, 2 ) ); ?></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<p>&nbsp;</p>
			</div>
			<div class="kleistad_col_7">
				<hr>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<p>Nog te betalen</p>
			</div>
			<div class="kleistad_col_7">
				<p>&euro; <?php echo esc_html( number_format_i18n( $restantkosten, 2 ) ); ?></p>
			</div>
		</div>
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<?php Kleistad_Betalen::issuers(); ?>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_10" style="padding-top: 20px;">
				<button type="submit" name="kleistad_submit_betaling" id="kleistad_submit">Betalen</button><br />
			</div>
		</div>
		</form>
			<?php
	elseif ( Kleistad_Public_Betaling::ACTIE_VERVOLG_ABONNEMENT === $data['actie'] ) :
			$vervolg_datum            = strftime(
				'%d-%m-%y', mktime(
					0, 0, 0,
					date( 'n', $data['abonnement']->start_datum ) + 3,
					date( 'j', $data['abonnement']->start_datum ),
					date( 'Y', $data['abonnement']->start_datum )
				)
			);
			$einde_overbrugging_datum = strftime(
				'%d-%m-%y', mktime(
					0, 0, 0,
					date( 'n', $data['abonnement']->incasso_datum ),
					date( 'j', $data['abonnement']->incasso_datum ) - 1,
					date( 'Y', $data['abonnement']->incasso_datum )
				)
			);
			$options                  = get_option( 'kleistad-opties' );
		?>

		<form action="<?php echo esc_url( get_permalink() ); ?>" method="POST">
			<?php wp_nonce_field( 'kleistad_betaling' ); ?>
		<input type="hidden" name="abonnee_id" value="<?php echo esc_attr( $data['abonnee']->ID ); ?>" />
		<input type="hidden" name="betaal" value="ideal" />
		<input type="hidden" name="actie" value="<?php echo esc_attr( $data['actie'] ); ?>" />
		<h2>Betaling vervolg abonnement</h2>

		<div class="kleistad_row">
			<div class="kleistad_col_4">
				<p>Abonnee</p>
			</div>
			<div class="kleistad_col_6">
				<p><?php echo esc_html( $data['abonnee']->first_name . ' ' . $data['abonnee']->last_name ); ?></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_4">
				<p>Soort abonnement</p>
			</div>
			<div class="kleistad_col_6">
				<p><?php echo esc_html( $data['abonnement']->soort ); ?></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_4">
				<p>Maand bedrag</p>
			</div>
			<div class="kleistad_col_6">
				<p>&euro; <?php echo esc_html( number_format_i18n( $options[ $data['abonnement']->soort . '_abonnement' ], 2 ) ); ?> </p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_4">
				<p>Automatische incasso vanaf</p>
			</div>
			<div class="kleistad_col_6">
				<p><?php echo esc_html( strftime( '%d-%m-%y', $data['abonnement']->incasso_datum ) ); ?></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_4">
				<p>&nbsp;</p>
			</div>
			<div class="kleistad_col_6">
				<hr>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_4">
				<p>Periode thans te betalen</p>
			</div>
			<div class="kleistad_col_6">
				<p><?php echo esc_html( $vervolg_datum . ' - ' . $einde_overbrugging_datum ); ?></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_4">
				<p>Periode bedrag</p>
			</div>
			<div class="kleistad_col_6">
				<p>&euro; <?php echo esc_html( number_format_i18n( $data['abonnement']->overbrugging(), 2 ) ); ?></p>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_10">
				<p><strong>Hierbij machtig ik Kleistad om in het vervolg het abonnementsgeld maandelijks automatisch af te schrijven. Ik kan dit achteraf nog altijd aanpassen.</strong></p>
			</div>
		</div>
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<?php Kleistad_Betalen::issuers(); ?>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_10" style="padding-top: 20px;">
				<button type="submit" name="kleistad_submit_betaling" id="kleistad_submit">Betalen</button><br />
			</div>
		</div>
		</form>
		<?php
	endif;
endif
?>
