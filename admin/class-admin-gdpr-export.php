<?php
/**
 * De admin-specifieke functies voor compliancy met de AVG wetgeving.
 *
 * @link https://www.kleistad.nl
 * @since 5.20
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * GDPR Export class
 */
class Admin_GDPR_Export {

	/**
	 * Exporteer persoonlijke data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Het email adres van de te exporteren persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 */
	public function exporter( string $email, int $page = 1 ) : array {
		$export_items = [];
		$gebruiker_id = email_exists( $email );
		if ( false !== $gebruiker_id ) {
			$gebruiker = get_userdata( $gebruiker_id );
			/**
			 * De velden telnr, straat etc. zijn wel degelijk toegestaan, phpstorm geeft hier ten onrechte een waarschuwing.
			 *
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$export_items = array_merge(
				[
					[
						'group_id'    => 'contactinfo',
						'group_label' => 'Contact informatie',
						'item_id'     => 'contactinfo',
						'data'        => [
							[
								'name'  => 'Telefoonnummer',
								'value' => $gebruiker->telnr,
							],
							[
								'name'  => 'Straat',
								'value' => $gebruiker->straat,
							],
							[
								'name'  => 'Nummer',
								'value' => $gebruiker->huisnr,
							],
							[
								'name'  => 'Postcode',
								'value' => $gebruiker->pcode,
							],
							[
								'name'  => 'Plaats',
								'value' => $gebruiker->plaats,
							],
						],
					],
				],
				$this->export_inschrijving( $gebruiker_id ),
				$this->export_abonnement( $gebruiker_id ),
				$this->export_saldo( $gebruiker_id ),
				$this->export_reservering( $gebruiker_id ),
				$this->export_dagdelenkaart( $gebruiker_id )
			);
		}
		// Geef aan of er nog meer te exporteren valt, de controle op page nummer is een dummy.
		$done = ( 1 === $page ); // Dummy actie.
		return [
			'data' => $export_items,
			'done' => $done,
		];
	}

	/**
	 * Export de inschrijving
	 *
	 * @since 4.3.0
	 *
	 * @param int $gebruiker_id Id van de gebruiker.
	 * @return array De persoonlijke data (cursus info).
	 */
	private function export_inschrijving( int $gebruiker_id ) : array {
		$cursist = new Cursist( $gebruiker_id );
		$items   = [];
		foreach ( $cursist->inschrijvingen as $inschrijving ) {
			$items[] = [
				'group_id'    => 'cursusinfo',
				'group_label' => 'Cursussen informatie',
				'item_id'     => 'cursus-' . $inschrijving->cursus->id,
				'data'        => [
					[
						'name'  => 'Cursus',
						'value' => $inschrijving->cursus->naam,
					],
					[
						'name'  => 'Aanmeld datum',
						'value' => wp_date( 'd-m-y', $inschrijving->datum ),
					],
					[
						'name'  => 'Opmerking',
						'value' => $inschrijving->opmerking,
					],
					[
						'name'  => 'Ingedeeld',
						'value' => $inschrijving->ingedeeld ? 'ja' : 'nee',
					],
					[
						'name'  => 'Geannuleerd',
						'value' => $inschrijving->geannuleerd ? 'ja' : 'nee',
					],
				],
			];
		}
		return $items;
	}

	/**
	 * Export het abonnement.
	 *
	 * @since 4.3.0
	 *
	 * @param  int $gebruiker_id Het wp user id van de abonnee.
	 * @return array De persoonlijke data (abonnement info).
	 */
	private function export_abonnement( int $gebruiker_id ) : array {
		$abonnement = new Abonnement( $gebruiker_id );
		$items      = [];
		$items[]    = [
			'group_id'    => 'abonnementinfo',
			'group_label' => 'Abonnement informatie',
			'item_id'     => 'abonnement-1',
			'data'        => [
				[
					'name'  => 'Aanmeld datum',
					'value' => wp_date( 'd-m-y', $abonnement->datum ),
				],
				[
					'name'  => 'Start datum',
					'value' => $abonnement->start_datum > 0 ? wp_date( 'd-m-y', $abonnement->start_datum ) : '',
				],
				[
					'name'  => 'Eind datum',
					'value' => $abonnement->eind_datum > 0 ? wp_date( 'd-m-y', $abonnement->eind_datum ) : '',
				],
				[
					'name'  => 'Pauze datum',
					'value' => $abonnement->pauze_datum > 0 ? wp_date( 'd-m-y', $abonnement->pauze_datum ) : '',
				],
				[
					'name'  => 'Herstart datum',
					'value' => $abonnement->herstart_datum > 0 ? wp_date( 'd-m-y', $abonnement->herstart_datum ) : '',
				],
				[
					'name'  => 'Opmerking',
					'value' => $abonnement->opmerking,
				],
				[
					'name'  => 'Soort abonnement',
					'value' => $abonnement->soort,
				],
			],
		];
		return $items;
	}

	/**
	 * Export stooksaldo.
	 *
	 * @since      4.3.0
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (stooksaldo).
	 */
	private function export_saldo( int $gebruiker_id ) : array {
		$saldo   = new Saldo( $gebruiker_id );
		$items   = [];
		$items[] = [
			'group_id'    => 'stooksaldo',
			'group_label' => 'Stooksaldo informatie',
			'item_id'     => 'stooksaldo-1',
			'data'        => [
				[
					'name'  => 'Saldo',
					'value' => number_format_i18n( $saldo->bedrag, 2 ),
				],
			],
		];
		return $items;
	}

	/**
	 * Export functie privacy gevoelige data.
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (stooksaldo).
	 */
	private function export_reservering( int $gebruiker_id ) : array {
		$items = [];
		$ovens = new Ovens();
		foreach ( $ovens as $oven ) {
			$stoken = new Stoken( $oven, 0, time() );
			foreach ( $stoken as $stook ) {
				foreach ( $stook->stookdelen as $stookdeel ) {
					if ( $stookdeel->medestoker === $gebruiker_id ) {
						$items[] = [
							'group_id'    => 'stook',
							'group_label' => 'Stook informatie',
							'item_id'     => 'stook-' . $oven->id . date( 'Y-m-d', $stook->datum ),
							'data'        => [
								[
									'name'  => 'Datum',
									'value' => wp_date( 'd-m-Y', $stook->datum ),
								],
								[
									'name'  => 'Oven',
									'value' => $ovens[ $oven->id ]->naam,
								],
							],
						];

					}
				}
			}
		}
		return $items;
	}

	/**
	 * Export functie privacy gevoelige data.
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (stooksaldo).
	 */
	private function export_dagdelenkaart( int $gebruiker_id ) : array {
		$dagdelenkaart = new Dagdelenkaart( $gebruiker_id );
		$items         = [];
		$items[]       = [
			'group_id'    => 'dagdelenkaart',
			'group_label' => 'Dagdelenkaart informatie',
			'item_id'     => 'dagdelenkaart-' . $dagdelenkaart->code,
			'data'        => [
				[
					'name'  => 'Datum',
					'value' => wp_date( 'd-m-Y', $dagdelenkaart->start_datum ),
				],
			],
		];
		return $items;
	}

}
