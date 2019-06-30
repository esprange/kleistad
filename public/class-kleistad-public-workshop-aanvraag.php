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

/**
 * De kleistad workshop aanvraag class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Workshop_Aanvraag extends Kleistad_ShortcodeForm {

	/**
	 *
	 * Prepareer 'workshop_aanvraag' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   5.6.0
	 */
	protected function prepare( &$data = null ) {
		if ( ! isset( $data['input'] ) ) {
			$data          = [];
			$data['input'] = [
				'naam'           => '',
				'email'          => '',
				'email_controle' => '',
				'contact'        => '',
				'telnr'          => '',
				'omvang'         => '',
				'periode'        => '',
				'vraag'          => '',
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
	 * @since   5.6.0
	 */
	protected function validate( &$data ) {
		$error          = new WP_Error();
		$data['cursus'] = null;
		$data['input']  = filter_input_array(
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
		if ( ! $this->validate_email( $data['input']['email'] ) ) {
			$error->add( 'verplicht', 'De invoer ' . $data['input']['email'] . ' is geen geldig E-mail adres.' );
			$data['input']['email']          = '';
			$data['input']['email_controle'] = '';
		} else {
			$this->validate_email( $data['input']['email_controle'] );
			if ( $data['input']['email_controle'] !== $data['input']['email'] ) {
				$error->add( 'verplicht', 'De ingevoerde e-mail adressen ' . $data['input']['email'] . ' en ' . $data['input']['email_controle'] . ' zijn niet identiek' );
				$data['input']['email_controle'] = '';
			}
		}
		if ( ! empty( $data['input']['telnr'] ) && ! $this->validate_telnr( $data['input']['telnr'] ) ) {
			$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven' );
		}
		if ( ! $this->validate_naam( $data['input']['contact'] ) ) {
			$error->add( 'verplicht', 'De naam van de contactpersooon (een of meer alfabetische karakters) is verplicht' );
			$data['input']['contact'] = '';
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
	 * @param array $data data te bewaren.
	 * @return string
	 *
	 * @since   5.6.0
	 */
	protected function save( $data ) {
		$error  = new WP_Error();
		$emailer = new Kleistad_Email();
		$result = wp_insert_post(
			[
				'post_type'      => Kleistad_WorkshopAanvraag::POST_TYPE,
				'post_title'     => $data['input']['contact'] . ' met vraag over ' . $data['input']['naam'],
				'post_name'      => $data['input']['email'],
				'post_excerpt'   => maybe_serialize(
					[
						'email'   => $data['input']['email'],
						'naam'    => $data['input']['naam'],
						'contact' => $data['input']['contact'],
						'omvang'  => $data['input']['omvang'],
						'periode' => $data['input']['periode'],
						'telnr'   => $data['input']['telnr'],
					]
				),
				'post_status'    => 'nieuw',
				'comment_status' => 'closed',
				'post_content'   => Kleistad_WorkshopAanvraag::communicatie(
					[
						'tekst' => $data['input']['vraag'],
						'type'  => 'aanvraag',
					]
				),
			]
		);
		if ( ! is_wp_error( $result ) ) {
			$emailer->send(
				[
					'to'         => "{$data['input']['contact']} <{$data['input']['email']}>",
					'subject'    => sprintf( "[WA#%08d] Bevestiging {$data['input']['naam']} aanvraag", $result ),
					'from'       => Kleistad_WorkshopAanvraag::MBX . '@' . Kleistad_Email::verzend_domein(),
					'reply-to'   => Kleistad_WorkshopAanvraag::MBX . '@' . Kleistad_Email::verzend_domein(),
					'slug'       => 'kleistad_email_bevestiging_workshop_aanvraag',
					'message-id' => sprintf( 'workshop_aanvraag_%08d', $result ),
					'parameters' => $data['input'],
				]
			);
			return 'Dank voor de aanvraag! Je krijgt een email ter bevestiging en er wordt spoedig contact met je opgenomen';
		} else {
			$error->add( 'aanvraag', 'Sorry, er is iets fout gegaan, probeer het later nog een keer' );
		}
	}

}
