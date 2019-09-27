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
		$workshops = \Kleistad\Workshop::all();
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
				'post_type'      => \Kleistad\WorkshopAanvraag::POST_TYPE,
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
	 * Bereid een workshop wijziging voor.
	 *
	 * @param int $workshop_id De workshop.
	 * @return array De workshop data.
	 */
	private function formulier( $workshop_id = null ) {
		$workshop = new \Kleistad\Workshop( $workshop_id );
		return [
			'workshop_id' => $workshop->id,
			'naam'        => $workshop->naam,
			'datum'       => date( 'd-m-Y', $workshop->datum ),
			'start_tijd'  => date( 'H:i', $workshop->start_tijd ),
			'eind_tijd'   => date( 'H:i', $workshop->eind_tijd ),
			'docent'      => $workshop->docent,
			'technieken'  => $workshop->technieken,
			'organisatie' => $workshop->organisatie,
			'contact'     => $workshop->contact,
			'email'       => $workshop->email,
			'telnr'       => $workshop->telnr,
			'programma'   => $workshop->programma,
			'kosten'      => $workshop->kosten,
			'aantal'      => $workshop->aantal,
			'betaald'     => $workshop->betaald,
			'definitief'  => $workshop->definitief,
			'vervallen'   => $workshop->vervallen,
			'aanvraag_id' => $workshop->aanvraag_id,
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
	protected function prepare( &$data = null ) {
		$error = new \WP_Error();

		$data['actie'] = filter_input( INPUT_POST, 'actie', FILTER_SANITIZE_STRING ) ?? filter_input( INPUT_GET, 'actie', FILTER_SANITIZE_STRING );
		if ( is_null( $data['actie'] ) ) {
			$data['actie'] = '-';
		}
		$gebruikers       = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$data['docenten'] = [];
		foreach ( $gebruikers as $gebruiker ) {
			if ( \Kleistad\Roles::override( $gebruiker->ID ) ) {
				$data['docenten'][] = $gebruiker;
			}
		}
		if ( 'toevoegen' === $data['actie'] ) {
			/*
			* Er moet een nieuwe workshop opgevoerd worden
			*/
			if ( ! isset( $data['workshop'] ) ) {
				$data['workshop'] = $this->formulier();
			}
		} elseif ( 'wijzigen' === $data['actie'] ) {
			/*
			 * Er is een workshop gekozen om te wijzigen.
			 */
			$workshop_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			if ( wp_verify_nonce( filter_input( INPUT_GET, '_wpnonce' ), 'kleistad_wijzig_workshop_' . $workshop_id ) ) {
				if ( ! isset( $data['workshop'] ) ) {
					$data['workshop'] = $this->formulier( $workshop_id );
				}
			} else {
				$error->add( 'security', 'Security fout! !' );
				return $error;
			}
		} elseif ( 'inplannen' === $data['actie'] ) {
			$casus_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			if ( wp_verify_nonce( filter_input( INPUT_GET, '_wpnonce' ), 'kleistad_plan_workshop_' . $casus_id ) ) {
				$casus         = get_post( $casus_id );
				$casus_details = unserialize( $casus->post_excerpt ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				if ( $casus_details['workshop_id'] ) {
					$data['workshop'] = $this->formulier( $casus_details['workshop_id'] );
				} else {
					$data['workshop']                = wp_parse_args( $casus_details, $this->formulier() );
					$data['workshop']['aanvraag_id'] = $casus_id;
				}
			} else {
				$error->add( 'security', 'Security fout! !' );
				return $error;
			}
		} elseif ( 'tonen' === $data['actie'] ) {
			$casus_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			if ( wp_verify_nonce( filter_input( INPUT_GET, '_wpnonce' ), 'kleistad_toon_aanvraag_' . $casus_id ) ) {
				$casus         = get_post( $casus_id );
				$data['casus'] = array_merge(
					unserialize( $casus->post_excerpt ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
					[
						'casus_id'        => $casus_id,
						'correspondentie' => unserialize( base64_decode( $casus->post_content ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
						'datum'           => date( 'd-m-Y H:i', strtotime( $casus->post_modified ) ),
					]
				);
			} else {
				$error->add( 'security', 'Security fout! !' );
				return $error;
			}
		} else {
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
					'workshop_id' => FILTER_SANITIZE_NUMBER_INT,
					'naam'        => FILTER_SANITIZE_STRING,
					'datum'       => FILTER_SANITIZE_STRING,
					'start_tijd'  => FILTER_SANITIZE_STRING,
					'eind_tijd'   => FILTER_SANITIZE_STRING,
					'docent'      => FILTER_SANITIZE_STRING,
					'technieken'  => [
						'filter'  => FILTER_SANITIZE_STRING,
						'flags'   => FILTER_REQUIRE_ARRAY,
						'options' => [ 'default' => [] ],
					],
					'organisatie' => FILTER_SANITIZE_STRING,
					'contact'     => FILTER_SANITIZE_STRING,
					'email'       => FILTER_SANITIZE_EMAIL,
					'telnr'       => FILTER_SANITIZE_STRING,
					'vervallen'   => FILTER_VALIDATE_BOOLEAN,
					'kosten'      => [
						'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
						'flags'  => FILTER_FLAG_ALLOW_FRACTION,
					],
					'aantal'      => FILTER_SANITIZE_NUMBER_INT,
					'definitief'  => FILTER_VALIDATE_BOOLEAN,
					'betaald'     => FILTER_VALIDATE_BOOLEAN,
					'programma'   => FILTER_DEFAULT,
					'aanvraag_id' => FILTER_SANITIZE_NUMBER_INT,
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
		$workshops = \Kleistad\Workshop::all();
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
		$error = new \WP_Error();
		if ( 'reageren' === $data['form_actie'] ) {
			\Kleistad\WorkshopAanvraag::reactie( $data['casus']['casus_id'], $data['casus']['reactie'] );
			return [
				'status' => 'Er is een email verzonden naar de aanvrager',
				'actie'  => 'refresh',
				'html'   => $this->display(),
			];
		}

		$workshop_id = $data['workshop']['workshop_id'];
		$bericht     = '';
		if ( $workshop_id > 0 ) {
			$workshop = new \Kleistad\Workshop( $workshop_id );
		} else {
			$workshop = new \Kleistad\Workshop();
		}
		if ( 'verwijderen' === $data['form_actie'] ) {
			/*
			* Cursus moet verwijderd worden.
			*/
			if ( $workshop->verwijder() ) {
				$bericht = 'De workshop informatie is verwijderd';
			} else {
				$error->add( 'bevestigd', 'Een workshop die bevestigd is kan niet verwijderd worden' );
				return $error;
			}
		} else {
			$workshop->naam        = $data['workshop']['naam'];
			$workshop->datum       = strtotime( $data['workshop']['datum'] );
			$workshop->start_tijd  = strtotime( $data['workshop']['start_tijd'] );
			$workshop->eind_tijd   = strtotime( $data['workshop']['eind_tijd'] );
			$workshop->docent      = $data['workshop']['docent'];
			$workshop->technieken  = $data['workshop']['technieken'];
			$workshop->organisatie = $data['workshop']['organisatie'];
			$workshop->contact     = $data['workshop']['contact'];
			$workshop->email       = $data['workshop']['email'];
			$workshop->telnr       = $data['workshop']['telnr'];
			$workshop->programma   = $data['workshop']['programma'];
			$workshop->kosten      = $data['workshop']['kosten'];
			$workshop->aantal      = $data['workshop']['aantal'];
			$workshop->aanvraag_id = $data['workshop']['aanvraag_id'];
			if ( 'bewaren' === $data['form_actie'] ) {
				$workshop->save();
				$bericht = 'De workshop informatie is opgeslagen';
			} elseif ( 'bevestigen' === $data['form_actie'] ) {
				$workshop->bevestig();
				$bericht = 'Gegevens zijn opgeslagen en een bevestigingsemail is verstuurd';
			} elseif ( 'afzeggen' === $data['form_actie'] ) {
				$workshop->afzeggen();
				$bericht = 'De afspraak voor de workshop is per email afgezegd';
			}
		}
		return [
			'status' => $bericht,
			'actie'  => 'refresh',
			'html'   => $this->display(),
		];
	}
}
