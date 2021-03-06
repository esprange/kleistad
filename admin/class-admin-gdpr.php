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
 * GDPR class
 */
class Admin_GDPR {

	/**
	 * Export de inschrijving
	 *
	 * @since 4.3.0
	 *
	 * @param int $gebruiker_id Id van de gebruiker.
	 * @return array De persoonlijke data (cursus info).
	 */
	private function export_inschrijving( $gebruiker_id ) {
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
						'value' => strftime( '%d-%m-%y', $inschrijving->datum ),
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
	private function export_abonnement( $gebruiker_id ) {
		$abonnement = new Abonnement( $gebruiker_id );
		$items      = [];
		$items[]    = [
			'group_id'    => 'abonnementinfo',
			'group_label' => 'Abonnement informatie',
			'item_id'     => 'abonnement-1',
			'data'        => [
				[
					'name'  => 'Aanmeld datum',
					'value' => strftime( '%d-%m-%y', $abonnement->datum ),
				],
				[
					'name'  => 'Start datum',
					'value' => $abonnement->start_datum > 0 ? strftime( '%d-%m-%y', $abonnement->start_datum ) : '',
				],
				[
					'name'  => 'Eind datum',
					'value' => $abonnement->eind_datum > 0 ? strftime( '%d-%m-%y', $abonnement->eind_datum ) : '',
				],
				[
					'name'  => 'Pauze datum',
					'value' => $abonnement->pauze_datum > 0 ? strftime( '%d-%m-%y', $abonnement->pauze_datum ) : '',
				],
				[
					'name'  => 'Herstart datum',
					'value' => $abonnement->herstart_datum > 0 ? strftime( '%d-%m-%y', $abonnement->herstart_datum ) : '',
				],
				[
					'name'  => 'Opmerking',
					'value' => $abonnement->opmerking,
				],
				[
					'name'  => 'Soort abonnement',
					'value' => $abonnement->soort,
				],
				[
					'name'  => 'Dag',
					'value' => $abonnement->dag,
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
	private function export_saldo( $gebruiker_id ) {
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
	private function export_reservering( $gebruiker_id ) {
		$items = [];
		$ovens = new Ovens();
		foreach ( $ovens as $oven ) {
			$stoken = new Stoken( $oven->id, 0, time() );
			foreach ( $stoken as $stook ) {
				foreach ( $stook->stookdelen as $stookdeel ) {
					if ( $stookdeel->medestoker === $gebruiker_id ) {
						$items[] = [
							'group_id'    => 'stook',
							'group_label' => 'Stook informatie',
							'item_id'     => 'stook-' . $oven->id . date( 'm-d-Y', $stook->datum ),
							'data'        => [
								[
									'name'  => 'Datum',
									'value' => strftime( $stook->datum ),
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
	private function export_dagdelenkaart( $gebruiker_id ) {
		$dagdelenkaart = new Dagdelenkaart( $gebruiker_id );
		$items         = [];
		$items[]       = [
			'group_id'    => 'dagdelenkaart',
			'group_label' => 'Dagdelenkaart informatie',
			'item_id'     => 'dagdelenkaart-' . $dagdelenkaart->code,
			'data'        => [
				[
					'name'  => 'Datum',
					'value' => strftime( $dagdelenkaart->start_datum ),
				],
			],
		];
		return $items;
	}

	/**
	 * Exporteer persoonlijke data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Het email adres van de te exporteren persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 */
	public function exporter( $email, $page = 1 ) {
		$export_items = [];
		$gebruiker_id = email_exists( $email );
		if ( false !== $gebruiker_id ) {
			$gebruiker    = get_userdata( $gebruiker_id );
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
	 * Erase / verwijder persoonlijke data. Om de consistentie van de database te waarborgen doen we in feite een anonimisering.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Het email adres van de te verwijderen persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 */
	public function eraser( $email, $page = 1 ) {
		$count        = 0;
		$gebruiker_id = email_exists( $email );
		if ( false !== $gebruiker_id ) {
			$gebruiker = new Gebruiker( $gebruiker_id );
			$count     = $gebruiker->anonimiseer() ?: 0;
		}
		return [
			'items_removed'  => $count,
			'items_retained' => false,
			'messages'       => [],
			'done'           => ( 0 < $count && 1 === $page ), // Controle op page is een dummy.
		];
	}

	/**
	 * Verwijder oude gegevens, ouder dan 5 jaar conform de privacy verklaring
	 * Uiteindelijk worden ook de gebruikers verwijderd. Dat gebeurt in de dagelijkse job.
	 *
	 * @since 6.4.0
	 */
	public function erase_old_privacy_data() {
		$erase_agv     = strtotime( '-5 years' ); // Persoonlijke gegevens worden 5 jaar bewaard.
		$erase_fiscaal = strtotime( '-7 years' ); // Order gegevens worden 7 jaar bewaard.
		$this->erase_cursussen( $erase_agv );
		$this->erase_dagdelenkaarten( $erase_agv );
		$this->erase_abonnementen( $erase_agv );
		$this->erase_workshops( $erase_agv );
		$this->erase_orders( $erase_fiscaal );
	}

	/**
	 * Verwijder oude cursussen
	 *
	 * @param int $datum Het criterium.
	 */
	private function erase_cursussen( $datum ) {
		foreach ( new Cursussen() as $cursus ) {
			if ( $cursus->eind_datum && $datum > $cursus->eind_datum ) {
				foreach ( new Inschrijvingen( $cursus->id ) as $inschrijving ) {
					$inschrijving->erase();
				}
				$cursus->erase();
			}
		}
	}

	/**
	 * Verwijder oude dagdelenkaarten
	 *
	 * @param int $datum Het criterium.
	 */
	private function erase_dagdelenkaarten( $datum ) {
		foreach ( new Dagdelenkaarten() as $dagdelenkaart ) {
			if ( $dagdelenkaart->eind_datum && $datum > $dagdelenkaart->eind_datum ) {
				$dagdelenkaart->erase();
			}
		}
	}

	/**
	 * Verwijder oude abonnementen
	 *
	 * @param int $datum Het criterium.
	 */
	private function erase_abonnementen( $datum ) {
		foreach ( new Abonnementen() as $abonnement ) {
			if ( $abonnement->eind_datum && $datum > $abonnement->eind_datum ) {
				$saldo = new Saldo( $abonnement->klant_id );
				$saldo->erase();
				$abonnement->erase();
			}
		}
	}

	/**
	 * Verwijder oude workshops
	 *
	 * @param int $datum Het criterium.
	 */
	private function erase_workshops( $datum ) {
		foreach ( new Workshops() as $workshop ) {
			if ( $datum > $workshop->datum ) {
				$workshop->erase();
			}
		}
	}

	/**
	 * Verwijder oude orders
	 *
	 * @param int $datum Het criterium.
	 */
	private function erase_orders( $datum ) {
		$orders = new Orders();
		foreach ( $orders as $order ) {
			if ( $datum > $order->datum ) {
				$order->erase();
			}
		};
	}

}
