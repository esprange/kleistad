<?php
/**
 * Shortcode verkoop (losse overige artikelen).
 *
 * @link       https://www.kleistad.nl
 * @since      6.20
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad verkoop class.
 */
class Public_Verkoop extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'verkoop' form
	 *
	 * @param array $data voor display.
	 * @return bool
	 *
	 * @since   6.2.0
	 */
	protected function prepare( &$data ) {
		$gebruiker_id = get_current_user_id();
		if ( ! isset( $data['input'] ) ) {
			$data          = [];
			$data['input'] = [
				'omschrijving' => [ '' ],
				'aantal'       => [ 1 ],
				'prijs'        => [ 0.0 ],
				'klant'        => '',
				'email'        => '',
			];
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'verkoop' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return bool
	 *
	 * @since   6.2.0
	 */
	protected function validate( &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'klant'        => FILTER_SANITIZE_STRING,
				'email'        => FILTER_SANITIZE_EMAIL,
				'omschrijving' => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'aantal'       => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION | FILTER_REQUIRE_ARRAY,
				],
				'prijs'        => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION | FILTER_REQUIRE_ARRAY,
				],
			]
		);
		return true;
	}

	/**
	 * Bewaar 'verkoop' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return \WP_ERROR|array
	 *
	 * @since   6.2.0
	 */
	protected function save( $data ) {
		$verkoopnr = get_option( 'kleistad_losnr', 0 );
		if ( ! update_option( 'kleistad_losnr', ++$verkoopnr ) ) {
			return [ 'status' => $this->status( new \WP_Error( 'intern', 'Er is iets fout gegaan, probeer het opnieuw' ) ) ];
		}
		$verkoop        = new \Kleistad\LosArtikel( $verkoopnr );
		$verkoop->klant = [
			'naam'  => $data['input']['klant'],
			'email' => $data['input']['email'],
		];
		$index          = 0;
		$count          = count( $data['input']['omschrijving'] );
		do {
			$verkoop->bestelregel( $data['input']['omschrijving'][ $index ], $data['input']['aantal'][ $index ], $data['input']['prijs'][ $index ] );
		} while ( ++$index < $count );
		$verkoop->email( '', $verkoop->bestel_order( 0.0, 'overig' ) );
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Er is een email verzonden met factuur en nadere informatie over de betaling' ),
		];
	}
}
