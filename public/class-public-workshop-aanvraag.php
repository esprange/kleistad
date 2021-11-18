<?php
/**
 * Shortcode workshop aanvraag.
 *
 * @link       https://www.kleistad.nl
 * @since      5.6.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;

/**
 * De kleistad workshop aanvraag class.
 */
class Public_Workshop_Aanvraag extends ShortcodeForm {

	/**
	 * Prepareer 'workshop_aanvraag' form
	 */
	protected function prepare() {
		if ( ! isset( $this->data['input'] ) ) {
			$this->data = [
				'input' => [
					'naam'           => '',
					'email'          => '',
					'email_controle' => '',
					'contact'        => '',
					'telnr'          => '',
					'omvang'         => '',
					'periode'        => '',
					'vraag'          => '',
				],
			];
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'workshop aanvraag' form
	 *
	 * @return WP_Error|bool
	 *
	 * @since   5.6.0
	 */
	protected function validate() {
		$error               = new WP_Error();
		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'email'          => FILTER_SANITIZE_EMAIL,
				'email_controle' => FILTER_SANITIZE_EMAIL,
				'contact'        => FILTER_SANITIZE_STRING,
				'naam'           => FILTER_SANITIZE_STRING,
				'omvang'         => FILTER_SANITIZE_STRING,
				'periode'        => FILTER_SANITIZE_STRING,
				'telnr'          => FILTER_SANITIZE_STRING,
				'vraag'          => FILTER_SANITIZE_STRING,
			]
		);
		if ( ! $this->validator->email( $this->data['input']['email'] ) ) {
			$error->add( 'verplicht', 'De invoer ' . $this->data['input']['email'] . ' is geen geldig E-mail adres.' );
			$this->data['input']['email']          = '';
			$this->data['input']['email_controle'] = '';
		}
		if ( 0 !== strcasecmp( $this->data['input']['email_controle'], $this->data['input']['email'] ) ) {
			$error->add( 'verplicht', "De ingevoerde e-mail adressen {$this->data['input']['email']} en {$this->data['input']['email_controle']} zijn niet identiek" );
			$this->data['input']['email_controle'] = '';
		}
		if ( ! empty( $this->data['input']['telnr'] ) && ! $this->validator->telnr( $this->data['input']['telnr'] ) ) {
			$error->add( 'onjuist', "Het ingevoerde telefoonnummer {$this->data['input']['telnr']} lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven" );
			$this->data['input']['telnr'] = '';
		}
		if ( ! $this->validator->naam( $this->data['input']['contact'] ) ) {
			$error->add( 'verplicht', 'De naam van de contactpersooon (een of meer alfabetische karakters) is verplicht' );
			$this->data['input']['contact'] = '';
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 *
	 * Bewaar 'workshop_aanvraag' form gegevens
	 *
	 * @return array
	 *
	 * @since   5.6.0
	 */
	protected function save() : array {
		$workshopaanvraag = new WorkshopAanvraag();
		$workshopaanvraag->start( $this->data['input'] );
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Dank voor de aanvraag! Je krijgt een email ter bevestiging en er wordt spoedig contact met je opgenomen' ),
		];
	}

}
