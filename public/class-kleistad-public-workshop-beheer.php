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

/**
 * De kleistad workshop class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Workshop_Beheer extends Kleistad_ShortcodeForm {

	/**
	 * Prepareer 'workshop' form
	 *
	 * @param array $data data voor display.
	 * @return \WP_ERROR|bool
	 *
	 * @since   5.0.0
	 */
	public function prepare( &$data = null ) {
		$data['workshop'] = [];
		$workshops        = Kleistad_Workshop::all();
		foreach ( $workshops as $workshop ) {
			$data['workshop'][] = [
				'id'          => $workshop->id,
				'code'        => $workshop->code,
				'naam'        => $workshop->naam,
				'datum'       => date( 'd-m-Y', $workshop->datum ),
				'datum_td'    => $workshop->datum,
				'start_tijd'  => date( 'H:i', $workshop->start_tijd ),
				'eind_tijd'   => date( 'H:i', $workshop->eind_tijd ),
				'docent'      => $workshop->docent,
				'technieken'  => $workshop->technieken,
				'organisatie' => $workshop->organisatie,
				'contact'     => $workshop->contact,
				'email'       => $workshop->email,
				'telefoon'    => $workshop->telefoon,
				'programma'   => $workshop->programma,
				'vervallen'   => $workshop->vervallen,
				'kosten'      => $workshop->kosten,
				'aantal'      => $workshop->aantal,
				'betaald'     => $workshop->betaald,
				'definitief'  => $workshop->definitief,
				'status'      => $workshop->vervallen ? 'vervallen' : ( ( $workshop->definitief ? 'definitief ' : 'concept' ) . ( $workshop->betaald ? 'betaald' : '' ) ),
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
	 * Valideer/sanitize 'workshop' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   5.0.0
	 */
	public function validate( &$data ) {
		$error            = new WP_Error();
		$data['workshop'] = filter_input_array(
			INPUT_POST,
			[
				'id'          => FILTER_SANITIZE_NUMBER_INT,
				'naam'        => FILTER_SANITIZE_STRING,
				'datum'       => FILTER_SANITIZE_STRING,
				'start_tijd'  => FILTER_SANITIZE_STRING,
				'eind_tijd'   => FILTER_SANITIZE_STRING,
				'docent'      => FILTER_SANITIZE_STRING,
				'technieken'  => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'organisatie' => FILTER_SANITIZE_STRING,
				'contact'     => FILTER_SANITIZE_STRING,
				'email'       => FILTER_SANITIZE_EMAIL,
				'telefoon'    => FILTER_SANITIZE_STRING,
				'vervallen'   => FILTER_SANITIZE_STRING,
				'kosten'      => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'aantal'      => FILTER_SANITIZE_NUMBER_INT,
			]
		);

		$data['workshop']['programma'] = sanitize_textarea_field( filter_input( INPUT_POST, 'programma' ) );
		if ( empty( $data['workshop']['technieken'] ) ) {
			$data['workshop']['technieken'] = [];
		}
		if ( strtotime( $data['workshop']['start_tijd'] ) >= strtotime( $data['workshop']['eind_tijd'] ) ) {
			$error->add( 'Invoerfout', 'De starttijd moet voor de eindtijd liggen' );
		}

		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 *
	 * Bewaar 'workshop' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_Error|string
	 *
	 * @since   5.0.0
	 */
	public function save( $data ) {
		$workshop_id = $data['workshop']['id'];

		if ( $workshop_id > 0 ) {
			$workshop = new Kleistad_Workshop( $workshop_id );
		} else {
			$workshop = new Kleistad_Workshop();
		}
		$workshop->naam        = $data['workshop']['naam'];
		$workshop->datum       = strtotime( $data['workshop']['datum'] );
		$workshop->start_tijd  = strtotime( $data['workshop']['start_tijd'] );
		$workshop->eind_tijd   = strtotime( $data['workshop']['eind_tijd'] );
		$workshop->docent      = $data['workshop']['docent'];
		$workshop->technieken  = $data['workshop']['technieken'];
		$workshop->organisatie = $data['workshop']['organisatie'];
		$workshop->contact     = $data['workshop']['contact'];
		$workshop->email       = $data['workshop']['email'];
		$workshop->telefoon    = $data['workshop']['telefoon'];
		$workshop->programma   = $data['workshop']['programma'];
		$workshop->vervallen   = '' != $data['workshop']['vervallen']; // phpcs:ignore
		$workshop->kosten      = $data['workshop']['kosten'];
		$workshop->aantal      = $data['workshop']['aantal'];

		switch ( $data['form_actie'] ) {
			case 'opslaan':
				$workshop->save();
				return 'Gegevens zijn opgeslagen';
			case 'bevestigen':
				$workshop->bevestig();
				return 'Gegevens zijn opgeslagen en een bevestigingsemail is verstuurd';
			case 'afzeggen':
				$workshop->afzeggen();
				return 'De afspraak voor de workshop is per email afgezegd';
			default:
				break;
		}
	}
}
