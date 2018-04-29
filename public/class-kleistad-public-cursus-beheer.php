<?php
/**
 * The public-facing functionality of the plugin for shortcode cursus_beheer.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin for shortcode cursus_beheer.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Cursus_Beheer extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'cursus_beheer' form
	 *
	 * @param array $data data to be preapred.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$cursus_store    = new Kleistad_Cursussen();
		$cursussen       = $cursus_store->get();

		$inschrijving_store  = new Kleistad_Inschrijvingen();
		$inschrijvingen      = $inschrijving_store->get();

		$gebruiker_store = new Kleistad_Gebruikers();
		$gebruikers      = $gebruiker_store->get();
		$rows            = [];

		foreach ( $cursussen as $cursus_id => $cursus ) {
			$wachtlijst  = [];
			$ingedeeld   = [];

			foreach ( $inschrijvingen as $cursist_id => $inschrijving ) {

				if ( array_key_exists( $cursus_id, $inschrijving ) ) {
					if ( $inschrijving[ $cursus_id ]->geannuleerd ) {
						continue;
					}
					$element = [
						'naam'       => $gebruikers[ $cursist_id ]->voornaam . ' ' . $gebruikers[ $cursist_id ]->achternaam,
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
					'id'                 => $cursus->id,
					'naam'               => $cursus->naam,
					'start_datum'        => date( 'd-m-Y', $cursus->start_datum ),
					'eind_datum'         => date( 'd-m-Y', $cursus->eind_datum ),
					'start_tijd'         => date( 'H:i', $cursus->start_tijd ),
					'eind_tijd'          => date( 'H:i', $cursus->eind_tijd ),
					'docent'             => $cursus->docent,
					'technieken'         => $cursus->technieken,
					'vervallen'          => $cursus->vervallen,
					'vol'                => $cursus->vol,
					'techniekkeuze'      => $cursus->techniekkeuze,
					'inschrijfkosten'    => $cursus->inschrijfkosten,
					'cursuskosten'       => $cursus->cursuskosten,
					'inschrijfslug'      => $cursus->inschrijfslug,
					'indelingslug'       => $cursus->indelingslug,
					'maximum'            => $cursus->maximum,
					'meer'               => $cursus->meer,
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
	 * @param array $data Returned data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function validate( &$data ) {

		$tab = filter_input( INPUT_POST, 'tab', FILTER_SANITIZE_STRING );
		if ( 'info' === $tab ) {
			$input = filter_input_array(
				INPUT_POST, [
					'tab'                => FILTER_SANITIZE_STRING,
					'cursus_id'          => FILTER_SANITIZE_NUMBER_INT,
					'naam'               => FILTER_SANITIZE_STRING,
					'docent'             => FILTER_SANITIZE_STRING,
					'start_datum'        => FILTER_SANITIZE_STRING,
					'eind_datum'         => FILTER_SANITIZE_STRING,
					'start_tijd'         => FILTER_SANITIZE_STRING,
					'eind_tijd'          => FILTER_SANITIZE_STRING,
					'techniekkeuze'      => FILTER_SANITIZE_STRING,
					'vol'                => FILTER_SANITIZE_STRING,
					'vervallen'          => FILTER_SANITIZE_STRING,
					'inschrijfkosten'    => [
						'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
						'flags'  => FILTER_FLAG_ALLOW_FRACTION,
					],
					'cursuskosten'       => [
						'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
						'flags'  => FILTER_FLAG_ALLOW_FRACTION,
					],
					'inschrijfslug'      => FILTER_SANITIZE_STRING,
					'indelingslug'       => FILTER_SANITIZE_STRING,
					'technieken'         => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_REQUIRE_ARRAY,
					],
					'maximum'            => FILTER_SANITIZE_NUMBER_INT,
					'meer'               => FILTER_SANITIZE_STRING,
				]
			);
		} elseif ( 'indeling' === $tab ) {
			$input               = filter_input_array(
				INPUT_POST, [
					'tab'            => FILTER_SANITIZE_STRING,
					'cursus_id'      => FILTER_SANITIZE_NUMBER_INT,
					'indeling_lijst' => FILTER_SANITIZE_STRING,
				]
			);
			$input['cursisten']  = ( '' === $input['indeling_lijst'] ) ? [] : json_decode( $input['indeling_lijst'], true );
		}
		$data = [
			'input' => $input,
		];
		return true;
	}

	/**
	 * Bewaar 'cursus_beheer' form gegevens
	 *
	 * @param array $data data to be saved.
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
		}
	}

}
