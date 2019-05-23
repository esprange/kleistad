<?php
/**
 * Shortcode cursus beheer.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De Cursus beheer class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Cursus_Beheer extends Kleistad_ShortcodeForm {

	/**
	 * Maak de lijst van cursussen
	 *
	 * @return array De cursussen data.
	 */
	private function lijst() {
		$cursussen = Kleistad_Cursus::all();
		$lijst     = [];
		$vandaag   = strtotime( 'today' );
		foreach ( $cursussen as $cursus_id => $cursus ) {
			$lijst[] = [
				'id'          => $cursus->id,
				'naam'        => $cursus->naam,
				'start_datum' => date( 'd-m-Y', $cursus->start_datum ),
				'eind_datum'  => date( 'd-m-Y', $cursus->eind_datum ),
				'start_tijd'  => date( 'H:i', $cursus->start_tijd ),
				'eind_tijd'   => date( 'H:i', $cursus->eind_tijd ),
				'docent'      => $cursus->docent,
				'vervallen'   => $cursus->vervallen,
				'vol'         => $cursus->vol,
				'status'      => $cursus->vervallen ? 'vervallen' :
					( $cursus->eind_datum < $vandaag ? 'voltooid' :
					( $cursus->start_datum < $vandaag ? 'actief' : 'nieuw' ) ),
			];
		}
		return $lijst;
	}

	/**
	 * Bereid een cursus wijziging voor.
	 *
	 * @param int $cursus_id De cursus.
	 * @return array De cursus data.
	 */
	private function formulier( $cursus_id = null ) {
		$cursus = new Kleistad_Cursus( $cursus_id );
		return [
			'id'              => $cursus->id,
			'naam'            => $cursus->naam,
			'start_datum'     => $cursus->start_datum,
			'eind_datum'      => $cursus->eind_datum,
			'lesdatums'       => implode(
				';',
				array_map(
					function( $lesdatum ) {
						return date( 'd-m-Y', $lesdatum );
					},
					$cursus->lesdatums
				)
			),
			'start_tijd'      => $cursus->start_tijd,
			'eind_tijd'       => $cursus->eind_tijd,
			'docent'          => $cursus->docent,
			'technieken'      => $cursus->technieken,
			'vervallen'       => $cursus->vervallen,
			'vol'             => $cursus->vol,
			'techniekkeuze'   => $cursus->techniekkeuze,
			'inschrijfkosten' => $cursus->inschrijfkosten,
			'cursuskosten'    => $cursus->cursuskosten,
			'inschrijfslug'   => $cursus->inschrijfslug,
			'indelingslug'    => $cursus->indelingslug,
			'maximum'         => $cursus->maximum,
			'meer'            => $cursus->meer,
			'tonen'           => $cursus->tonen,
			'gedeeld'         => ( 0 < $cursus->inschrijfkosten ),
		];
	}

	/**
	 *
	 * Prepareer 'cursus_beheer' form
	 *
	 * @param array $data data voor display.
	 * @return \WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data = null ) {
		$error = new WP_Error();

		$data['actie'] = filter_input( INPUT_POST, 'actie', FILTER_SANITIZE_STRING );
		if ( is_null( $data['actie'] ) ) {
			$data['actie'] = filter_input( INPUT_GET, 'actie', FILTER_SANITIZE_STRING );
		}
		$gebruikers       = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$data['docenten'] = [];
		foreach ( $gebruikers as $gebruiker ) {
			if ( Kleistad_Roles::override( $gebruiker->ID ) ) {
				$data['docenten'][] = $gebruiker;
			}
		}
		if ( 'toevoegen' === $data['actie'] ) {
			/*
			* Er moet een nieuwe cursus opgevoerd worden
			*/
			$data['cursus'] = $this->formulier();
		} elseif ( 'wijzigen' === $data['actie'] ) {
			/*
			 * Er is een cursus gekozen om te wijzigen.
			 */
			$cursus_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			if ( wp_verify_nonce( filter_input( INPUT_GET, '_wpnonce' ), 'kleistad_wijzig_cursus_' . $cursus_id ) ) {
				$data['cursus'] = $this->formulier( $cursus_id );
			} else {
				$error->add( 'security', 'Security fout! !' );
				return $error;
			}
		} else {
			$data['cursussen'] = $this->lijst();
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'cursus_beheer' form
	 *
	 * @param array $data gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( &$data ) {
		$error         = new WP_Error();
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'tab'             => FILTER_SANITIZE_STRING,
				'cursus_id'       => FILTER_SANITIZE_NUMBER_INT,
				'naam'            => FILTER_SANITIZE_STRING,
				'docent'          => FILTER_SANITIZE_STRING,
				'start_datum'     => FILTER_SANITIZE_STRING,
				'eind_datum'      => FILTER_SANITIZE_STRING,
				'lesdatums'       => FILTER_SANITIZE_STRING,
				'start_tijd'      => FILTER_SANITIZE_STRING,
				'eind_tijd'       => FILTER_SANITIZE_STRING,
				'techniekkeuze'   => FILTER_SANITIZE_STRING,
				'vol'             => FILTER_SANITIZE_STRING,
				'vervallen'       => FILTER_SANITIZE_STRING,
				'inschrijfkosten' => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'cursuskosten'    => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'inschrijfslug'   => FILTER_SANITIZE_STRING,
				'indelingslug'    => FILTER_SANITIZE_STRING,
				'technieken'      => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'maximum'         => FILTER_SANITIZE_NUMBER_INT,
				'meer'            => FILTER_SANITIZE_STRING,
				'tonen'           => FILTER_SANITIZE_STRING,
			]
		);
		if ( is_null( $data['input']['technieken'] ) ) {
			$data['input']['technieken'] = [];
		}
		if ( 'bewaren' === $data['form_actie'] ) {
			if ( strtotime( $data['input']['start_tijd'] ) >= strtotime( $data['input']['eind_tijd'] ) ) {
				$error->add( 'Invoerfout', 'De starttijd moet voor de eindtijd liggen' );
			}
			if ( strtotime( $data['input']['start_datum'] ) > strtotime( $data['input']['eind_datum'] ) ) {
				$error->add( 'Invoerfout', 'De startdatum moet eerder of gelijk aan de einddatum zijn' );
			}
			if ( 0.0 === $data['input']['cursuskosten'] && 0.0 < $data['input']['inschrijfkosten'] ) {
				$error->add( 'Invoerfout', 'Als er inschrijfkosten zijn dan kunnen de cursuskosten niet gelijk zijn aan 0 euro' );
			}
			if ( '' != $data['input']['tonen'] && is_null( get_page_by_title( $data['input']['inschrijfslug'], OBJECT ) ) ) { // phpcs:ignore
				$error->add( 'Invoerfout', 'Er bestaat nog geen pagina met de naam ' . $data['input']['inschrijfslug'] );
			}
			if ( '' != $data['input']['tonen'] && is_null( get_page_by_title( $data['input']['indelingslug'], OBJECT ) ) ) { // phpcs:ignore
				$error->add( 'Invoerfout', 'Er bestaat nog geen pagina met de naam ' . $data['input']['indelingslug'] );
			}
			if ( ! empty( $error->get_error_codes() ) ) {
				return $error;
			}
		}
		return true;
	}

	/**
	 * Bewaar 'cursus_beheer' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		$cursus_id = $data['input']['cursus_id'];

		if ( $cursus_id > 0 ) {
			$cursus = new Kleistad_Cursus( $cursus_id );
		} else {
			$cursus = new Kleistad_Cursus();
		}
		if ( 'verwijderen' === $data['form_actie'] ) {
			/*
			* Cursus moet verwijderd worden.
			*/
			if ( $cursus->verwijder() ) {
				return "De cursus informatie is verwijderd";
			} else {
				$error->add( 'ingedeeld', 'Er zijn al cursisten inschrijvingen, de cursus kan niet verwijderd worden' );
				return $error;
			}
		} elseif ( 'bewaren' === $data['form_actie'] ) {
			$cursus->naam            = $data['input']['naam'];
			$cursus->docent          = $data['input']['docent'];
			$cursus->start_datum     = strtotime( $data['input']['start_datum'] );
			$cursus->eind_datum      = strtotime( $data['input']['eind_datum'] );
			$cursus->lesdatums       = array_map(
				function( $lesdatum ) {
					return strtotime( $lesdatum );
				},
				explode( ';', $data['input']['lesdatums'] )
			);
			$cursus->start_tijd      = strtotime( $data['input']['start_tijd'] );
			$cursus->eind_tijd       = strtotime( $data['input']['eind_tijd'] );
			$cursus->techniekkeuze   = '' != $data['input']['techniekkeuze']; // phpcs:ignore
			$cursus->vol             = '' != $data['input']['vol']; // phpcs:ignore
			$cursus->vervallen       = '' != $data['input']['vervallen']; // phpcs:ignore
			$cursus->inschrijfkosten = $data['input']['inschrijfkosten'];
			$cursus->cursuskosten    = $data['input']['cursuskosten'];
			$cursus->inschrijfslug   = $data['input']['inschrijfslug'];
			$cursus->indelingslug    = $data['input']['indelingslug'];
			$cursus->technieken      = $data['input']['technieken'];
			$cursus->maximum         = $data['input']['maximum'];
			$cursus->meer            = '' != $data['input']['meer']; // phpcs:ignore
			$cursus->tonen           = '' != $data['input']['tonen']; // phpcs:ignore
			$cursus->save();
			return 'De cursus informatie is opgeslagen';
		}
	}

}
