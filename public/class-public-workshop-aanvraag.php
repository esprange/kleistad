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
use WP_REST_Response;

/**
 * De kleistad workshop aanvraag class.
 */
class Public_Workshop_Aanvraag extends Public_Bestelling {

	/**
	 * Prepareer 'workshop_aanvraag' form
	 *
	 * @return string
	 */
	protected function prepare() : string {
		if ( ! isset( $this->data['input'] ) ) {
			$this->data['input'] = [
				'naam'           => '',
				'user_email'     => '',
				'email_controle' => '',
				'contact'        => '',
				'telnr'          => '',
				'aantal'         => '4',
				'datum'          => '',
				'dagdeel'        => '',
				'technieken'     => [],
				'opmerking'      => '',
			];
		}
		$planning = new Workshopplanning();
		if ( 0 === count( $planning->get_beschikbaarheid() ) ) {
			return $this->status(
				new WP_Error(
					'Aanvraag',
					sprintf( 'Helaas is er de komende %d weken geen ruimte beschikbaar. Probeer het later opnieuw', opties()['weken workshop'] )
				)
			);
		}
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'workshop aanvraag' form
	 *
	 * @return array
	 * @since   5.6.0
	 */
	public function process(): array {
		$error               = new WP_Error();
		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'user_email'     => FILTER_SANITIZE_EMAIL,
				'email_controle' => FILTER_SANITIZE_EMAIL,
				'contact'        => FILTER_SANITIZE_STRING,
				'naam'           => FILTER_SANITIZE_STRING,
				'aantal'         => FILTER_SANITIZE_NUMBER_INT,
				'telnr'          => FILTER_SANITIZE_STRING,
				'opmerking'      => FILTER_SANITIZE_STRING,
				'datum'          => FILTER_SANITIZE_STRING,
				'dagdeel'        => FILTER_SANITIZE_STRING,
				'technieken'     => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_REQUIRE_ARRAY,
					'options' => [ 'default' => [] ],
				],
			]
		);
		if ( is_null( $this->data['input']['technieken'] ) ) {
			$this->data['input']['technieken'] = [];
		}
		if ( ! $this->validator->email( $this->data['input']['user_email'] ) ) {
			$error->add( 'verplicht', 'De invoer ' . $this->data['input']['user_email'] . ' is geen geldig E-mail adres.' );
			$this->data['input']['user_email']     = '';
			$this->data['input']['email_controle'] = '';
		}
		if ( 0 !== strcasecmp( $this->data['input']['email_controle'], $this->data['input']['user_email'] ) ) {
			$error->add( 'verplicht', "De ingevoerde e-mail adressen {$this->data['input']['user_email']} en {$this->data['input']['email_controle']} zijn niet identiek" );
			$this->data['input']['email_controle'] = '';
		}
		if ( ! $this->validator->telnr( $this->data['input']['telnr'] ) ) {
			$error->add( 'onjuist', "Het ingevoerde telefoonnummer {$this->data['input']['telnr']} lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven" );
			$this->data['input']['telnr'] = '';
		}
		if ( ! $this->validator->naam( $this->data['input']['contact'] ) ) {
			$error->add( 'verplicht', 'De naam van de contactpersooon (een of meer alfabetische karakters) is verplicht' );
			$this->data['input']['contact'] = '';
		}
		if ( false === strtotime( $this->data['input']['datum'] ) ) {
			$error->add( 'onjuist', 'De gekozen datum voor de workshop is niet correct' );
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $this->melding( $error );
		}
		$this->data['input']['datum'] = strtotime( "{$this->data['input']['datum']} 0:00" );
		return $this->save();
	}

	/**
	 *
	 * Bewaar 'workshop_aanvraag' form gegevens
	 *
	 * @return array
	 *
	 * @since   5.6.0
	 */
	protected function save(): array {
		$workshop = new Workshop();
		$workshop->actie->aanvraag( $this->data['input'] );
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Dank voor de aanvraag! Je krijgt een email ter bevestiging en er wordt spoedig contact met je opgenomen' ),
		];
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 7.0.0
	 */
	public static function register_rest_routes() : void {
		register_rest_route(
			KLEISTAD_API,
			'/aanvraag',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_aanvraag' ],
				'permission_callback' => function() {
					return true;
				},
			]
		);
	}

	/**
	 * Haal de mogelijke plandata op.
	 *
	 * @return WP_REST_Response
	 */
	public static function callback_aanvraag() : WP_REST_Response {
		$planning = new Workshopplanning();
		return new WP_REST_Response( [ 'plandata' => $planning->get_beschikbaarheid() ] );
	}
}
