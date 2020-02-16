<?php
/**
 * Shortcode contact form.
 *
 * @link       https://www.kleistad.nl
 * @since      6.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad cursus inschrijving class.
 */
class Public_Contact extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'contact' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   6.3.0
	 */
	protected function prepare( &$data ) {
		if ( ! isset( $data['input'] ) ) {
			$data          = [];
			$data['input'] = [
				'naam'      => '',
				'email'     => '',
				'telnr'     => '',
				'onderwerp' => '',
				'vraag'     => '',
			];
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'cursus_inschrijving' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   6.3.0
	 */
	protected function validate( &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'email'     => FILTER_SANITIZE_EMAIL,
				'naam'      => FILTER_SANITIZE_STRING,
				'telnr'     => FILTER_SANITIZE_STRING,
				'onderwerp' => FILTER_SANITIZE_STRING,
				'vraag'     => FILTER_SANITIZE_STRING,
			]
		);
		return true;
	}

	/**
	 *
	 * Bewaar 'contact' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_Error|array
	 *
	 * @since   6.3.0
	 */
	protected function save( $data ) {
		$emailer = new \Kleistad\Email();
		$emailer->send(
			[
				'to'         => 'Kleistad <info@' . \Kleistad\Email::domein() . '>',
				'from'       => 'no_reply@' . \Kleistad\Email::verzend_domein(),
				'cc'         => [ $data['input']['email'] ],
				'slug'       => 'contact_vraag',
				'subject'    => 'Vraag over ' . $data['input']['onderwerp'],
				'parameters' => [
					'naam'     => $data['input']['naam'],
					'vraag'    => $data['input']['vraag'],
					'telefoon' => $data['input']['telnr'],
					'email'    => $data['input']['email'],
				],
			]
		);
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Jouw vraag is ontvangen en er wordt spoedig contact met je opgenomen' ),
		];
	}

}