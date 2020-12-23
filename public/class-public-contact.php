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
			$data      = [
				'input' => [
					'naam'      => '',
					'email'     => '',
					'telnr'     => '',
					'onderwerp' => '',
					'vraag'     => '',
				],
			];
			$gebruiker = wp_get_current_user();
			if ( $gebruiker->exists() ) {
				$data['input']['naam']  = $gebruiker->display_name;
				$data['input']['email'] = $gebruiker->user_email;
				$data['input']['telnr'] = $gebruiker->telnr;
			}
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'contact' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return bool
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
	 * @return array
	 *
	 * @since   6.3.0
	 */
	protected function save( $data ) {
		$emailer          = new Email();
		$email_parameters = [
			'to'         => "Kleistad <{$emailer->info}{$emailer->domein}>",
			'from'       => "{$emailer->info}{$emailer->verzend_domein}",
			'from_name'  => $data['input']['naam'],
			'reply-to'   => $data['input']['email'],
			'slug'       => 'contact_vraag',
			'subject'    => 'Vraag over ' . $data['input']['onderwerp'],
			'auto'       => false,
			'sign'       => '',
			'parameters' => [
				'naam'     => $data['input']['naam'],
				'vraag'    => $data['input']['vraag'],
				'telefoon' => $data['input']['telnr'],
				'email'    => $data['input']['email'],
			],
		];
		$emailer->send( $email_parameters );

		$email_parameters['to']                   = $data['input']['email'];
		$email_parameters['from_name']            = 'Kleistad';
		$email_parameters['reply-to']             = "Kleistad <{$emailer->info}{$emailer->domein}>";
		$email_parameters['parameters']['vraag'] .= '<br/><p>Bedankt voor de vraag, wij proberen die snel te beantwoorden.</p><br/>';
		$emailer->send( $email_parameters );
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Jouw vraag is ontvangen en er wordt spoedig contact met je opgenomen' ),
		];
	}

}
