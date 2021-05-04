<?php
/**
 * Shortcode dagdelenkaart.
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;

/**
 * De kleistad dagdelenkaart class.
 */
class Public_Dagdelenkaart extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'dagdelenkaart' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.3.0
	 */
	protected function prepare( array &$data ) {
		if ( ! isset( $data['input'] ) ) {
			$data          = [];
			$data['input'] = [
				'user_email'      => '',
				'email_controle'  => '',
				'first_name'      => '',
				'last_name'       => '',
				'straat'          => '',
				'huisnr'          => '',
				'pcode'           => '',
				'plaats'          => '',
				'telnr'           => '',
				'start_datum'     => '',
				'opmerking'       => '',
				'betaal'          => 'ideal',
				'mc4wp-subscribe' => '0',
			];
		}
		$atts               = shortcode_atts(
			[ 'verklaring' => '' ],
			$this->atts,
			'kleistad_dagdelenkaart'
		);
		$data['verklaring'] = htmlspecialchars_decode( $atts['verklaring'] );
		return true;
	}

	/**
	 * Valideer/sanitize 'dagdelenkaart' form
	 *
	 * @param array $data gevalideerde data.
	 * @return WP_Error|bool
	 *
	 * @since   4.3.0
	 */
	protected function validate( array &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'gebruiker_id'    => FILTER_SANITIZE_NUMBER_INT,
				'user_email'      => FILTER_SANITIZE_EMAIL,
				'email_controle'  => FILTER_SANITIZE_EMAIL,
				'first_name'      => FILTER_SANITIZE_STRING,
				'last_name'       => FILTER_SANITIZE_STRING,
				'straat'          => FILTER_SANITIZE_STRING,
				'huisnr'          => FILTER_SANITIZE_STRING,
				'pcode'           => FILTER_SANITIZE_STRING,
				'plaats'          => FILTER_SANITIZE_STRING,
				'telnr'           => FILTER_SANITIZE_STRING,
				'start_datum'     => FILTER_SANITIZE_STRING,
				'opmerking'       => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_FLAG_STRIP_LOW,
				],
				'betaal'          => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe' => FILTER_SANITIZE_STRING,
			]
		);
		if ( is_array( $data['input'] ) ) {
			if ( '' === $data['input']['start_datum'] ) {
				return new WP_Error( 'verplicht', 'Er is nog niet aangegeven wanneer de dagdelenkaart moet ingaan' );
			}
			if ( 0 === intval( $data['input']['gebruiker_id'] ) ) {
				$error = $this->validator->gebruiker( $data['input'] );
				if ( is_wp_error( $error ) ) {
					return $error;
				}
			}
			return true;
		}
		return new WP_Error( 'input', 'geen juiste data ontvangen' );
	}

	/**
	 * Bewaar 'dagdelenkaart' form gegevens
	 *
	 * @param array $data te bewaren saved.
	 * @return WP_Error|array
	 * @suppressWarnings(PHPMD.StaticAccess)
	 *
	 * @since   4.3.0
	 */
	protected function save( array $data ) : array {
		$gebruiker_id = Gebruiker::registreren( $data['input'] );
		if ( ! is_int( $gebruiker_id ) ) {
			return [ 'status' => $this->status( new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het later opnieuw' ) ) ];
		}
		$dagdelenkaart = new Dagdelenkaart( $gebruiker_id );
		$dagdelenkaart->nieuw( strtotime( $data['input']['start_datum'] ), $data['input']['opmerking'] );

		if ( 'ideal' === $data['input']['betaal'] ) {
			$ideal_uri = $dagdelenkaart->betaling->doe_ideal( 'Bedankt voor de betaling! Een dagdelenkaart is aangemaakt en kan bij Kleistad opgehaald worden', opties()['dagdelenkaart'] );
			if ( ! empty( $ideal_uri ) ) {
				return [ 'redirect_uri' => $ideal_uri ];
			}
			return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		if ( ! $dagdelenkaart->verzend_email( '_bank', $dagdelenkaart->bestel_order( 0.0, $dagdelenkaart->start_datum ) ) ) {
			return [
				'status' => $this->status( new WP_Error( '', 'Een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' ) ),
			];
		}
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Er is een email verzonden met nadere informatie over de betaling' ),
		];
	}
}
