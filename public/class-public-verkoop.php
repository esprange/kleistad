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
class Public_Verkoop extends Public_Bestelling {

	/**
	 * Prepareer 'verkoop' form
	 *
	 * @return string
	 */
	protected function prepare() : string {
		if ( ! isset( $this->data['input'] ) ) {
			$this->data = [
				'input'      => [
					'omschrijving' => [ '' ],
					'aantal'       => [ 1 ],
					'prijs'        => [ 0.0 ],
					'klant'        => '',
					'klant_id'     => 0,
					'email'        => '',
				],
				'gebruikers' => get_users(
					[
						'orderby' => 'display_name',
						'fields'  => [
							'display_name',
							'id',
						],
					]
				),
			];
		}
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'verkoop' form
	 *
	 * @return array
	 */
	public function process() :array {
		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'klant'         => FILTER_SANITIZE_STRING,
				'klant_id'      => FILTER_SANITIZE_NUMBER_INT,
				'klant_type'    => FILTER_SANITIZE_STRING,
				'email'         => FILTER_SANITIZE_EMAIL,
				'saldo_verkoop' => FILTER_SANITIZE_STRING,
				'omschrijving'  => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'aantal'        => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION | FILTER_REQUIRE_ARRAY,
				],
				'prijs'         => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION | FILTER_REQUIRE_ARRAY,
				],
			]
		);
		return $this->save();
	}

	/**
	 * Bewaar 'verkoop' form gegevens
	 *
	 * @return array
	 *
	 * @since   6.2.0
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function save() : array {
		if ( $this->data['input']['saldo_verkoop'] && 'bestaand' === $this->data['input']['klant_type'] ) {
			return $this->saldo_verkoop();
		}
		return $this->losartikel_verkoop();
	}

	/**
	 * Voor een saldo verkoop in.
	 *
	 * @return array
	 */
	private function saldo_verkoop() : array {
		$klant = get_user_by( 'id', $this->data['input']['klant_id'] );
		$saldo = new Saldo( $klant->ID );
		foreach ( array_keys( $this->data['input']['omschrijving'] ) as $index ) {
			$saldo->actie->verkoop(
				$this->data['input']['aantal'][ $index ] * $this->data['input']['prijs'][ $index ],
				$this->data['input']['omschrijving'][ $index ]
			);
		}
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'De aankoop is verwerkt op het stook/materialen saldo' ),
		];
	}

	/**
	 * Voor een losartikel verkoop in.
	 *
	 * @return array
	 */
	private function losartikel_verkoop() : array {
		$verkoop = new LosArtikel();
		if ( 'bestaand' === $this->data['input']['klant_type'] ) {
			$klant = get_user_by( 'id', $this->data['input']['klant_id'] );
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
				'naam'  => $this->data['input']['klant'],
				'adres' => '',
				'email' => $this->data['input']['email'],
			];
		}
		foreach ( array_keys( $this->data['input']['omschrijving'] ) as $index ) {
			$verkoop->bestelregel( $this->data['input']['omschrijving'][ $index ], $this->data['input']['aantal'][ $index ], $this->data['input']['prijs'][ $index ] );
		}
		$verkoop->save();
		$order = new Order( $verkoop->get_referentie() );
		$verkoop->verzend_email( '', $order->bestel() );
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Er is een email verzonden met factuur en nadere informatie over de betaling' ),
		];
	}
}
