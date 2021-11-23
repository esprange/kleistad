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
	 * Prepareer 'input' form voor toevoegen.
	 *
	 * @return string
	 */
	protected function prepare_toevoegen() : string {
		/*
		* Er moet een nieuwe workshop opgevoerd worden
		*/
		$this->data['docenten'] = $this->docenten();
		if ( ! isset( $this->data['workshop'] ) ) {
			$this->data['workshop'] = $this->formulier();
		}
		return $this->content();
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
		$this->data['docenten'] = $this->docenten();
		if ( ! isset( $this->data['workshop'] ) ) {
			$this->data['workshop'] = $this->formulier( $this->data['id'] );
		}
		return $this->content();
	}

	/**
	 * Prepareer 'input' form voor inplannen.
	 *
	 * @return string
	 */
	protected function prepare_inplannen() : string {
		/**
		 * Een workshop aanvraag gaat gepland worden.
		 */
		$aanvraag               = new WorkshopAanvraag( $this->data['id'] );
		$this->data['docenten'] = $this->docenten();
		if ( $aanvraag->workshop_id ) {
			$this->data['workshop'] = $this->formulier( $aanvraag->workshop_id );

			return $this->content();
		}
		$this->data['workshop']                = wp_parse_args(
			[
				'email'   => $aanvraag->email,
				'contact' => $aanvraag->contact,
				'telnr'   => $aanvraag->telnr,
			],
			$this->formulier()
		);
		$this->data['workshop']['aanvraag_id'] = $this->data['id'];
		return $this->content();
	}

	/**
	 * Prepareer 'input' form voor inplannen.
	 *
	 * @return string
	 */
	protected function prepare_tonen() : string {
		/**
		 * Een workshop aanvraag moet getoond worden.
		 */
		$aanvraag            = new WorkshopAanvraag( $this->data['id'] );
		$this->data['casus'] = [
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
		$this->data['workshops'] = $this->planning();
		$this->data['aanvragen'] = $this->aanvragen();
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'workshop_beheer' form
	 *
	 * @since   5.0.0
	 *
	 * @return array
	 */
	protected function process() : array {
		$error = new WP_Error();
		if ( 'reageren' === $this->form_actie ) {
			$this->data['casus'] = filter_input_array(
				INPUT_POST,
				[
					'casus_id' => FILTER_SANITIZE_NUMBER_INT,
					'reactie'  => FILTER_SANITIZE_STRING,
				]
			);
			if ( empty( $this->data['casus']['reactie'] ) ) {
				return $this->melding( new WP_Error( 'reactie', 'Er is nog geen reactie ingevoerd!' ) );
			}
			return $this->save();
		}
		$this->data['workshop']              = filter_input_array(
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
		$this->data['workshop']['programma'] = sanitize_textarea_field( $this->data['workshop']['programma'] );
		if ( is_null( $this->data['workshop']['technieken'] ) ) {
			$this->data['workshop']['technieken'] = [];
		}
		if ( in_array( $this->form_actie, [ 'bewaren', 'bevestigen' ], true ) ) {
			if ( ! $this->validator->email( $this->data['workshop']['email'] ) ) {
				$error->add( 'verplicht', 'De invoer ' . $this->data['workshop']['email'] . ' is geen geldig E-mail adres.' );
			}
			if ( ! $this->validator->telnr( $this->data['workshop']['telnr'] ) ) {
				$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven' );
			}
			if ( strtotime( $this->data['workshop']['start_tijd'] ) >= strtotime( $this->data['workshop']['eind_tijd'] ) ) {
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
	 * @return array
	 */
	protected function reageren() : array {
		$workshopaanvraag = new WorkshopAanvraag( $this->data['casus']['casus_id'] );
		$workshopaanvraag->reactie( $this->data['casus']['reactie'] );
		return [
			'status'  => $this->status( 'Er is een email verzonden naar de aanvrager' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Zeg een workshop af
	 *
	 * @return array
	 */
	protected function afzeggen() : array {
		$workshop = new Workshop( intval( $this->data['workshop']['workshop_id'] ) );
		$workshop->actie->afzeggen();
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
	 * Verwerk de input data en bevestig de workshop.
	 *
	 * @return array
	 */
	protected function bevestigen() : array {
		$workshop = $this->update_workshop();
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
	 * @return Workshop
	 */
	private function update_workshop() : Workshop {
		$workshop_id                 = intval( $this->data['workshop']['workshop_id'] );
		$workshop                    = ( $workshop_id > 0 ) ? new Workshop( $workshop_id ) : new Workshop();
		$workshop->naam              = $this->data['workshop']['naam'];
		$workshop->datum             = strtotime( $this->data['workshop']['datum'] );
		$workshop->start_tijd        = strtotime( $this->data['workshop']['start_tijd'] );
		$workshop->eind_tijd         = strtotime( $this->data['workshop']['eind_tijd'] );
		$workshop->docent            = $this->data['workshop']['docent'];
		$workshop->technieken        = $this->data['workshop']['technieken'];
		$workshop->organisatie       = $this->data['workshop']['organisatie'];
		$workshop->organisatie_adres = $this->data['workshop']['organisatie_adres'];
		$workshop->organisatie_email = $this->data['workshop']['organisatie_email'];
		$workshop->contact           = $this->data['workshop']['contact'];
		$workshop->email             = $this->data['workshop']['email'];
		$workshop->telnr             = $this->data['workshop']['telnr'];
		$workshop->programma         = $this->data['workshop']['programma'];
		$workshop->kosten            = floatval( $this->data['workshop']['kosten'] );
		$workshop->aantal            = intval( $this->data['workshop']['aantal'] );
		$workshop->aanvraag_id       = intval( $this->data['workshop']['aanvraag_id'] );
		return $workshop;
	}

}
