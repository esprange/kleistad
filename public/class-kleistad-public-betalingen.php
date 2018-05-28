<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Betalingen extends Kleistad_Shortcode {

	/**
	 * Prepareer 'betalingen' form
	 *
	 * @param array $data data to be prepared.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		if ( ! Kleistad_Roles::override() ) {
			return true;
		}

		$rows               = [];
		$cursus_store       = new Kleistad_Cursussen();
		$cursussen          = $cursus_store->get();
		$inschrijving_store = new Kleistad_Inschrijvingen();
		$inschrijvingen     = $inschrijving_store->get();
		$cursist_store      = new Kleistad_Gebruikers();
		$cursisten          = $cursist_store->get();

		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if ( ( $cursussen[ $cursus_id ]->eind_datum > time() ) && ( ! $inschrijving->i_betaald || ! $inschrijving->c_betaald ) ) {
					$rows[] = [
						'inschrijver_id' => $cursist_id,
						'naam'           => $cursisten[ $cursist_id ]->voornaam . ' ' . $cursisten[ $cursist_id ]->achternaam,
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
	 * @param array $data Returned data.
	 * @return array
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
	 * @param array $data data to be saved.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		foreach ( $data['cursisten'] as $cursist_id => $cursist ) {
			if ( isset( $cursist['c_betaald'] ) ) {
				foreach ( $cursist['c_betaald'] as $cursus_id => $value ) {
					$inschrijving            = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
					$inschrijving->c_betaald = true;
					$inschrijving->save();
				}
			}
			if ( isset( $cursist['i_betaald'] ) ) {
				foreach ( $cursist['i_betaald'] as $cursus_id => $value ) {
					$inschrijving            = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
					$inschrijving->i_betaald = true;
					$inschrijving->save();
				}
			}
			if ( isset( $cursist['geannuleerd'] ) ) {
				foreach ( $cursist['geannuleerd'] as $cursus_id => $value ) {
					$inschrijving              = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
					$inschrijving->geannuleerd = true;
					$inschrijving->save();
				}
			}
		}

		return 'Betaal informatie is geregistreerd.';
	}

}
