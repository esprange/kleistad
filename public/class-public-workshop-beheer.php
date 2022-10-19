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
		$this->data['id'] = null;
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
			$this->data['workshop'] = $this->formulier( $this->data['id'] );
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
		$this->planning();
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
		$error                  = new WP_Error();
		$this->data['workshop'] = filter_input_array(
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
		if ( 'reageren' === $this->form_actie ) {
			if ( empty( $this->data['workshop']['reactie'] ) ) {
				return $this->melding( new WP_Error( 'reactie', 'Er is nog geen reactie ingevoerd!' ) );
			}
			return $this->save();
		}
		$this->data['workshop']['programma'] = sanitize_textarea_field( $this->data['workshop']['programma'] );
		if ( is_null( $this->data['workshop']['technieken'] ) ) {
			$this->data['workshop']['technieken'] = [];
		}
		if ( is_null( $this->data['workshop']['werkplekken'] ) ) {
			$this->data['workshop']['werkplekken'] = [];
		}
		$this->data['workshop']['docent'] = implode( ';', $this->data['workshop']['docent'] ?? [] );
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
		$workshop = new Workshop( $this->data['workshop']['workshop_id'] );
		$workshop->actie->reactie( $this->data['workshop']['reactie'] );
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
		$workshop = new Workshop( $this->data['workshop']['workshop_id'] );
		$workshop->actie->reactie( $this->data['workshop']['reactie'] );
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
		$workshop = new Workshop( intval( $this->data['workshop']['workshop_id'] ) );
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
	 * Verwerk de input data en bevestig de workshop.
	 *
	 * @return array
	 */
	protected function bevestigen() : array {
		$workshop = $this->update_workshop();
		$result   = $workshop->actie->bevestig();
		if ( ! empty( $result ) ) {
			return [
				'status'  => $this->status( new WP_Error( 'bevestig', $result ) ),
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
		$workshop               = new Workshop();
		$workshop->communicatie = [
			[
				'type'    => WorkshopActie::NIEUW,
				'from'    => 'Kleistad',
				'subject' => "Toevoeging {$this->data['workshop']['naam']} door " . wp_get_current_user()->display_name,
				'tekst'   => '',
				'tijd'    => current_time( 'd-m-Y H:i' ),
			],
		];
		$workshop_id            = intval( $this->data['workshop']['workshop_id'] );
		if ( $workshop_id ) {
			$workshop = new Workshop( $workshop_id );
		}
		$workshop->naam              = $this->data['workshop']['naam'];
		$workshop->datum             = strtotime( $this->data['workshop']['datum'] );
		$workshop->start_tijd        = strtotime( $this->data['workshop']['start_tijd'] );
		$workshop->eind_tijd         = strtotime( $this->data['workshop']['eind_tijd'] );
		$workshop->docent            = $this->data['workshop']['docent'] ?? '';
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
		$workshop->werkplekken       = $this->data['workshop']['werkplekken'];
		return $workshop;
	}

	/**
	 * Maak de lijst van workshops
	 *
	 * @return void.
	 */
	private function planning() : void {
		$workshops                    = new Workshops();
		$this->data['workshops']      = [];
		$this->data['gaat_vervallen'] = false;
		foreach ( $workshops as $workshop ) {
			$status = $workshop->get_statustekst();
			if ( Workshop::VERVALT === $status ) {
				$this->data['gaat_vervallen'] = true;
			}
			$docenten = explode( ', ', $workshop->get_docent_naam() );
			array_walk(
				$docenten,
				function( &$docent ) {
					$docent = strstr( $docent . ' ', ' ', true );
				}
			);
			$this->data['workshops'][] = [
				'id'         => $workshop->id,
				'code'       => $workshop->code,
				'datum_ux'   => $workshop->datum,
				'datum'      => date( 'd-m-Y', $workshop->datum ),
				'contact'    => substr( $workshop->contact, 0, 14 ),
				'start_tijd' => date( 'H:i', $workshop->start_tijd ),
				'eind_tijd'  => date( 'H:i', $workshop->eind_tijd ),
				'docent'     => implode( '<br/>', $docenten ),
				'aantal'     => $workshop->aantal,
				'status'     => $workshop->get_statustekst(),
				'cstatus'    => $workshop->communicatie[0]['type'] ?? '',
				'update'     => strtotime( $workshop->communicatie[0]['tijd'] ?? '' ),
			];
		}
	}

	/**
	 * Bereid een workshop wijziging voor.
	 *
	 * @param int|null $workshop_id De workshop.
	 * @return array De workshop data.
	 */
	private function formulier( ?int $workshop_id ) : array {
		$workshop = new Workshop( $workshop_id );
		return [
			'workshop_id'       => $workshop->id,
			'naam'              => $workshop->naam,
			'datum'             => date( 'd-m-Y', $workshop->datum ),
			'start_tijd'        => date( 'H:i', $workshop->start_tijd ),
			'eind_tijd'         => date( 'H:i', $workshop->eind_tijd ),
			'docent'            => array_map( 'intval', explode( ';', $workshop->docent ) ),
			'docent_naam'       => $workshop->get_docent_naam(),
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
			'communicatie'      => $workshop->communicatie,
			'werkplekken'       => $workshop->werkplekken,
		];
	}

}
