<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
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
class Kleistad_Public_Betalingen extends Kleistad_Public_Shortcode {

	/**
	 *
	 * prepareer 'betalingen' form
	 *
	 * @return array
	 *
	 * @since   4.0.0
	 */
	public function prepare( $data = null ) {
		if ( ! Kleistad_Roles::override() ) {
			return '';
		}

		$rows = [];
		$cursusStore = new Kleistad_Cursussen();
		$cursussen = $cursusStore->get();
		$inschrijvingStore = new Kleistad_Inschrijvingen;
		$inschrijvingen = $inschrijvingStore->get();
		$cursistStore = new Kleistad_Gebruikers();
		$cursisten = $cursistStore->get();

		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if ( ( $cursussen[ $cursus_id ]->eind_datum > time()) and ( ! $inschrijving->i_betaald || ! $inschrijving->c_betaald ) ) {
					$rows[] = [
						'inschrijver_id' => $cursist_id,
						'naam' => $cursisten[ $cursist_id ]->voornaam . ' ' . $cursisten[ $cursist_id ]->achternaam,
						'datum' => $inschrijving->datum,
						'code' => $inschrijving->code,
						'i_betaald' => $inschrijving->i_betaald,
						'value' => $cursist_id . ' ' . $cursus_id,
						'c_betaald' => $inschrijving->c_betaald,
						'geannuleerd' => $inschrijving->geannuleerd,
					];
				}
			}
		}

		return compact( 'rows' );
	}

	/**
	 *
	 * valideer/sanitize 'betalingen' form
	 *
	 * @return array
	 *
	 * @since   4.0.0
	 */
	public function validate() {
		$cursisten = [];

		$i_betalingen = filter_input( INPUT_POST, 'i_betaald', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		if ( ! is_null( $i_betalingen ) ) {
			foreach ( $i_betalingen as $i_betaald ) {
				$atts = explode( ' ', $i_betaald );
				$cursist_id = intval( $atts[0] );
				$cursus_id = intval( $atts[1] );
				$cursisten[ $cursist_id ]['i_betaald'][ $cursus_id ] = 1;
			}
		}
		$c_betalingen = filter_input( INPUT_POST, 'c_betaald', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		if ( ! is_null( $c_betalingen ) ) {
			foreach ( $c_betalingen as $c_betaald ) {
				$atts = explode( ' ', $c_betaald );
				$cursist_id = intval( $atts[0] );
				$cursus_id = intval( $atts[1] );
				$cursisten[ $cursist_id ]['c_betaald'][ $cursus_id ] = 1;
			}
		}
		$annuleringen = filter_input( INPUT_POST, 'geannuleerd', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		if ( ! is_null( $annuleringen ) ) {
			foreach ( $annuleringen as $geannuleerd ) {
				$atts = explode( ' ', $geannuleerd );
				$cursist_id = intval( $atts[0] );
				$cursus_id = intval( $atts[1] );
				$cursisten[ $cursist_id ]['geannuleerd'][ $cursus_id ] = 1;
			}
		}
		return compact( 'cursisten' );
	}

	/**
	 *
	 * bewaar 'betalingen' form gegevens
	 *
	 * @return string
	 *
	 * @since   4.0.0
	 */
	public function save( $data ) {
		extract( $data );

		foreach ( $cursisten as $cursist_id => $cursist ) {
			if ( isset( $cursist['c_betaald'] ) ) {
				foreach ( $cursist['c_betaald'] as $cursus_id => $value ) {
					$inschrijving = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
					$inschrijving->c_betaald = true;
					$inschrijving->save();
				}
			}
			if ( isset( $cursist['i_betaald'] ) ) {
				foreach ( $cursist['i_betaald'] as $cursus_id => $value ) {
					$inschrijving = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
					$inschrijving->i_betaald = true;
					$inschrijving->save();
				}
			}
			if ( isset( $cursist['geannuleerd'] ) ) {
				foreach ( $cursist['geannuleerd'] as $cursus_id => $value ) {
					$inschrijving = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
					$inschrijving->geannuleerd = true;
					$inschrijving->save();
				}
			}
		}

		return 'Betaal informatie is geregistreerd.';
	}

}
