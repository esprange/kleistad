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
		$rows           = [];
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
			$rows[] = [
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
		$data = [
			'rows' => $rows,
		];
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
			$input = filter_input_array(
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
			if ( is_null( $input['technieken'] ) ) {
				$input['technieken'] = [];
			}
			/**
			 * Controleer of de nieuwe cursus al niet bestaat.
			 */
			if ( ! ( 0 < $input['cursus_id'] ) ) {
				$start_datum = strftime( '%d-%m', strtotime( $input['start_datum'] ) );
				$start_tijd  = strftime( '%H-%M', strtotime( $input['start_tijd'] ) );
				$cursussen   = Kleistad_Cursus::all();
				foreach ( $cursussen as $cursus ) {
					if ( ! $cursus->vervallen ) {
						if ( strftime( '%d-%m', $cursus->start_datum ) === $start_datum &&
							strftime( '%H-%M', $cursus->start_tijd ) === $start_tijd ) {
							$error->add( 'dubbel', 'Er is al een cursus die op deze datum/tijd van start gaat' );
							return $error;
						}
					}
				}
			}
		} elseif ( 'indeling' === $tab ) { // phpcs:ignore
			/**
			 * Voorlopig geen actie op dit tabblad.
			 */
		} elseif ( 'email' === $tab ) {
			$input = filter_input_array(
				INPUT_POST,
				[
					'tab'       => FILTER_SANITIZE_STRING,
					'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
				]
			);
		}
		$data = [
			'input' => $input,
		];
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
			$cursus->techniekkeuze   = '' != $data['input']['techniekkeuze']; // WPCS: loose comparison ok.
			$cursus->vol             = '' != $data['input']['vol']; // WPCS: loose comparison ok.
			$cursus->vervallen       = '' != $data['input']['vervallen']; // WPCS: loose comparison ok.
			$cursus->inschrijfkosten = $data['input']['inschrijfkosten'];
			$cursus->cursuskosten    = $data['input']['cursuskosten'];
			$cursus->inschrijfslug   = $data['input']['inschrijfslug'];
			$cursus->indelingslug    = $data['input']['indelingslug'];
			$cursus->technieken      = $data['input']['technieken'];
			$cursus->maximum         = $data['input']['maximum'];
			$cursus->meer            = '' != $data['input']['meer']; // WPCS: loose comparison ok.
			$cursus->tonen           = '' != $data['input']['tonen']; // WPCS: loose comparison ok.
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
