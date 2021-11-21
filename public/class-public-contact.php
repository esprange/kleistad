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
	 * @return string
	 *
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	protected function prepare() : string {
		if ( ! isset( $this->data['input'] ) ) {
			$this->data = [
				'input' => [
					'naam'      => '',
					'email'     => '',
					'telnr'     => '',
					'onderwerp' => '',
					'vraag'     => '',
				],
			];
			$gebruiker  = wp_get_current_user();
			if ( $gebruiker->exists() ) {
				$this->data['input']['naam']  = $gebruiker->display_name;
				$this->data['input']['email'] = $gebruiker->user_email;
				$this->data['input']['telnr'] = $gebruiker->telnr;
			}
		}
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'contact' form
	 *
	 * @since   6.3.0
	 *
	 * @return array
	 */
	protected function process() : array {
		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'email'     => FILTER_SANITIZE_EMAIL,
				'naam'      => FILTER_SANITIZE_STRING,
				'telnr'     => FILTER_SANITIZE_STRING,
				'onderwerp' => FILTER_SANITIZE_STRING,
				'vraag'     => FILTER_SANITIZE_STRING,
			]
		);
		return $this->save();
	}

	/**
	 *
	 * Bewaar 'contact' form gegevens
	 *
	 * @return array
	 *
	 * @since   6.3.0
	 */
	protected function save() : array {
		$emailer          = new Email();
		$email_parameters = [
			'to'         => "Kleistad <$emailer->info@$emailer->domein>",
			'from'       => "$emailer->info@$emailer->verzend_domein",
			'from_name'  => $this->data['input']['naam'],
			'reply-to'   => $this->data['input']['email'],
			'slug'       => 'contact_vraag',
			'subject'    => 'Vraag over ' . $this->data['input']['onderwerp'],
			'auto'       => false,
			'sign'       => '',
			'parameters' => [
				'naam'     => $this->data['input']['naam'],
				'vraag'    => $this->data['input']['vraag'],
				'telefoon' => $this->data['input']['telnr'],
				'email'    => $this->data['input']['email'],
			],
		];
		$emailer->send( $email_parameters );

		$email_parameters['to']                   = $this->data['input']['email'];
		$email_parameters['from_name']            = 'Kleistad';
		$email_parameters['reply-to']             = "Kleistad <$emailer->info@$emailer->domein>";
		$email_parameters['parameters']['vraag'] .= '<br/><p>Bedankt voor de vraag, wij proberen die snel te beantwoorden.</p><br/>';
		$emailer->send( $email_parameters );
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Jouw vraag is ontvangen en er wordt spoedig contact met je opgenomen' ),
		];
	}

}
