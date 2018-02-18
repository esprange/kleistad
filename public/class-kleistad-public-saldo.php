<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Saldo extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'saldo' form
	 *
	 * @param array $data the prepared data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$gebruiker_id = get_current_user_id();
		$saldo = number_format( (float) get_user_meta( $gebruiker_id, 'stooksaldo', true ), 2, ',', '' );
		$data = [
			'gebruiker_id' => $gebruiker_id,
			'saldo' => $saldo,
		];
		return true;
	}

	/**
	 * Valideer/sanitize 'saldo' form
	 *
	 * @param array $data Returned data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function validate( &$data ) {

		$gebruiker_id = filter_input( INPUT_POST, 'kleistad_gebruiker_id', FILTER_SANITIZE_NUMBER_INT );
		$via = filter_input( INPUT_POST, 'kleistad_via', FILTER_SANITIZE_STRING );
		$bedrag = filter_input( INPUT_POST, 'kleistad_bedrag', FILTER_SANITIZE_NUMBER_FLOAT );
		$datum = strftime( '%d-%m-%Y', strtotime( filter_input( INPUT_POST, 'kleistad_datum', FILTER_SANITIZE_STRING ) ) );

		$data = [
			'gebruiker_id' => $gebruiker_id,
			'via' => $via,
			'bedrag' => $bedrag,
			'datum' => $datum,
		];
		return true;
	}

	/**
	 * Bewaar 'saldo' form gegevens
	 *
	 * @param array $data the data to be saved.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		$gebruiker = get_userdata( $data['gebruiker_id'] );

		$to = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
		if ( self::compose_email(
			$to, 'wijziging stooksaldo', 'kleistad_email_saldo_wijziging', [
				'datum' => $data['datum'],
				'via' => $data['via'],
				'bedrag' => $data['bedrag'],
				'voornaam' => $gebruiker->first_name,
				'achternaam' => $gebruiker->last_name,
			]
		) ) {
			$huidig = (float) get_user_meta( $data['gebruiker_id'], 'stooksaldo', true );
			$saldo = $data['bedrag'] + $huidig;
			update_user_meta( $gebruiker->ID, 'stooksaldo', $saldo );
			Kleistad_Oven::log_saldo( "wijziging saldo $gebruiker->display_name van $huidig naar $saldo, betaling per {$data['via']}." );
			return "Het saldo is bijgewerkt naar &euro; $saldo en een email is verzonden.";
		} else {
			$error->add( '', 'Er is een fout opgetreden want de email kon niet verzonden worden' );
			return $error;
		}
	}

}
