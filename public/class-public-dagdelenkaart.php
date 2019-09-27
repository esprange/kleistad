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
	protected function prepare( &$data = null ) {
		if ( is_null( $data ) ) {
			$data          = [];
			$data['input'] = [
				'EMAIL'           => '',
				'email_controle'  => '',
				'FNAME'           => '',
				'LNAME'           => '',
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
	 * @return \WP_Error|bool
	 *
	 * @since   4.3.0
	 */
	protected function validate( &$data ) {
		$error         = new \WP_Error();
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'gebruiker_id'    => FILTER_SANITIZE_NUMBER_INT,
				'EMAIL'           => FILTER_SANITIZE_EMAIL,
				'email_controle'  => FILTER_SANITIZE_EMAIL,
				'FNAME'           => FILTER_SANITIZE_STRING,
				'LNAME'           => FILTER_SANITIZE_STRING,
				'straat'          => FILTER_SANITIZE_STRING,
				'huisnr'          => FILTER_SANITIZE_STRING,
				'pcode'           => FILTER_SANITIZE_STRING,
				'plaats'          => FILTER_SANITIZE_STRING,
				'telnr'           => FILTER_SANITIZE_STRING,
				'start_datum'     => FILTER_SANITIZE_STRING,
				'opmerking'       => FILTER_SANITIZE_STRING,
				'betaal'          => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe' => FILTER_SANITIZE_STRING,
			]
		);

		if ( '' === $data['input']['start_datum'] ) {
			$error->add( 'verplicht', 'Er is nog niet aangegeven wanneer de dagdelenkaart moet ingaan' );
		}
		if ( 0 === intval( $data['input']['gebruiker_id'] ) ) {
			$this->validate_gebruiker( $error, $data['input'] );
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Bewaar 'dagdelenkaart' form gegevens
	 *
	 * @param array $data te bewaren saved.
	 * @return \WP_Error|array
	 *
	 * @since   4.3.0
	 */
	protected function save( $data ) {
		$error = new \WP_Error();

		if ( ! is_user_logged_in() ) {
			$gebruiker_id = email_exists( $data['input']['EMAIL'] );
			$gebruiker_id = Public_Main::upsert_user(
				[
					'ID'         => ( false !== $gebruiker_id ) ? $gebruiker_id : null,
					'first_name' => $data['input']['FNAME'],
					'last_name'  => $data['input']['LNAME'],
					'telnr'      => $data['input']['telnr'],
					'user_email' => $data['input']['EMAIL'],
					'straat'     => $data['input']['straat'],
					'huisnr'     => $data['input']['huisnr'],
					'pcode'      => $data['input']['pcode'],
					'plaats'     => $data['input']['plaats'],
				]
			);
		} else {
			$gebruiker_id = get_current_user_id();
		}

		if ( is_int( $gebruiker_id ) && 0 < $gebruiker_id ) {
			$dagdelenkaart              = new \Kleistad\Dagdelenkaart( $gebruiker_id );
			$dagdelenkaart->opmerking   = $data['input']['opmerking'];
			$dagdelenkaart->start_datum = strtotime( $data['input']['start_datum'] );
			$dagdelenkaart->save();

			if ( 'ideal' === $data['input']['betaal'] ) {
				$dagdelenkaart->betalen(
					'Bedankt voor de betaling! Een dagdelenkaart is aangemaakt en kan bij Kleistad opgehaald worden'
				);
			} else {
				if ( $dagdelenkaart->email( '_bank' ) ) {
					return [
						'status'  => 'Er is een email verzonden met nadere informatie over de betaling',
						'vervolg' => 'home',
					];
				} else {
					$error->add( '', 'Een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' );
					return $error;
				}
			}
		} else {
			$error->add( '', 'Gegevens konden niet worden opgeslagen. Neem s.v.p. contact op met Kleistad.' );
			return $error;
		}
	}
}
