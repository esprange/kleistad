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

if ( ! is_user_logged_in() || is_super_admin() ) :
	?>

	<form action="<?php echo esc_url( get_permalink() ); ?>" method="POST">
		<?php wp_nonce_field( 'kleistad_abonnee_inschrijving' ); ?>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
				<label class="kleistad_label">Keuze abonnement</label>
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" name="abonnement_keuze" id="kleistad_onbeperkt" type="radio" checked required
					   data-bedrag="<?php echo esc_attr( $data['bedrag_onbeperkt'] ); ?>"
					   value="onbeperkt" <?php checked( 'onbeperkt', $data['input']['abonnement_keuze'] ); ?> />
				<label class="kleistad_label_cbr" for="kleistad_onbeperkt" >Onbeperkt</label>
			</div>
			<div class="kleistad_col_1">
			</div>
			<div class="kleistad_col_3">
				<input class="kleistad_input_cbr" name="abonnement_keuze" id="kleistad_beperkt" type="radio" required
					   data-bedrag="<?php echo esc_attr( $data['bedrag_beperkt'] ); ?>"
					   value="beperkt" <?php checked( 'beperkt', $data['input']['abonnement_keuze'] ); ?> />
				<label class="kleistad_label_cbr" for="kleistad_beperkt">Beperkt</label>
			</div>
		</div>
		<div class="kleistad_row" id="kleistad_dag" style="visibility:hidden" title="kies de dag dat je van jouw beperkt abonnement gebruikt gaat maken" >
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_dag_keuze">Dag</label>
			</div>
			<div class="kleistad_col_7">
				<select class="kleistad_input" name="dag" id="kleistad_dag_keuze" >
					<option value="maandag" <?php selected( $data['input']['dag'], 'maandag' ); ?> >Maandag</option>
					<option value="dinsdag" <?php selected( $data['input']['dag'], 'dinsdag' ); ?>>Dinsdag</option>
					<option value="woensdag" <?php selected( $data['input']['dag'], 'woensdag' ); ?>>Woensdag</option>
					<option value="donderdag" <?php selected( $data['input']['dag'], 'donderdag' ); ?>>Donderdag</option>
					<option value="vrijdag" <?php selected( $data['input']['dag'], 'vrijdag' ); ?>>Vrijdag</option>
				</select>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_start_datum">Start per</label>
			</div>
			<div class="kleistad_col_7 kleistad_input">
				<input class="kleistad_datum, kleistad_input" name="start_datum" id="kleistad_start_datum" type="text" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>" />
			</div>
		</div>
		<?php if ( is_super_admin() ) : ?>
			<div class="kleistad_row">
				<div class="kleistad_col_3 kleistad_label">
					<label for="kleistad_gebruiker_id">Abonnee</label>
				</div>
				<div class="kleistad_col_7">
					<select class="kleistad_input" name="gebruiker_id" id="kleistad_gebruiker_id" >
						<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
							<option value="<?php echo esc_attr( $gebruiker->id ); ?>"><?php echo esc_html( $gebruiker->display_name ); ?></option>
						<?php endforeach ?>
					</select>
				</div>
			</div>
		<?php
		else :
			require plugin_dir_path( dirname( __FILE__ ) ) . '/partials/kleistad-public-gebruiker.php';
			?>
		<?php endif ?>
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<input type="radio" name="betaal" id="kleistad_betaal_ideal" class="kleistad_input_cbr" value="ideal" <?php checked( $data['input']['betaal'], 'ideal' ); ?> />
				<label class="kleistad_label_cbr" for="kleistad_betaal_ideal"></label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_10">
				<?php Kleistad_Betalen::issuers(); ?>
			</div>
		</div>
		<div class ="kleistad_row">
			<div class="kleistad_col_10">
				<input type="radio" name="betaal" id="kleistad_betaal_stort" class="kleistad_input_cbr" required value="stort" <?php checked( $data['input']['betaal'], 'stort' ); ?> />
				<label class="kleistad_label_cbr" for="kleistad_betaal_stort"></label>
			</div>
		</div>

		<div class="kleistad_row" style="padding-top: 20px;">
			<div class="kleistad_col_10">
				<button name="kleistad_submit_abonnee_inschrijving" id="kleistad_submit" type="submit" <?php disabled( ! is_super_admin() && '' !== $data['verklaring'] ); ?>>Betalen</button>
			</div>
		</div>
	</form>
<?php endif ?>
