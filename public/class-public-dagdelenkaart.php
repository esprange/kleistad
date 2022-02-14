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
	 * @since   4.3.0
	 *
	 * @return string
	 */
	protected function prepare() : string {
		if ( ! isset( $this->data['input'] ) ) {
			$this->data['input'] = [
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
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'dagdelenkaart' form
	 *
	 * @since   4.3.0
	 *
	 * @return array
	 */
	public function process() : array {
		$this->data['input'] = filter_input_array(
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
		if ( is_array( $this->data['input'] ) ) {
			if ( '' === $this->data['input']['start_datum'] ) {
				return $this->melding( new WP_Error( 'verplicht', 'Er is nog niet aangegeven wanneer de dagdelenkaart moet ingaan' ) );
			}
			if ( 0 === intval( $this->data['input']['gebruiker_id'] ) ) {
				$error = $this->validator->gebruiker( $this->data['input'] );
				if ( ! is_bool( $error ) ) {
					return $this->melding( $error );
				}
			}
			return $this->save();
		}
		return $this->melding( new WP_Error( 'input', 'geen juiste data ontvangen' ) );
	}

	/**
	 * Bewaar 'dagdelenkaart' form gegevens
	 *
	 * @return array
	 * @suppressWarnings(PHPMD.StaticAccess)
	 *
	 * @since   4.3.0
	 */
	protected function save() : array {
		$gebruiker_id = Gebruiker::registreren( $this->data['input'] );
		if ( ! is_int( $gebruiker_id ) ) {
			return [ 'status' => $this->status( new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het later opnieuw' ) ) ];
		}
		$dagdelenkaart = new Dagdelenkaart( $gebruiker_id );
		$dagdelenkaart->nieuw( strtotime( $this->data['input']['start_datum'] ), $this->data['input']['opmerking'] );

		if ( 'ideal' === $this->data['input']['betaal'] ) {
			$ideal_uri = $dagdelenkaart->betaling->doe_ideal( 'Bedankt voor de betaling! Een dagdelenkaart is aangemaakt en kan bij Kleistad opgehaald worden', opties()['dagdelenkaart'], $dagdelenkaart->geef_referentie() );
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
