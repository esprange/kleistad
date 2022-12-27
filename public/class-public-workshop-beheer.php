<?php
/**
 * Shortcode workshop.
 *
 * @link       https://www.kleistad.nl
 * @since      5.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;

/**
 * De kleistad workshop class.
 */
class Public_Workshop_Beheer extends ShortcodeForm {

	/**
	 * Prepareer 'input' form voor toevoegen.
	 *
	 * @return string
	 */
	protected function prepare_toevoegen() : string {
		/*
		* Er moet een nieuwe workshop opgevoerd worden, zelfde als wijzigen maar dan zonder id.
		*/
		$this->data['id'] = 0;
		return $this->prepare_wijzigen();
	}

	/**
	 * Prepareer 'input' form voor wijzigen.
	 *
	 * @return string
	 */
	protected function prepare_wijzigen() : string {
		/*
		* Er is een workshop gekozen om te wijzigen.
		*/
		$this->data['docenten'] = new Docenten();
		if ( ! isset( $this->data['workshop'] ) ) {
			$this->data['workshop'] = new Workshop( $this->data['id'] );
		}
		return $this->content();
	}

	/**
	 * Prepareer het standaard scherm
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		/**
		 * De workshopaanvragen en de geplande workshops moeten worden getoond.
		 */
		$this->data['workshops'] = new Workshops();
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'workshop_beheer' form
	 *
	 * @since   5.0.0
	 *
	 * @return array
	 */
	public function process() : array {
		$error                              = new WP_Error();
		$this->data['input']                = filter_input_array(
			INPUT_POST,
			[
				'workshop_id'       => FILTER_SANITIZE_NUMBER_INT,
				'reactie'           => FILTER_SANITIZE_STRING,
				'naam'              => FILTER_SANITIZE_STRING,
				'datum'             => FILTER_SANITIZE_STRING,
				'start_tijd'        => FILTER_SANITIZE_STRING,
				'eind_tijd'         => FILTER_SANITIZE_STRING,
				'docent'            => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_REQUIRE_ARRAY,
					'options' => [ 'default' => [] ],
				],
				'technieken'        => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_REQUIRE_ARRAY,
					'options' => [ 'default' => [] ],
				],
				'werkplekken'       => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_REQUIRE_ARRAY,
					'options' => [ 'default' => [] ],
				],
				'organisatie'       => FILTER_SANITIZE_STRING,
				'organisatie_adres' => FILTER_SANITIZE_STRING,
				'organisatie_email' => FILTER_SANITIZE_EMAIL,
				'contact'           => FILTER_SANITIZE_STRING,
				'email'             => FILTER_SANITIZE_EMAIL,
				'telnr'             => FILTER_SANITIZE_STRING,
				'vervallen'         => FILTER_VALIDATE_BOOLEAN,
				'kosten'            => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'aantal'            => FILTER_SANITIZE_NUMBER_INT,
				'definitief'        => FILTER_VALIDATE_BOOLEAN,
				'betaald'           => FILTER_VALIDATE_BOOLEAN,
				'programma'         => FILTER_DEFAULT,
				'aanvraag_id'       => FILTER_SANITIZE_NUMBER_INT,
			]
		);
		$this->data['input']['workshop_id'] = intval( $this->data['input']['workshop_id'] );
		if ( 'reageren' === $this->form_actie ) {
			if ( empty( $this->data['input']['reactie'] ) ) {
				return $this->melding( new WP_Error( 'reactie', 'Er is nog geen reactie ingevoerd!' ) );
			}
			return $this->save();
		}
		$this->data['input']['programma'] = sanitize_textarea_field( $this->data['input']['programma'] );
		if ( is_null( $this->data['input']['technieken'] ) ) {
			$this->data['input']['technieken'] = [];
		}
		if ( is_null( $this->data['input']['werkplekken'] ) ) {
			$this->data['input']['werkplekken'] = [];
		}
		$this->data['input']['docent'] = implode( ';', $this->data['input']['docent'] ?? [] );
		if ( in_array( $this->form_actie, [ 'bewaren', 'bevestigen' ], true ) ) {
			if ( ! $this->validator->email( $this->data['input']['email'] ) ) {
				$error->add( 'verplicht', 'De invoer ' . $this->data['input']['email'] . ' is geen geldig E-mail adres.' );
			}
			if ( ! $this->validator->telnr( $this->data['input']['telnr'] ) ) {
				$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven' );
			}
			if ( strtotime( $this->data['input']['start_tijd'] ) >= strtotime( $this->data['input']['eind_tijd'] ) ) {
				$error->add( 'Invoerfout', 'De starttijd moet voor de eindtijd liggen' );
			}
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $this->melding( $error );
		}
		return $this->save();
	}

	/**
	 * Schrijf workshop informatie naar het bestand.
	 */
	protected function workshops() {
		$workshops = new Workshops();
		fputcsv(
			$this->filehandle,
			[
				'code',
				'naam',
				'datum',
				'starttijd',
				'eindtijd',
				'docent',
				'technieken',
				'aantal',
				'kosten',
				'status',
				'organisatie',
				'organisatie_adres',
				'organisatie_email',
				'contact',
				'email',
				'telnr',
				'programma',
			],
			';'
		);
		foreach ( $workshops as $workshop ) {
			fputcsv(
				$this->filehandle,
				[
					$workshop->code,
					$workshop->naam,
					date( 'd-m-Y', $workshop->datum ),
					date( 'H:i', $workshop->start_tijd ),
					date( 'H:i', $workshop->eind_tijd ),
					$workshop->get_docent_naam(),
					implode( ',', $workshop->technieken ),
					$workshop->aantal,
					number_format_i18n( $workshop->kosten, 2 ),
					$workshop->get_statustekst(),
					$workshop->organisatie,
					$workshop->organisatie_adres,
					$workshop->organisatie_email,
					$workshop->contact,
					$workshop->email,
					$workshop->telnr,
					$workshop->programma,
				],
				';'
			);
		}
	}

	/**
	 * Reageer op een aanvraag
	 *
	 * @return array
	 */
	protected function reageren() : array {
		$workshop = new Workshop( $this->data['input']['workshop_id'] );
		$workshop->actie->reactie( $this->data['input']['reactie'] );
		return [
			'status'  => $this->status( 'Er is een email verzonden naar het contact' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Negeer een vraag en zet de status terug naar reactie.
	 *
	 * @return array
	 */
	protected function negeren() : array {
		$workshop = new Workshop( $this->data['input']['workshop_id'] );
		$workshop->actie->reactie();
		return [
			'status'  => $this->status( 'De status is aangepast' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Zeg een workshop af
	 *
	 * @return array
	 */
	protected function afzeggen() : array {
		$workshop = new Workshop( $this->data['input']['workshop_id'] );
		$workshop->actie->annuleer();
		return [
			'status'  => $this->status( 'De afspraak voor de workshop is ' . ( $workshop->definitief ? 'per email afgezegd' : 'verwijderd' ) ),
			'content' => $this->display(),
		];
	}

	/**
	 * Verwerk de input data en bewaar de workshop.
	 *
	 * @return array
	 */
	protected function bewaren() : array {
		$workshop = $this->update_workshop();
		$workshop->save();
		return [
			'status'  => $this->status( 'De workshop informatie is opgeslagen' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Verwerk de input data en bewaar de workshop.
	 *
	 * @return array
	 */
	protected function herstellen() : array {
		$workshop                = $this->update_workshop();
		$workshop->definitief    = false;
		$workshop->vervallen     = false;
		$workshop->aanvraagdatum = strtotime( 'now' );
		$workshop->save();
		return [
			'status'  => $this->status( 'De workshop informatie is opgeslagen' ),
			'content' => $this->display(),
		];
	}


	/**
	 * Verwerk de input data en bevestig de workshop.
	 *
	 * @return array
	 */
	protected function bevestigen() : array {
		$workshop = $this->update_workshop();
		return [
			'status'  => $this->status( $workshop->actie->bevestig() ),
			'content' => $this->display(),
		];
	}

	/**
	 * Verwerk de input data en geef de workshop terug.
	 *
	 * @return Workshop
	 */
	private function update_workshop() : Workshop {
		$workshop                    = new Workshop( $this->data['input']['workshop_id'] );
		$workshop->naam              = $this->data['input']['naam'];
		$workshop->datum             = strtotime( $this->data['input']['datum'] );
		$workshop->start_tijd        = strtotime( $this->data['input']['start_tijd'], $workshop->datum );
		$workshop->eind_tijd         = strtotime( $this->data['input']['eind_tijd'], $workshop->datum );
		$workshop->docent            = $this->data['input']['docent'] ?? '';
		$workshop->technieken        = $this->data['input']['technieken'];
		$workshop->organisatie       = $this->data['input']['organisatie'];
		$workshop->organisatie_adres = $this->data['input']['organisatie_adres'];
		$workshop->organisatie_email = $this->data['input']['organisatie_email'];
		$workshop->contact           = $this->data['input']['contact'];
		$workshop->email             = $this->data['input']['email'];
		$workshop->telnr             = $this->data['input']['telnr'];
		$workshop->programma         = $this->data['input']['programma'];
		$workshop->kosten            = floatval( $this->data['input']['kosten'] );
		$workshop->aantal            = intval( $this->data['input']['aantal'] );
		$workshop->aanvraag_id       = intval( $this->data['input']['aanvraag_id'] );
		$workshop->werkplekken       = $this->data['input']['werkplekken'];
		return $workshop;
	}

}
