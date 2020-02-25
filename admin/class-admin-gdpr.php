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
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Export de inschrijving
	 *
	 * @since 4.3.0
	 *
	 * @param int $gebruiker_id Id van de gebruiker.
	 * @return array De persoonlijke data (cursus info).
	 */
	private static function export_inschrijving( $gebruiker_id ) {
		$inschrijvingen = \Kleistad\Inschrijving::all();
		$items          = [];
		if ( isset( $inschrijvingen[ $gebruiker_id ] ) ) {
			foreach ( $inschrijvingen[ $gebruiker_id ] as $cursus_id => $inschrijving ) {
				$items[] = [
					'group_id'    => 'cursusinfo',
					'group_label' => 'Cursussen informatie',
					'item_id'     => 'cursus-' . $cursus_id,
					'data'        => [
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
	private static function export_abonnement( $gebruiker_id ) {
		$abonnement = new \Kleistad\Abonnement( $gebruiker_id );
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
	private static function export_saldo( $gebruiker_id ) {
		$saldo   = new \Kleistad\Saldo( $gebruiker_id );
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
	 * @global object $wpdb wp database
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (stooksaldo).
	 */
	private static function export_reservering( $gebruiker_id ) {
		$reserveringen = \Kleistad\Reservering::all();
		$ovens         = \Kleistad\Oven::all();
		$items         = [];
		foreach ( $reserveringen as $reservering ) {
			$key = array_search( $gebruiker_id, array_column( $reservering->verdeling, 'id' ), true );
			if ( false !== $key ) {
				$items[] = [
					'group_id'    => 'stook',
					'group_label' => 'Stook informatie',
					'item_id'     => 'stook-' . $reservering->id,
					'data'        => [
						[
							'name'  => 'Datum',
							'value' => strftime( $reservering->datum ),
						],
						[
							'name'  => 'Oven',
							'value' => $ovens[ $reservering->oven_id ]->naam,
						],
					],
				];
			}
		}
		return $items;
	}

	/**
	 * Export functie privacy gevoelige data.
	 *
	 * @global object $wpdb wp database
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (stooksaldo).
	 */
	private static function export_dagdelenkaart( $gebruiker_id ) {
		$dagdelenkaart = new \Kleistad\Dagdelenkaart( $gebruiker_id );
		$items         = [];
		$items[]       = [
			'group_id'    => 'dagdelenkaart',
			'group_label' => 'Dagdelenkaart informatie',
			'item_id'     => 'dagdelenkaart-' . $dagdelenkaart->id,
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
	public static function exporter( $email, $page = 1 ) {
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
				self::export_inschrijving( $gebruiker_id ),
				self::export_abonnement( $gebruiker_id ),
				self::export_saldo( $gebruiker_id ),
				self::export_reservering( $gebruiker_id ),
				self::export_dagdelenkaart( $gebruiker_id )
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
	 * Erase / verwijder persoonlijke data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Het email adres van de te verwijderen persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 */
	public static function eraser( $email, $page = 1 ) {
		$count        = 0;
		$gebruiker_id = email_exists( $email );
		$domein       = \Kleistad\Email::domein();
		if ( false !== $gebruiker_id ) {
			$stub = "- verwijderd$gebruiker_id -";
			wp_update_user(
				(object) [
					'user_nicename' => $stub,
					'role'          => '',
					'display_name'  => $stub,
					'user_email'    => "verwijderd$gebruiker_id@$domein",
					'nickname'      => $stub,
					'first_name'    => '',
					'last_name'     => $stub,
					'description'   => '',
					'user_pass'     => wp_generate_password( 12, true ),
				]
			);
			update_user_meta( $gebruiker_id, 'telnr', '******' );
			update_user_meta( $gebruiker_id, 'straat', '******' );
			update_user_meta( $gebruiker_id, 'huisnr', '******' );
			update_user_meta( $gebruiker_id, 'pcode', '******' );
			update_user_meta( $gebruiker_id, 'plaats', '******' );
			delete_user_meta( $gebruiker_id, \Kleistad\Saldo::META_KEY );
			$count = 6;
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
	 *
	 * @since 6.4.0
	 */
	public static function erase_old_privacy_data() {
		$erase_agv     = strtotime( '-5 years' ); // Persoonlijke gegevens worden 5 jaar bewaard.
		$erase_fiscaal = strtotime( '-7 years' ); // Order gegevens worden 7 jaar bewaard.
		self::erase_cursussen( $erase_agv );
		self::erase_dagdeelkaarten( $erase_agv );
		self::erase_abonnementen( $erase_agv );
		self::erase_workshops( $erase_agv );
		self::erase_gebruikers( $erase_agv );
		self::erase_orders( $erase_fiscaal );
	}

	/**
	 * Verwijder oude cursussen
	 *
	 * @param int $datum Het criterium.
	 */
	private static function erase_cursussen( $datum ) {
		foreach ( \Kleistad\Cursus::all() as $cursus_id => $cursus ) {
			if ( $datum > $cursus->eind_datum ) {
				foreach ( \Kleistad\Inschrijving::all() as $cursist_id => $cursist_inschrijvingen ) {
					if ( array_key_exists( $cursus_id, $cursist_inschrijvingen ) ) {
						$cursist_inschrijvingen[ $cursus_id ]->erase();
					}
				}
				$cursus->erase();
			}
		}
	}

	/**
	 * Verwijder oude dagdeelkaarten
	 *
	 * @param int $datum Het criterium.
	 */
	private static function erase_dagdeelkaarten( $datum ) {
		foreach ( \Kleistad\Dagdelenkaart::all() as $gebruiker_id => $dagdeelkaart ) {
			if ( $datum > strtotime( '+3 month', $dagdeelkaart->start_datum ) ) {
				$dagdeelkaart->erase();
			}
		}
	}

	/**
	 * Verwijder oude abonnementen
	 *
	 * @param int $datum Het criterium.
	 */
	private static function erase_abonnementen( $datum ) {
		foreach ( \Kleistad\Abonnement::all() as $abonnee_id => $abonnement ) {
			if ( $datum > $abonnement->eind_datum ) {
				$abonnement->erase();
				$saldo = new \Kleistad\Saldo( $abonnee_id );
				$saldo->erase();
			}
		}
	}

	/**
	 * Verwijder oude workshops
	 *
	 * @param int $datum Het criterium.
	 */
	private static function erase_workshops( $datum ) {
		foreach ( \Kleistad\Workshop::all() as $workshop ) {
			if ( $datum > $workshop->datum ) {
				$workshop->erase();
			}
		}
	}

	/**
	 * Verwijder voormalige gebruikers
	 * Als de gebruiker lang geleden is aangemaakt en er staat niets meer open, dan kan deze weg.
	 * Maar als er nog rollen toegekend zijn niet. Een bestuurslid hoeft voor de rest niets te hebben maar heeft wel de rol.
	 *
	 * @param int $datum Het criterium.
	 */
	private static function erase_gebruikers( $datum ) {
		foreach ( get_users() as $gebruiker ) {
			if (
				$datum > strtotime( $gebruiker->user_registered ) &&
				empty( get_user_meta( $gebruiker->ID, \Kleistad\Inschrijving::META_KEY ) ) &&
				empty( get_user_meta( $gebruiker->ID, \Kleistad\Dagdeelkaart::META_KEY ) ) &&
				empty( get_user_meta( $gebruiker->ID, \Kleistad\Abonnement::META_KEY ) ) &&
				empty( $gebruiker->roles )
				) {
					wp_delete_user( $gebruiker->ID, 1 );
			}
		}
	}

	/**
	 * Verwijder oude orders
	 *
	 * @param int $datum Het criterium.
	 */
	private static function erase_orders( $datum ) {
		foreach ( \Kleistad\Order::all() as $order ) {
			if ( $datum > $order->datum ) {
				$order->erase();
			}
		};
	}

}
