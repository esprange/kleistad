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
	 * Maak de lijst van workshops
	 *
	 * @return array De workshops data.
	 */
	private function planning() : array {
		$workshops = new Workshops();
		$lijst     = [];
		foreach ( $workshops as $workshop ) {
			$lijst[] = [
				'id'         => $workshop->id,
				'code'       => $workshop->code,
				'datum_ux'   => $workshop->datum,
				'datum'      => date( 'd-m-Y', $workshop->datum ),
				'naam'       => $workshop->naam,
				'start_tijd' => date( 'H:i', $workshop->start_tijd ),
				'eind_tijd'  => date( 'H:i', $workshop->eind_tijd ),
				'docent'     => $workshop->docent,
				'aantal'     => $workshop->aantal,
				'status'     => $workshop->geef_statustekst(),
			];
		}
		return $lijst;
	}

	/**
	 * Maak de lijst van aanvragen
	 *
	 * @return array De aanvragen data.
	 */
	private function aanvragen() : array {
		$workshop_aanvragen = new WorkshopAanvragen();
		$lijst              = [];
		foreach ( $workshop_aanvragen as $workshop_aanvraag ) {
			$lijst[] = [
				'titel'  => $workshop_aanvraag->post_title,
				'status' => $workshop_aanvraag->workshop_id ? "$workshop_aanvraag->post_status (W$workshop_aanvraag->workshop_id)" : $workshop_aanvraag->post_status,
				'id'     => $workshop_aanvraag->ID,
				'datum'  => strtotime( $workshop_aanvraag->post_modified ),
			];
		}
		return $lijst;
	}

	/**
	 * Bepaal de mogelijke docenten, zou beter kunnen als er een role docenten is...
	 *
	 * @return array De docenten.
	 */
	private function docenten() : array {
		$docenten   = [];
		$gebruikers = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => 'display_name',
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			if ( user_can( $gebruiker->ID, OVERRIDE ) ) {
				$docenten[] = $gebruiker;
			}
		}
		return $docenten;
	}

	/**
	 * Bereid een workshop wijziging voor.
	 *
	 * @param int|null $workshop_id De workshop.
	 * @return array De workshop data.
	 */
	private function formulier( ?int $workshop_id = null ) : array {
		$workshop = new Workshop( $workshop_id );
		return [
			'workshop_id'       => $workshop->id,
			'naam'              => $workshop->naam,
			'datum'             => date( 'd-m-Y', $workshop->datum ),
			'start_tijd'        => date( 'H:i', $workshop->start_tijd ),
			'eind_tijd'         => date( 'H:i', $workshop->eind_tijd ),
			'docent'            => $workshop->docent,
			'technieken'        => $workshop->technieken,
			'organisatie'       => $workshop->organisatie,
			'organisatie_adres' => $workshop->organisatie_adres,
			'organisatie_email' => $workshop->organisatie_email,
			'contact'           => $workshop->contact,
			'email'             => $workshop->email,
			'telnr'             => $workshop->telnr,
			'programma'         => $workshop->programma,
			'kosten'            => $workshop->kosten,
			'aantal'            => $workshop->aantal,
			'betaald'           => $workshop->is_betaald(),
			'definitief'        => $workshop->definitief,
			'vervallen'         => $workshop->vervallen,
			'aanvraag_id'       => $workshop->aanvraag_id,
			'gefactureerd'      => $workshop->betaling_email,
		];
	}

	/**
	 * Prepareer 'input' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   5.0.0
	 */
	protected function prepare( array &$data ) {
		if ( 'toevoegen' === $data['actie'] ) {
			/*
			* Er moet een nieuwe workshop opgevoerd worden
			*/
			$data['docenten'] = $this->docenten();
			if ( ! isset( $data['workshop'] ) ) {
				$data['workshop'] = $this->formulier();
			}
			return true;
		}
		if ( 'wijzigen' === $data['actie'] ) {
			/*
			* Er is een workshop gekozen om te wijzigen.
			*/
			$data['docenten'] = $this->docenten();
			if ( ! isset( $data['workshop'] ) ) {
				$data['workshop'] = $this->formulier( $data['id'] );
			}
			return true;
		}
		if ( 'inplannen' === $data['actie'] ) {
			/**
			 * Een workshop aanvraag gaat gepland worden.
			 */
			$aanvraag         = new WorkshopAanvraag( $data['id'] );
			$data['docenten'] = $this->docenten();
			if ( $aanvraag->workshop_id ) {
				$data['workshop'] = $this->formulier( $aanvraag->workshop_id );
				return true;
			}
			$data['workshop']                = wp_parse_args(
				[
					'email'   => $aanvraag->email,
					'contact' => $aanvraag->contact,
					'telnr'   => $aanvraag->telnr,
				],
				$this->formulier()
			);
			$data['workshop']['aanvraag_id'] = $data['id'];
			return true;
		}
		if ( 'tonen' === $data['actie'] ) {
			/**
			 * Een workshop aanvraag moet getoond worden.
			 */
			$aanvraag      = new WorkshopAanvraag( $data['id'] );
			$data['casus'] = [
				'correspondentie' => $aanvraag->communicatie,
				'casus_id'        => $aanvraag->ID,
				'datum'           => date( 'd-m-Y H:i', strtotime( $aanvraag->post_modified ) ),
				'naam'            => $aanvraag->naam,
				'contact'         => $aanvraag->contact,
				'telnr'           => $aanvraag->telnr,
				'email'           => $aanvraag->email,
				'omvang'          => $aanvraag->omvang,
				'periode'         => $aanvraag->periode,
			];
			return true;
		}
		/**
		 * De workshopaanvragen en de geplande workshops moeten worden getoond.
		 */
		$data['workshops'] = $this->planning();
		$data['aanvragen'] = $this->aanvragen();
		return true;
	}

	/**
	 * Valideer/sanitize 'workshop_beheer' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return WP_Error|bool
	 *
	 * @since   5.0.0
	 */
	protected function validate( array &$data ) {
		$error = new WP_Error();
		if ( 'reageren' === $data['form_actie'] ) {
			$data['casus'] = filter_input_array(
				INPUT_POST,
				[
					'casus_id' => FILTER_SANITIZE_NUMBER_INT,
					'reactie'  => FILTER_SANITIZE_STRING,
				]
			);
			if ( empty( $data['casus']['reactie'] ) ) {
				return new WP_Error( 'reactie', 'Er is nog geen reactie ingevoerd!' );
			}
			return true;
		}
		$data['workshop']              = filter_input_array(
			INPUT_POST,
			[
				'workshop_id'       => FILTER_SANITIZE_NUMBER_INT,
				'naam'              => FILTER_SANITIZE_STRING,
				'datum'             => FILTER_SANITIZE_STRING,
				'start_tijd'        => FILTER_SANITIZE_STRING,
				'eind_tijd'         => FILTER_SANITIZE_STRING,
				'docent'            => FILTER_SANITIZE_STRING,
				'technieken'        => [
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
		$data['workshop']['programma'] = sanitize_textarea_field( $data['workshop']['programma'] );
		if ( is_null( $data['workshop']['technieken'] ) ) {
			$data['workshop']['technieken'] = [];
		}
		if ( in_array( $data['form_actie'], [ 'bewaren', 'bevestigen' ], true ) ) {
			if ( ! $this->validator->email( $data['workshop']['email'] ) ) {
				$error->add( 'verplicht', 'De invoer ' . $data['workshop']['email'] . ' is geen geldig E-mail adres.' );
			}
			if ( ! $this->validator->telnr( $data['workshop']['telnr'] ) ) {
				$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven' );
			}
			if ( strtotime( $data['workshop']['start_tijd'] ) >= strtotime( $data['workshop']['eind_tijd'] ) ) {
				$error->add( 'Invoerfout', 'De starttijd moet voor de eindtijd liggen' );
			}
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Schrijf workshop informatie naar het bestand.
	 *
	 * @param array $data De argumenten.
	 */
	protected function workshops( array $data ) {
		$workshops = new Workshops();
		fputcsv(
			$data['filehandle'],
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
				$data['filehandle'],
				[
					$workshop->code,
					$workshop->naam,
					date( 'd-m-Y', $workshop->datum ),
					date( 'H:i', $workshop->start_tijd ),
					date( 'H:i', $workshop->eind_tijd ),
					$workshop->docent,
					implode( ',', $workshop->technieken ),
					$workshop->aantal,
					number_format_i18n( $workshop->kosten, 2 ),
					$workshop->geef_statustekst(),
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
	 * @param array $data data te bewaren.
	 *
	 * @return array
	 */
	protected function reageren( array $data ) : array {
		$workshopaanvraag = new WorkshopAanvraag( $data['casus']['casus_id'] );
		$workshopaanvraag->reactie( $data['casus']['reactie'] );
		return [
			'status'  => $this->status( 'Er is een email verzonden naar de aanvrager' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Zeg een workshop af
	 *
	 * @param array $data data te bewaren.
	 *
	 * @return array
	 */
	protected function afzeggen( array $data ) : array {
		$workshop = new Workshop( intval( $data['workshop']['workshop_id'] ) );
		$workshop->actie->afzeggen();
		return [
			'status'  => $this->status( 'De afspraak voor de workshop is ' . ( $workshop->definitief ) ? 'per email afgezegd' : 'verwijderd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Verwerk de input data en bewaar de workshop.
	 *
	 * @param array $data De input.
	 *
	 * @return array
	 */
	protected function bewaren( array $data ) : array {
		$workshop = $this->update_workshop( $data );
		$workshop->save();
		return [
			'status'  => $this->status( 'De workshop informatie is opgeslagen' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Verwerk de input data en bevestig de workshop.
	 *
	 * @param array $data De input.
	 *
	 * @return array
	 */
	protected function bevestigen( array $data ) : array {
		$workshop = $this->update_workshop( $data );
		if ( ! $workshop->actie->bevestig() ) {
			return [
				'status'  => $this->status( new WP_Error( 'factuur', 'De factuur kan niet meer gewijzigd worden' ) ),
				'content' => $this->display(),
			];
		}
		return [
			'status'  => $this->status( 'Gegevens zijn opgeslagen en een bevestigingsemail is verstuurd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Verwerk de input data en geef de workshop terug.
	 *
	 * @param array $data De input.
	 * @return Workshop
	 */
	private function update_workshop( array $data ) : Workshop {
		$workshop_id                 = intval( $data['workshop']['workshop_id'] );
		$workshop                    = ( $workshop_id > 0 ) ? new Workshop( $workshop_id ) : new Workshop();
		$workshop->naam              = $data['workshop']['naam'];
		$workshop->datum             = strtotime( $data['workshop']['datum'] );
		$workshop->start_tijd        = strtotime( $data['workshop']['start_tijd'] );
		$workshop->eind_tijd         = strtotime( $data['workshop']['eind_tijd'] );
		$workshop->docent            = $data['workshop']['docent'];
		$workshop->technieken        = $data['workshop']['technieken'];
		$workshop->organisatie       = $data['workshop']['organisatie'];
		$workshop->organisatie_adres = $data['workshop']['organisatie_adres'];
		$workshop->organisatie_email = $data['workshop']['organisatie_email'];
		$workshop->contact           = $data['workshop']['contact'];
		$workshop->email             = $data['workshop']['email'];
		$workshop->telnr             = $data['workshop']['telnr'];
		$workshop->programma         = $data['workshop']['programma'];
		$workshop->kosten            = floatval( $data['workshop']['kosten'] );
		$workshop->aantal            = intval( $data['workshop']['aantal'] );
		$workshop->aanvraag_id       = intval( $data['workshop']['aanvraag_id'] );
		return $workshop;
	}

}
