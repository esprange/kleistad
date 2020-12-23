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

/**
 * De kleistad workshop class.
 */
class Public_Workshop_Beheer extends ShortcodeForm {

	/**
	 * Maak de lijst van workshops
	 *
	 * @return array De workshops data.
	 */
	private function planning() {
		$workshops = new Workshops();
		$lijst     = [];
		foreach ( $workshops as $workshop_id => $workshop ) {
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
				'status'     => $workshop->status(),
			];
		}
		return $lijst;
	}

	/**
	 * Maak de lijst van aanvragen
	 *
	 * @return array De aanvragen data.
	 */
	private function aanvragen() {
		$casussen = get_posts(
			[
				'post_type'      => WorkshopAanvraag::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => [ 'nieuw', 'gereageerd', 'vraag', 'gepland' ],
			]
		);
		$lijst    = [];
		foreach ( $casussen as $casus ) {
			$casus_details = unserialize( $casus->post_excerpt ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			$lijst[]       = [
				'titel'    => $casus->post_title,
				'status'   => $casus_details['workshop_id'] ? $casus->post_status . ' (W' . $casus_details['workshop_id'] . ')' : $casus->post_status,
				'id'       => $casus->ID,
				'datum_ux' => strtotime( $casus->post_modified ),
				'datum'    => date( 'd-m-Y H:i', strtotime( $casus->post_modified ) ),
			];
		}
		return $lijst;
	}

	/**
	 * Bepaal de mogelijke docenten, zou beter kunnen als er een role docenten is...
	 *
	 * @return array De docenten.
	 */
	private function docenten() {
		$docenten   = [];
		$gebruikers = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => 'display_name',
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			if ( user_can( $gebruiker->ID, self::OVERRIDE ) ) {
				$docenten[] = $gebruiker;
			}
		}
		return $docenten;
	}

	/**
	 * Bereid een workshop wijziging voor.
	 *
	 * @param int $workshop_id De workshop.
	 * @return array De workshop data.
	 */
	private function formulier( $workshop_id = null ) {
		$workshop = new Workshop( $workshop_id );
		$order    = new Order( $workshop->geef_referentie() );
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
			'betaald'           => $workshop->betaald,
			'definitief'        => $workshop->definitief,
			'vervallen'         => $workshop->vervallen,
			'aanvraag_id'       => $workshop->aanvraag_id,
			'gefactureerd'      => boolval( $order->id ),
			'betaling_email'    => $workshop->betaling_email,
		];
	}

	/**
	 * Prepareer 'input' form
	 *
	 * @param array $data data voor display.
	 * @return \WP_ERROR|bool
	 *
	 * @since   5.0.0
	 */
	protected function prepare( &$data ) {
		if ( 'toevoegen' === $data['actie'] ) {
			/*
			* Er moet een nieuwe workshop opgevoerd worden
			*/
			$data['docenten'] = $this->docenten();
			if ( ! isset( $data['workshop'] ) ) {
				$data['workshop'] = $this->formulier();
			}
		} elseif ( 'wijzigen' === $data['actie'] ) {
			/*
			* Er is een workshop gekozen om te wijzigen.
			*/
			$data['docenten'] = $this->docenten();
			if ( ! isset( $data['workshop'] ) ) {
				$data['workshop'] = $this->formulier( $data['id'] );
			}
		} elseif ( 'inplannen' === $data['actie'] ) {
			/**
			 * Een workshop aanvraag gaat gepland worden.
			 */
			$casus            = get_post( $data['id'] );
			$casus_details    = unserialize( $casus->post_excerpt ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			$data['docenten'] = $this->docenten();
			if ( $casus_details['workshop_id'] ) {
				$data['workshop'] = $this->formulier( $casus_details['workshop_id'] );
			} else {
				$data['workshop']                = wp_parse_args( $casus_details, $this->formulier() );
				$data['workshop']['aanvraag_id'] = $data['id'];
			}
		} elseif ( 'tonen' === $data['actie'] ) {
			/**
			 * Een workshop aanvraag moet getoond worden.
			 */
			$casus         = get_post( $data['id'] );
			$data['casus'] = array_merge(
				unserialize( $casus->post_excerpt ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				[
					'casus_id'        => $data['id'],
					'correspondentie' => unserialize( base64_decode( $casus->post_content ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
					'datum'           => date( 'd-m-Y H:i', strtotime( $casus->post_modified ) ),
				]
			);
		} else {
			/**
			 * De workshopaanvragen en de geplande workshops moeten worden getoond.
			 */
			$data['workshops'] = $this->planning();
			$data['aanvragen'] = $this->aanvragen();
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'workshop_beheer' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   5.0.0
	 */
	protected function validate( &$data ) {
		$error = new \WP_Error();
		if ( 'reageren' === $data['form_actie'] ) {
			$data['casus'] = filter_input_array(
				INPUT_POST,
				[
					'casus_id' => FILTER_SANITIZE_NUMBER_INT,
					'reactie'  => FILTER_SANITIZE_STRING,
				]
			);
			if ( empty( $data['casus']['reactie'] ) ) {
				$error->add( 'reactie', 'Er is nog geen reactie ingevoerd!' );
			}
		} else {
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
			if ( 'bewaren' === $data['form_actie'] || 'bevestigen' === $data['form_actie'] ) {
				if ( ! $this->validate_email( $data['workshop']['email'] ) ) {
					$error->add( 'verplicht', 'De invoer ' . $data['workshop']['email'] . ' is geen geldig E-mail adres.' );
				}
				if ( ! empty( $data['workshop']['telnr'] ) && ! $this->validate_telnr( $data['workshop']['telnr'] ) ) {
					$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven' );
				}
				if ( strtotime( $data['workshop']['start_tijd'] ) >= strtotime( $data['workshop']['eind_tijd'] ) ) {
					$error->add( 'Invoerfout', 'De starttijd moet voor de eindtijd liggen' );
				}
			}
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Schrijf workshop informatie naar het bestand.
	 */
	protected function workshops() {
		$workshops = new Workshops();
		fputcsv(
			$this->file_handle,
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
			';',
			'"'
		);
		foreach ( $workshops as $workshop ) {
			fputcsv(
				$this->file_handle,
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
					$workshop->status(),
					$workshop->organisatie,
					$workshop->organisatie_adres,
					$workshop->organisatie_email,
					$workshop->contact,
					$workshop->email,
					$workshop->telnr,
					$workshop->programma,
				],
				';',
				'"'
			);
		}
	}

	/**
	 *
	 * Bewaar 'input' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_Error|array
	 *
	 * @since   5.0.0
	 */
	protected function save( $data ) {
		if ( 'reageren' === $data['form_actie'] ) {
			WorkshopAanvraag::reactie( $data['casus']['casus_id'], $data['casus']['reactie'] );
			return [
				'status'  => $this->status( 'Er is een email verzonden naar de aanvrager' ),
				'content' => $this->display(),
			];
		}

		$workshop_id = $data['workshop']['workshop_id'];
		$bericht     = '';
		if ( $workshop_id > 0 ) {
			$workshop = new Workshop( $workshop_id );
		} else {
			$workshop = new Workshop();
		}
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
		$workshop->kosten            = $data['workshop']['kosten'];
		$workshop->aantal            = $data['workshop']['aantal'];
		$workshop->aanvraag_id       = $data['workshop']['aanvraag_id'];
		if ( 'bewaren' === $data['form_actie'] ) {
			$workshop->save();
			$bericht = 'De workshop informatie is opgeslagen';
		} elseif ( 'bevestigen' === $data['form_actie'] ) {
			if ( false !== $workshop->bevestig() ) {
				$bericht = 'Gegevens zijn opgeslagen en een bevestigingsemail is verstuurd';
			} else {
				return [
					'status'  => $this->status( new \WP_Error( 'factuur', 'De factuur kan niet meer gewijzigd worden' ) ),
					'content' => $this->display(),
				];
			}
		} elseif ( 'afzeggen' === $data['form_actie'] ) {
			$workshop->afzeggen();
			if ( $workshop->definitief ) {
				$workshop->verzend_email( '_afzegging' );
				$bericht = 'De afspraak voor de workshop is per email afgezegd';
			} else {
				$bericht = 'De afspraak voor de workshop is verwijderd';
			}
		}
		return [
			'status'  => $this->status( $bericht ),
			'content' => $this->display(),
		];
	}
}
