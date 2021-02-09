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

$this->form();
?>
	<div class="kleistad-row">
		<div class="kleistad-col-3">
			<label class="kleistad-label">Keuze abonnement</label>
		</div>
		<div class="kleistad-col-3">
			<input name="abonnement_keuze" id="kleistad_onbeperkt" type="radio" checked required
				data-bedrag="<?php echo esc_attr( 3 * $this->options['onbeperkt_abonnement'] ); ?>"
				data-bedragtekst="= 3 termijnen"
				value="onbeperkt" <?php checked( 'onbeperkt', $data['input']['abonnement_keuze'] ); ?> />
			<label for="kleistad_onbeperkt" >
				Onbeperkt<br/>(€ <?php echo esc_html( number_format_i18n( $this->options['onbeperkt_abonnement'], 2 ) ); ?> p.m.)
			</label>
		</div>
		<div class="kleistad-col-1">
		</div>
		<div class="kleistad-col-3">
			<input name="abonnement_keuze" id="kleistad_beperkt" type="radio" required
				data-bedrag="<?php echo esc_attr( 3 * $this->options['beperkt_abonnement'] ); ?>"
				data-bedragtekst="= 3 termijnen"
				value="beperkt" <?php checked( 'beperkt', $data['input']['abonnement_keuze'] ); ?> />
			<label for="kleistad_beperkt">
				Beperkt<br/>(€ <?php echo esc_html( number_format_i18n( $this->options['beperkt_abonnement'], 2 ) ); ?> p.m.)
			</label>
		</div>
	</div>
	<div class="kleistad-row" id="kleistad_dag" style="visibility:hidden" title="kies de dag dat je van jouw beperkt abonnement gebruikt gaat maken" >
		<div class="kleistad-col-3 kleistad-label">
			<label for="kleistad_dag_keuze">Dag</label>
		</div>
		<div class="kleistad-col-7">
			<select class="kleistad-input" name="dag" id="kleistad_dag_keuze" >
				<option value="maandag" <?php selected( $data['input']['dag'], 'maandag' ); ?> >Maandag</option>
				<option value="dinsdag" <?php selected( $data['input']['dag'], 'dinsdag' ); ?>>Dinsdag</option>
				<option value="woensdag" <?php selected( $data['input']['dag'], 'woensdag' ); ?>>Woensdag</option>
				<option value="donderdag" <?php selected( $data['input']['dag'], 'donderdag' ); ?>>Donderdag</option>
				<option value="vrijdag" <?php selected( $data['input']['dag'], 'vrijdag' ); ?>>Vrijdag</option>
			</select>
		</div>
	</div>
	<div class="kleistad-row">
		<div class="kleistad-col-3 kleistad-label">
			<label for="kleistad_start_datum">Start per</label>
		</div>
		<div class="kleistad-col-3 kleistad-input">
			<input class="kleistad-datum kleistad-input" name="start_datum" id="kleistad_start_datum" type="text" required value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
		</div>
	</div>
	<?php
	if ( is_super_admin() ) :
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_gebruiker_id">Abonnee</label>
			</div>
			<div class="kleistad-col-7">
				<select class="kleistad-input" name="gebruiker_id" id="kleistad_gebruiker_id" >
					<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
						<option value="<?php echo esc_attr( $gebruiker->ID ); ?>"><?php echo esc_html( $gebruiker->display_name ); ?></option>
					<?php endforeach ?>
				</select>
			</div>
		</div>
		<?php
	else :
		require plugin_dir_path( dirname( __FILE__ ) ) . '/partials/public-gebruiker.php';
	endif // If user is admin.
	?>
	<div class ="kleistad-row">
		<div class="kleistad-col-10">
			<input type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" <?php checked( $data['input']['betaal'], 'ideal' ); ?> />
			<label for="kleistad_betaal_ideal"></label>
		</div>
	</div>
	<div class="kleistad-row">
		<div class="kleistad-col-10">
			<?php Betalen::issuers(); ?>
		</div>
	</div>
	<div class ="kleistad-row">
		<div class="kleistad-col-10">
			<input type="radio" name="betaal" id="kleistad_betaal_stort" required value="stort" <?php checked( $data['input']['betaal'], 'stort' ); ?> />
			<label for="kleistad_betaal_stort"></label>
		</div>
	</div>

	<div class="kleistad-row" style="padding-top: 20px;">
		<div class="kleistad-col-10">
			<button name="kleistad_submit_abonnee_inschrijving" id="kleistad_submit" type="submit" <?php disabled( ! is_super_admin() && '' !== $data['verklaring'] ); ?>>Betalen</button>
		</div>
	</div>
</form>
