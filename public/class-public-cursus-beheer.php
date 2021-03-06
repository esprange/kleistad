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

namespace Kleistad;

use WP_Error;

/**
 * De Cursus beheer class.
 */
class Public_Cursus_Beheer extends ShortcodeForm {

	/**
	 * Maak de lijst van cursussen
	 *
	 * @return array De cursussen data.
	 */
	private function lijst() : array {
		$cursussen = new Cursussen();
		$lijst     = [];
		$vandaag   = strtotime( 'today' );
		foreach ( $cursussen as $cursus ) {
			$lijst[] = [
				'id'          => $cursus->id,
				'naam'        => $cursus->naam,
				'start_datum' => date( 'd-m-Y', $cursus->start_datum ),
				'eind_datum'  => date( 'd-m-Y', $cursus->eind_datum ),
				'start_tijd'  => date( 'H:i', $cursus->start_tijd ),
				'eind_tijd'   => date( 'H:i', $cursus->eind_tijd ),
				'docent'      => $cursus->docent_naam(),
				'vervallen'   => $cursus->vervallen,
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
	 * @param int|null $cursus_id De cursus.
	 * @return array De cursus data.
	 */
	private function formulier( ?int $cursus_id = null ) : array {
		$cursus = new Cursus( $cursus_id );
		return [
			'id'              => $cursus->id,
			'naam'            => $cursus->naam,
			'code'            => empty( $cursus_id ) ? '' : $cursus->code,
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
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( array &$data ) {
		$data['docenten'] = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'role'    => [ DOCENT ],
				'orderby' => 'display_name',
			]
		);

		if ( 'toevoegen' === $data['actie'] ) {
			/*
			* Er moet een nieuwe cursus opgevoerd worden
			*/
			if ( ! isset( $data['cursus'] ) ) {
				$data['cursus'] = $this->formulier();
			}
			return true;
		}
		if ( 'wijzigen' === $data['actie'] ) {
			/*
			 * Er is een cursus gekozen om te wijzigen.
			 */
			if ( ! isset( $data['cursus'] ) ) {
				$data['cursus'] = $this->formulier( $data['id'] );
			}
			return true;
		}
		$data['cursussen'] = $this->lijst();
		return true;
	}

	/**
	 * Valideer/sanitize 'cursus_beheer' form
	 *
	 * @param array $data gevalideerde data.
	 * @return WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( array &$data ) {
		$error          = new WP_Error();
		$data['cursus'] = filter_input_array(
			INPUT_POST,
			[
				'cursus_id'       => FILTER_SANITIZE_NUMBER_INT,
				'naam'            => FILTER_SANITIZE_STRING,
				'docent'          => FILTER_SANITIZE_STRING,
				'start_datum'     => FILTER_SANITIZE_STRING,
				'eind_datum'      => FILTER_SANITIZE_STRING,
				'lesdatums'       => FILTER_SANITIZE_STRING,
				'start_tijd'      => FILTER_SANITIZE_STRING,
				'eind_tijd'       => FILTER_SANITIZE_STRING,
				'techniekkeuze'   => FILTER_SANITIZE_STRING,
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
		if ( 'verwijderen' === $data['form_actie'] ) {
			return true;
		}
		if ( is_null( $data['cursus']['technieken'] ) ) {
			$data['cursus']['technieken'] = [];
		}
		if ( $data['cursus']['cursuskosten'] < $data['cursus']['inschrijfkosten'] ) {
			$error->add( 'Invoerfout', 'Als er inschrijfkosten zijn dan kunnen de cursuskosten niet lager zijn' );
		}
		if ( ! is_null( $data['cursus']['tonen'] ) ) {
			foreach ( [ $data['cursus']['inschrijfslug'], $data['cursus']['indelingslug'] ] as $slug ) {
				if ( is_null( get_page_by_title( $slug, OBJECT, Email::POST_TYPE ) ) ) { // phpcs:ignore
					$error->add( 'Invoerfout', 'Er bestaat nog geen pagina met de naam ' . $data['cursus']['inschrijfslug'] );
				}
			}
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Verwijder de cursus
	 *
	 * @param array $data data te verwijderen.
	 *
	 * @return array
	 */
	protected function verwijderen( array $data ) : array {
		$cursus = new Cursus( $data['cursus']['cursus_id'] );
		if ( count( new Inschrijvingen( $cursus->id, true ) ) ) {
			return [
				'status' => $this->status( new WP_Error( 'ingedeeld', 'Er zijn al cursisten inschrijvingen, de cursus kan niet verwijderd worden' ) ),
			];
		}
		$cursus->erase();
		return [
			'status'  => $this->status( 'De cursus informatie is verwijderd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Bewaar de cursus
	 *
	 * @param array $data date te bewaren.
	 *
	 * @return array
	 */
	protected function bewaren( array $data ) : array {
		$cursus_id               = $data['cursus']['cursus_id'];
		$cursus                  = $cursus_id > 0 ? new Cursus( $cursus_id ) : new Cursus();
		$cursus->naam            = $data['cursus']['naam'];
		$cursus->docent          = $data['cursus']['docent'];
		$cursus->start_datum     = strtotime( $data['cursus']['start_datum'] );
		$cursus->eind_datum      = strtotime( $data['cursus']['eind_datum'] );
		$cursus->lesdatums       = array_map(
			function( $lesdatum ) {
				return strtotime( $lesdatum );
			},
			explode( ';', $data['cursus']['lesdatums'] )
		);
		$cursus->start_tijd      = strtotime( $data['cursus']['start_tijd'] );
		$cursus->eind_tijd       = strtotime( $data['cursus']['eind_tijd'] );
		$cursus->techniekkeuze   = '' != $data['cursus']['techniekkeuze']; // phpcs:ignore
		$cursus->vervallen       = '' != $data['cursus']['vervallen']; // phpcs:ignore
		$cursus->inschrijfkosten = $data['cursus']['inschrijfkosten'];
		$cursus->cursuskosten    = $data['cursus']['cursuskosten'];
		$cursus->inschrijfslug   = $data['cursus']['inschrijfslug'];
		$cursus->indelingslug    = $data['cursus']['indelingslug'];
		$cursus->technieken      = $data['cursus']['technieken'];
		$cursus->maximum         = $data['cursus']['maximum'];
		$cursus->meer            = '' != $data['cursus']['meer']; // phpcs:ignore
		$cursus->tonen           = '' != $data['cursus']['tonen']; // phpcs:ignore
		$cursus->vol             = 0 === $cursus->ruimte();
		$cursus->save();
		return [
			'status'  => $this->status( 'De cursus informatie is opgeslagen' ),
			'content' => $this->display(),
		];
	}

}
