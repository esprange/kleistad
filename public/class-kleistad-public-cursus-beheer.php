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
	 *
	 * Prepareer 'cursus_beheer' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$cursussen      = Kleistad_Cursus::all();
		$inschrijvingen = Kleistad_Inschrijving::all();
		$data['rows']   = [];
		$vandaag        = strtotime( 'today' );
		foreach ( $cursussen as $cursus_id => $cursus ) {
			$ingedeeld = [];

			foreach ( $inschrijvingen as $cursist_id => $inschrijving ) {

				if ( array_key_exists( $cursus_id, $inschrijving ) ) {
					if ( $inschrijving[ $cursus_id ]->geannuleerd ) {
						continue;
					}
					if ( $inschrijving[ $cursus->id ]->ingedeeld ) {
						$cursist                  = get_userdata( $cursist_id );
						$ingedeeld[ $cursist_id ] = [
							'naam'          => $cursist->display_name . ( 1 === intval( $inschrijving[ $cursus_id ]->aantal ) ? '' : ' (' . $inschrijving[ $cursus_id ]->aantal . ')' ),
							'extra_info'    => ( 0 < count( $inschrijving[ $cursus_id ]->technieken ) ?
								'Technieken: ' . implode( ', ', $inschrijving[ $cursus_id ]->technieken ) . '; ' : '' ) . $inschrijving[ $cursus_id ]->opmerking,
							'i_betaald'     => $inschrijving[ $cursus_id ]->i_betaald,
							'c_betaald'     => $inschrijving[ $cursus_id ]->c_betaald,
							'restant_email' => $inschrijving[ $cursus_id ]->restant_email,
							'id'            => $cursist_id,
						];
					}
				}
			}
			$data['rows'][] = [
				'cursus'    => [
					'id'              => $cursus->id,
					'naam'            => $cursus->naam,
					'start_datum'     => date( 'd-m-Y', $cursus->start_datum ),
					'eind_datum'      => date( 'd-m-Y', $cursus->eind_datum ),
					'start_tijd'      => date( 'H:i', $cursus->start_tijd ),
					'eind_tijd'       => date( 'H:i', $cursus->eind_tijd ),
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
					'lopend'          => ( $cursus->start_datum < strtotime( 'today' ) ),
					'gedeeld'         => ( 0 < $cursus->inschrijfkosten ),
					'status'          => $cursus->vervallen ? 'vervallen' :
						( $cursus->eind_datum < $vandaag ? 'voltooid' :
						( $cursus->start_datum < $vandaag ? 'actief' : 'nieuw' ) ),
				],
				'ingedeeld' => $ingedeeld,
			];
		}
		$gebruikers = get_users(
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
	public function validate( &$data ) {
		$error = new WP_Error();
		$tab   = filter_input( INPUT_POST, 'tab', FILTER_SANITIZE_STRING );
		if ( 'info' === $tab ) {
			$data['input'] = filter_input_array(
				INPUT_POST,
				[
					'tab'             => FILTER_SANITIZE_STRING,
					'cursus_id'       => FILTER_SANITIZE_NUMBER_INT,
					'naam'            => FILTER_SANITIZE_STRING,
					'docent'          => FILTER_SANITIZE_STRING,
					'start_datum'     => FILTER_SANITIZE_STRING,
					'eind_datum'      => FILTER_SANITIZE_STRING,
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
		} elseif ( 'indeling' === $tab ) { // phpcs:ignore
			/**
			 * Voorlopig geen actie op dit tabblad.
			 */
		} elseif ( 'email' === $tab ) {
			$data['input'] = filter_input_array(
				INPUT_POST,
				[
					'tab'       => FILTER_SANITIZE_STRING,
					'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
				]
			);
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
	public function save( $data ) {
		$cursus_id = $data['input']['cursus_id'];

		if ( $cursus_id > 0 ) {
			$cursus = new Kleistad_Cursus( $cursus_id );
		} else {
			$cursus = new Kleistad_Cursus();
		}

		if ( 'info' === $data['input']['tab'] ) {
			$cursus->naam            = $data['input']['naam'];
			$cursus->docent          = $data['input']['docent'];
			$cursus->start_datum     = strtotime( $data['input']['start_datum'] );
			$cursus->eind_datum      = strtotime( $data['input']['eind_datum'] );
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
			return 'Gegevens zijn opgeslagen';
		} elseif ( 'indeling' === $data['input']['tab'] ) { // phpcs:ignore
			/**
			 * Geen actie voorlopig op dit tabblad.
			 */
		} elseif ( 'email' === $data['input']['tab'] ) {
			$inschrijvingen         = Kleistad_Inschrijving::all();
			$aantal_verzonden_email = 0;
			foreach ( $inschrijvingen as $inschrijving ) {
				if ( array_key_exists( $cursus_id, $inschrijving ) ) {
					if (
						( $inschrijving[ $cursus_id ]->geannuleerd ) ||
						( $inschrijving[ $cursus_id ]->c_betaald ) ||
						( $inschrijving[ $cursus_id ]->restant_email )
					) {
						continue;
					}
					if ( $inschrijving[ $cursus_id ]->ingedeeld ) {
						$aantal_verzonden_email++;
						$inschrijving[ $cursus_id ]->email( 'betaling' );
						$inschrijving[ $cursus_id ]->restant_email = true;
						$inschrijving[ $cursus_id ]->save();
					}
				}
			}
			if ( $aantal_verzonden_email > 0 ) {
				return "Emails zijn verstuurd naar $aantal_verzonden_email cursisten";
			} else {
				return 'Er zijn geen nieuwe emails verzonden';
			}
		}
	}

}
