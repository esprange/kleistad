<?php
/**
 * De  abstracte class voor shortcodes.
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.11
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * De abstract class voor shortcodes
 */
abstract class Kleistad_ShortcodeForm extends Kleistad_ShortCode {
	/**
	 * Validatie functie, wordt voor form validatie gebruikt
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data.
	 * @return \WP_ERROR|bool
	 */
	abstract public function validate( &$data );

	/**
	 * Save functie, wordt gebruikt bij formulieren
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data die kan worden opgeslagen.
	 * @return \WP_ERROR|string
	 */
	abstract public function save( $data );

	/**
	 * Controleer of er betaald is en geef dan een melding.
	 *
	 * @since  4.5.1
	 * @return string html tekst.
	 */
	protected function betaald() {
		$html    = '';
		$betaald = filter_input( INPUT_GET, 'betaald' );
		if ( ! is_null( $betaald ) ) {
			$gebruiker_id = filter_input( INPUT_GET, 'betaald' );
			$betaling     = new Kleistad_Betalen();
			$result       = $betaling->controleer( $gebruiker_id );
			if ( ! is_wp_error( $result ) ) {
				$html .= '<div class="kleistad_succes"><p>' . $result . '</p></div>';
			} else {
				$html .= '<div class="kleistad_fout"><p>' . $result->get_error_message() . '</p></div>';
			}
		}
		return $html;
	}

	/**
	 * Verwerk de formulier invoer
	 *
	 * @since 4.5.1
	 *
	 * @param  array $data de uit te wisselen data.
	 * @return string html tekst.
	 */
	protected function process( &$data ) {
		$html               = '';
		$data['form_actie'] = filter_input( INPUT_POST, 'kleistad_submit_' . $this->shortcode );
		if ( ! is_null( $data['form_actie'] ) ) {
			if ( wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'kleistad_' . $this->shortcode ) ) {
				$result = $this->validate( $data );
				if ( ! is_wp_error( $result ) ) {
					$result = $this->save( $data );
				}
				if ( ! is_wp_error( $result ) ) {
					$html .= '<div class="kleistad_succes"><p>' . $result . '</p></div>';
					$data  = null;
				} else {
					foreach ( $result->get_error_messages() as $error ) {
						$html .= '<div class="kleistad_fout"><p>' . $error . '</p></div>';
					}
				}
			} else {
				$html .= '<div class="kleistad_fout"><p>security fout</p></div>';
			}
		}
		return $html;
	}

	/**
	 * Voer het rapport van de shortcode uit.
	 *
	 * @since 4.5.1
	 */
	public function run() {
		$data = [];
		$html = $this->betaald();
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$html .= $this->process( $data );
			$html .= $this->display( $data );
		} else {
			$html .= $this->display();
		}
		return $html;
	}
}
