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
		$gebruikers     = Kleistad_Gebruiker::all();
		$rows           = [];
		$vandaag        = strtotime( 'today' );
		foreach ( $cursussen as $cursus_id => $cursus ) {
			$wachtlijst = [];
			$ingedeeld  = [];

			foreach ( $inschrijvingen as $cursist_id => $inschrijving ) {

				if ( array_key_exists( $cursus_id, $inschrijving ) ) {
					if ( $inschrijving[ $cursus_id ]->geannuleerd ) {
						continue;
					}
					$aantal  = 1 === $inschrijving[ $cursus_id ]->aantal ? '' : ' (' . $inschrijving[ $cursus_id ]->aantal . ')';
					$element = [
						'naam'       => $gebruikers[ $cursist_id ]->voornaam . ' ' . $gebruikers[ $cursist_id ]->achternaam . $aantal,
						'opmerking'  => $inschrijving[ $cursus_id ]->opmerking,
						'technieken' => $inschrijving[ $cursus_id ]->technieken,
						'ingedeeld'  => $inschrijving[ $cursus_id ]->ingedeeld,
						'id'         => $cursist_id,
					];
					if ( $inschrijving[ $cursus->id ]->ingedeeld ) {
						$ingedeeld[ $cursist_id ] = $element;
					} elseif ( $inschrijving[ $cursus_id ]->i_betaald ) {
						$wachtlijst[ $cursist_id ] = $element;
					}
				}
			}
			$rows[] = [
				'cursus'     => [
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
					'status'          => $cursus->vervallen ? 'vervallen' : ( $cursus->eind_datum < $vandaag ? 'voltooid' : ( $cursus->start_datum < $vandaag ? 'actief' : 'nieuw' ) ),
				],
				'wachtlijst' => $wachtlijst,
				'ingedeeld'  => $ingedeeld,
			];
		}
		$data = [
			'rows'       => $rows,
			'gebruikers' => $gebruikers,
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
				INPUT_POST, [
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
		} elseif ( 'indeling' === $tab ) {
			$input              = filter_input_array(
				INPUT_POST, [
					'tab'            => FILTER_SANITIZE_STRING,
					'cursus_id'      => FILTER_SANITIZE_NUMBER_INT,
					'indeling_lijst' => FILTER_SANITIZE_STRING,
				]
			);
			$input['cursisten'] = ( '' === $input['indeling_lijst'] ) ? [] : json_decode( $input['indeling_lijst'], true );
		} elseif ( 'email' === $tab ) {
			$input = filter_input_array(
				INPUT_POST, [
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
		} elseif ( 'indeling' === $data['input']['tab'] ) {
			$aantal_ingedeeld = 0;
			foreach ( $data['input']['cursisten'] as $cursist ) {
				$inschrijving = new Kleistad_Inschrijving( $cursist, $cursus_id );
				if ( ! $inschrijving->ingedeeld ) {
					$aantal_ingedeeld++;
					$inschrijving->ingedeeld = true;
					$inschrijving->save();
					$inschrijving->email( 'indeling' );
				}
			}
			if ( $aantal_ingedeeld > 0 ) {
				return "Emails zijn verstuurd naar $aantal_ingedeeld cursisten";
			}
		} elseif ( 'email' === $data['input']['tab'] ) {
			$inschrijvingen         = Kleistad_Inschrijving::all();
			$aantal_verzonden_email = 0;
			foreach ( $inschrijvingen as $inschrijving ) {
				if ( array_key_exists( $cursus_id, $inschrijving ) ) {
					if ( $inschrijving[ $cursus_id ]->geannuleerd ) {
						continue;
					}
					if ( $inschrijving[ $cursus_id ]->c_betaald ) {
						continue;
					}
					if ( $inschrijving[ $cursus_id ]->ingedeeld ) {
						$aantal_verzonden_email++;
						$inschrijving[ $cursus_id ]->email( 'betaling' );
					}
				}
			}
			if ( $aantal_verzonden_email > 0 ) {
				return "Emails zijn verstuurd naar $aantal_verzonden_email cursisten";
			}
		}
	}

}
