<?php
/**
 * De shortcode betalingen (overzicht en formulier).
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De kleistad betalingen class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Betalingen extends Kleistad_ShortcodeForm {

	/**
	 * Prepareer 'betalingen' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		if ( ! Kleistad_Roles::override() ) {
			return true;
		}

		$rows           = [];
		$cursussen      = Kleistad_Cursus::all();
		$inschrijvingen = Kleistad_Inschrijving::all();

		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if ( ( $cursussen[ $cursus_id ]->eind_datum > strtotime( '-7 days' ) ) &&
					( ! $inschrijving->i_betaald || ! $inschrijving->c_betaald ) &&
					( ! ( $inschrijving->geannuleerd && $cursussen[ $cursus_id ]->start_datum < strtotime( 'today' ) ) )
					) {
					$cursist = get_userdata( $cursist_id );
					$rows[]  = [
						'inschrijver_id' => $cursist_id,
						'naam'           => $cursist->display_name,
						'datum'          => $inschrijving->datum,
						'code'           => $inschrijving->code,
						'i_betaald'      => $inschrijving->i_betaald,
						'value'          => $cursist_id . ' ' . $cursus_id,
						'c_betaald'      => $inschrijving->c_betaald,
						'geannuleerd'    => $inschrijving->geannuleerd,
					];
				}
			}
		}
		$data = [
			'rows' => $rows,
		];
		return true;
	}

	/**
	 *
	 * Valideer/sanitize 'betalingen' form
	 *
	 * @param array $data gevalideerde data.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function validate( &$data ) {
		$cursisten = [];

		$i_betalingen = filter_input( INPUT_POST, 'i_betaald', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		if ( ! is_null( $i_betalingen ) ) {
			foreach ( $i_betalingen as $i_betaald ) {
				$atts       = explode( ' ', $i_betaald );
				$cursist_id = intval( $atts[0] );
				$cursus_id  = intval( $atts[1] );
				$cursisten[ $cursist_id ]['i_betaald'][ $cursus_id ] = 1;
			}
		}
		$c_betalingen = filter_input( INPUT_POST, 'c_betaald', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		if ( ! is_null( $c_betalingen ) ) {
			foreach ( $c_betalingen as $c_betaald ) {
				$atts       = explode( ' ', $c_betaald );
				$cursist_id = intval( $atts[0] );
				$cursus_id  = intval( $atts[1] );
				$cursisten[ $cursist_id ]['c_betaald'][ $cursus_id ] = 1;
			}
		}
		$annuleringen = filter_input( INPUT_POST, 'geannuleerd', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		if ( ! is_null( $annuleringen ) ) {
			foreach ( $annuleringen as $geannuleerd ) {
				$atts       = explode( ' ', $geannuleerd );
				$cursist_id = intval( $atts[0] );
				$cursus_id  = intval( $atts[1] );
				$cursisten[ $cursist_id ]['geannuleerd'][ $cursus_id ] = 1;
			}
		}
		$data = [
			'cursisten' => $cursisten,
		];
		return true;
	}

	/**
	 * Bewaar 'betalingen' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		foreach ( $data['cursisten'] as $cursist_id => $cursist ) {
			if ( isset( $cursist['geannuleerd'] ) ) {
				foreach ( $cursist['geannuleerd'] as $cursus_id => $value ) {
					$inschrijving              = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
					$inschrijving->geannuleerd = true;
					$inschrijving->save();
				}
			}
			if ( isset( $cursist['c_betaald'] ) ) {
				foreach ( $cursist['c_betaald'] as $cursus_id => $value ) {
					$inschrijving = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
					if ( ! $inschrijving->c_betaald ) {
						$inschrijving->c_betaald = true;
						if ( ! $inschrijving->ingedeeld && ! $inschrijving->geannuleerd ) {
							$inschrijving->ingedeeld = true;
							$cursus                  = new Kleistad_Cursus( $cursus_id );
							if ( strtotime( 'today' ) < $cursus->start_datum ) {
								// Alleen email versturen als de cursus nog niet gestart is.
								$inschrijving->email( 'indeling' );
							}
						}
						$inschrijving->save();
					}
				}
			}
			if ( isset( $cursist['i_betaald'] ) ) {
				foreach ( $cursist['i_betaald'] as $cursus_id => $value ) {
					$inschrijving = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
					if ( ! $inschrijving->i_betaald ) {
						$inschrijving->i_betaald = true;
						if ( ! $inschrijving->ingedeeld && ! $inschrijving->geannuleerd ) {
							$inschrijving->ingedeeld = true;
							$inschrijving->email( 'indeling' );
						}
						$inschrijving->save();
					}
				}
			}
		}
		return 'Betaal informatie is geregistreerd.';
	}
}
