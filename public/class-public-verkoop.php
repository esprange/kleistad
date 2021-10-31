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

use WP_Error;

/**
 * De kleistad verkoop class.
 */
class Public_Verkoop extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'verkoop' form
	 *
	 * @param array $data voor display.
	 */
	protected function prepare( array &$data ) {
		if ( ! isset( $data['input'] ) ) {
			$data               = [];
			$data['input']      = [
				'omschrijving' => [ '' ],
				'aantal'       => [ 1 ],
				'prijs'        => [ 0.0 ],
				'klant'        => '',
				'klant_id'     => 0,
				'email'        => '',
			];
			$data['gebruikers'] = get_users(
				[
					'orderby' => 'display_name',
					'fields'  => [
						'display_name',
						'id',
					],
				]
			);
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'verkoop' form
	 *
	 * @param array $data Gevalideerde data.
	 */
	protected function validate( array &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'klant'        => FILTER_SANITIZE_STRING,
				'klant_id'     => FILTER_SANITIZE_NUMBER_INT,
				'klant_type'   => FILTER_SANITIZE_STRING,
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
	 * @return WP_ERROR|array
	 *
	 * @since   6.2.0
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function save( array $data ) : array {
		$verkoop = new LosArtikel();
		if ( 'bestaand' === $data['input']['klant_type'] ) {
			$klant = get_user_by( 'id', $data['input']['klant_id'] );
			/**
			 * De adres elementen zijn onderdeel gemaakt van het object.
			 *
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$verkoop->klant    = [
				'naam'  => $klant->display_name,
				'adres' => "$klant->straat $klant->huisnr\n$klant->pcode $klant->plaats",
				'email' => $klant->user_email,
			];
			$verkoop->klant_id = $klant->ID;
		} else {
			$verkoop->klant = [
				'naam'  => $data['input']['klant'],
				'adres' => '',
				'email' => $data['input']['email'],
			];
		}
		$index = 0;
		$count = count( $data['input']['omschrijving'] );
		do {
			$verkoop->bestelregel( $data['input']['omschrijving'][ $index ], $data['input']['aantal'][ $index ], $data['input']['prijs'][ $index ] );
		} while ( ++$index < $count );
		$verkoop->verzend_email( '', $verkoop->bestel_order( 0.0, strtotime( '+14 days 0:00' ) ) );
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Er is een email verzonden met factuur en nadere informatie over de betaling' ),
		];
	}
}
