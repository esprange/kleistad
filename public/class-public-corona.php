<?php
/**
 * Shortcode contact form.
 *
 * @link       https://www.kleistad.nl
 * @since      6.3.4
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;

/**
 * De kleistad cursus inschrijving class.
 */
class Public_Corona extends ShortcodeForm {

	/**
	 * Haal de beschikbaarheid op
	 *
	 * @param  int $datum De datum.
	 * @return array De beschikbaarheid.
	 */
	private function beschikbaarheid( $datum ) {
		$adhoc            = get_option( 'kleistad_adhoc_' . date( 'yyyymmdd', $datum ), [] );
		$resultaat        = [];
		$meesters         = get_option( 'kleistad_meesters', [] );
		$beschikbaarheden = get_option( 'kleistad_corona_beschikbaarheid', [] );
		if ( isset( $beschikbaarheden[ $datum ] ) ) {
			foreach ( $beschikbaarheden[ $datum ]  as $blokdeel => $beschikbaarheid ) {
				if ( isset( $adhoc[ $blokdeel ] ) ) {
					$resultaat[] = array_merge(
						$beschikbaarheid,
						[
							'M' => [
								'id' => $adhoc[ $blokdeel ],
								's'  => 0,
							],
						]
					);
				} else {
					$dagnr       = intval( date( 'w', $datum ) );
					$resultaat[] = array_merge(
						$beschikbaarheid,
						[
							'M' => [
								'id' => $meesters[ $dagnr ][ $blokdeel ] ?? 0,
								's'  => intval( isset( $meesters[ $dagnr ][ $blokdeel ] ) ),
							],
						]
					);
				}
			}
		}
		return $resultaat;
	}

	/**
	 * Bewaar een standaard meester
	 *
	 * @param int $meester_id Het gebruikersid van de meester.
	 * @param int $datum      De unix datum.
	 * @param int $blokdeel   Het blokdeel, beginnend vanaf 0.
	 */
	private function standaard_meester( $meester_id, $datum, $blokdeel ) {
		$dagnr                           = intval( date( 'w', $datum ) );
		$meesters                        = get_option( 'kleistad_meesters', [] );
		$meesters[ $dagnr ][ $blokdeel ] = $meester_id;
		update_option( 'kleistad_meesters', $meesters );
	}

	/**
	 * Bewaar een afwijkende meester
	 *
	 * @param int $meester_id Het gebruikersid van de meester.
	 * @param int $datum      De unix datum.
	 * @param int $blokdeel   Het blokdeel, beginnend vanaf 0.
	 */
	private function adhoc_meester( $meester_id, $datum, $blokdeel ) {
		$optie              = 'kleistad_adhoc_' . date( 'yyyymmdd', $datum );
		$adhoc              = get_option( $optie, [] );
		$adhoc[ $blokdeel ] = $meester_id;
		update_option( $optie, $adhoc );
	}

	/**
	 * Bepaald de eerstmogelijke datum om te reserveren.
	 *
	 * @return array De datums.
	 */
	private function mogelijke_datums() {
		$beschikbaarheid = get_option( 'kleistad_corona_beschikbaarheid', [] );
		$datum_lijst     = [];
		if ( is_array( $beschikbaarheid ) ) {
			foreach ( array_keys( $beschikbaarheid ) as $mogelijke_datum ) {
				if ( $mogelijke_datum >= strtotime( 'today' ) ) {
					$datum_lijst[] = $mogelijke_datum;
				}
			}
		}
		return $datum_lijst;
	}

	/**
	 * Haal alle reserveringen op voor een bezetting overzicht
	 *
	 * @return array
	 */
	private function bezetting() {
		$bezetting        = [];
		$beschikbaarheden = get_option( 'kleistad_corona_beschikbaarheid', [] );
		foreach ( $beschikbaarheden as $datum => $beschikbaarheid ) {
			$reserveringen = get_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), [] );
			$index         = 0;
			foreach ( $beschikbaarheid as $tijdzone ) {
				$bezetting[ $datum ][ $tijdzone['T'] ] = [
					'H' => isset( $reserveringen[ $index ]['H'] ) ? count( $reserveringen[ $index ]['H'] ) : 0,
					'D' => isset( $reserveringen[ $index ]['D'] ) ? count( $reserveringen[ $index ]['D'] ) : 0,
					'B' => isset( $reserveringen[ $index ]['B'] ) ? count( $reserveringen[ $index ]['B'] ) : 0,
				];
				$index++;
			}
		}
		return $bezetting;
	}

	/**
	 * Toon het gebruik van het atelier obv de reserveringen door de gebruiker
	 *
	 * @param int $id De gebruiker.
	 * @return array
	 */
	private function gebruik( $id ) {
		$beschikbaarheden = get_option( 'kleistad_corona_beschikbaarheid', [] );
		$gebruik          = [];
		foreach ( $beschikbaarheden as $datum => $beschikbaarheid ) {
			$reserveringen = get_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), [] );
			foreach ( $reserveringen as $index => $reservering ) {
				$aanwezig = false;
				foreach ( $reservering as $werk => $ids ) {
					if ( in_array( $id, $ids, true ) ) {
						$aanwezig = $werk;
						break;
					}
				}
				if ( $aanwezig ) {
					$gebruik[ $datum ][ $beschikbaarheid[ $index ]['T'] ] = $aanwezig;
				}
			}
		}
		return $gebruik;
	}

	/**
	 * Haal de reeds aanwezige reserveringen op
	 *
	 * @param  int $datum De datum.
	 * @return array De reserveringen.
	 */
	private function reserveringen( $datum ) {
		$reserveringen = [];
		$lijst         = get_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), [] );
		foreach ( $lijst as $index => $reservering ) {
			foreach ( $reservering as $werk => $ids ) {
				$current_id = get_current_user_id();
				$namen      = [];
				foreach ( $ids as $id ) {
					if ( $id !== $current_id ) {
						$namen[] = substr( get_user_by( 'id', $id )->display_name, 0, 15 );
					}
				}
				$reserveringen[ $index ][ $werk ] = [
					'namen'    => $namen,
					'aanwezig' => in_array( $current_id, $ids, true ),
				];
			}
		}
		return $reserveringen;
	}

	/**
	 *
	 * Prepareer 'corona' form
	 *
	 * @param array $data data voor display.
	 * @return bool|WP_Error
	 *
	 * @since   6.3.4
	 */
	protected function prepare( &$data ) {
		$atts = shortcode_atts(
			[ 'actie' => '' ],
			$this->atts,
			'kleistad_corona'
		);

		if ( ! empty( $atts['actie'] ) && current_user_can( BESTUUR ) ) {
			$data['actie'] = $atts['actie'];
			if ( 'gebruikers' === $data['actie'] ) {
				$data['gebruikers'] = get_users(
					[ 'fields' => [ 'ID', 'display_name' ] ],
				);

				$data['id']      = intval( filter_input( INPUT_GET, 'gebruiker' ) );
				$data['gebruik'] = $this->gebruik( $data['id'] );
				return true;
			}
			if ( 'overzicht' === $data['actie'] ) {
				$data['overzicht'] = $this->bezetting();
				return true;
			}
		}
		$datums = $this->mogelijke_datums();
		if ( empty( $datums ) ) {
			return new WP_Error( 'werkplek', 'Er is geen enkele beschikbaarheid' );
		}
		wp_add_inline_style( 'kleistad', '.kleistad_shortcode td, th { padding:0;text-align:center; }' );
		$datum_str       = filter_input( INPUT_GET, 'datum' );
		$datum           = is_null( $datum_str ) ? $datums[0] : strtotime( $datum_str );
		$current_user_id = get_current_user_id();
		$data            = [
			'actie'           => 'reserveren',
			'input'           => [
				'naam'  => get_user_by( 'id', $current_user_id )->first_name,
				'id'    => $current_user_id,
				'datum' => $datum,
			],
			'beschikbaarheid' => $this->beschikbaarheid( $datum ),
			'reserveringen'   => $this->reserveringen( $datum ),
			'datums'          => $datums,
			'gebruikers'      => get_users(
				[ 'fields' => [ 'ID', 'display_name' ] ],
			),
		];
		if ( current_user_can( BESTUUR ) || current_user_can( DOCENT ) ) {
			$cursisten_zonder_abonnement = get_transient( 'kleistad_za' );
			if ( ! is_array( $cursisten_zonder_abonnement ) ) {
				$cursisten_zonder_abonnement = [];
				foreach ( new Cursisten() as $cursist ) {
					if ( user_can( $cursist->ID, LID ) || user_can( $cursist->ID, BESTUUR ) || user_can( $cursist->ID, DOCENT ) ) {
						continue;
					}
					if ( $cursist->is_actief() ) {
						$cursisten_zonder_abonnement[] = [
							'id'   => $cursist->ID,
							'naam' => $cursist->display_name,
						];
					}
				}
				set_transient( 'kleistad_za', $cursisten_zonder_abonnement, 900 );
			}
			$data['cursisten_za'] = $cursisten_zonder_abonnement;
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'corona' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return WP_Error|bool
	 *
	 * @since   6.3.4
	 */
	protected function validate( &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'datum'     => FILTER_SANITIZE_STRING,
				'res'       => [
					'filter' => FILTER_DEFAULT,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'id'        => FILTER_SANITIZE_NUMBER_INT,
				'meester'   => [
					'filter' => FILTER_DEFAULT,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'standaard' => [
					'filter' => FILTER_DEFAULT,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
			]
		);
		return true;
	}

	/**
	 *
	 * Bewaar 'corona' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return WP_Error|array
	 *
	 * @since   6.3.4
	 */
	protected function save( $data ) {
		$datum           = strtotime( $data['input']['datum'] );
		$beschikbaarheid = $this->beschikbaarheid( $datum );
		$reserveringen   = get_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), [] );
		$aanpassingen    = $data['input']['res'] ?: [];
		$gebruiker_id              = intval( $data['input']['id'] );
		foreach ( $aanpassingen as $index => $aanpassing ) {
			foreach ( array_keys( $aanpassing ) as $werk ) {
				if ( ! in_array( $gebruiker_id, $reserveringen[ $index ][ $werk ] ?? [], true ) ) {
					if ( count( $reserveringen[ $index ][ $werk ] ?? [] ) >= $beschikbaarheid[ $index ][ $werk ] ) {
						return [
							'status' => $this->status( new WP_Error( 'werkplek', 'De reservering kon niet worden opgeslagen, probeer het opnieuw' ) ),
						];
					}
					$reserveringen[ $index ][ $werk ][] = $gebruiker_id;
				}
			}
		}
		foreach ( $reserveringen as $index => $reservering ) {
			foreach ( $reservering as $werk => $ids ) {
				if ( ! isset( $aanpassingen[ $index ][ $werk ] ) && in_array( $gebruiker_id, $ids, true ) ) {
					$reserveringen[ $index ][ $werk ] = array_diff( $reserveringen[ $index ][ $werk ], [ $gebruiker_id ] );
				}
			}
		}
		update_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), $reserveringen );
		if ( current_user_can( BESTUUR ) && is_array( $data['input']['meester'] ) ) {
			foreach ( $data['input']['meester'] as $blokdeel => $gebruiker_id ) {
				if ( $data['input']['standaard'][ $blokdeel ] ) {
					$this->standaard_meester( $gebruiker_id, $datum, $blokdeel );
				} else {
					$this->adhoc_meester( $gebruiker_id, $datum, $blokdeel );
				}
			}
		}
		return [
			'status' => $this->status( 'De reservering is aangepast' ),
		];
	}

	/**
	 * Schrijf overzicht informatie naar het bestand.
	 */
	protected function overzicht() {
		$overzicht = $this->bezetting();
		ksort( $overzicht );
		$overzicht_fields = [
			'Datum',
			'Tijd',
			'Handvormen',
			'Draaien',
			'Bovenruimte',
		];
		fputcsv( $this->file_handle, $overzicht_fields, ';', '"' );
		foreach ( $overzicht as $datum => $tijden ) {
			foreach ( $tijden as $tijd => $gebruik ) {
				fputcsv(
					$this->file_handle,
					[
						date( 'd-m-Y', $datum ),
						$tijd,
						$gebruik['H'],
						$gebruik['D'],
						$gebruik['B'],
					],
					';',
					'"'
				);
			}
		}
	}

	/**
	 * Schrijf overzicht informatie naar het bestand.
	 */
	protected function gebruiker() {
		$gebruiker_id = intval( filter_input( INPUT_GET, 'gebruiker', FILTER_SANITIZE_NUMBER_INT ) );
		$gebruik      = $this->gebruik( $gebruiker_id );
		ksort( $gebruik );

		fputcsv( $this->file_handle, [ get_user_by( 'id', $gebruiker_id )->display_name ], ';', '"' );
		$gebruik_fields = [
			'Datum',
			'Tijd',
			'Aanwezig',
		];
		fputcsv( $this->file_handle, $gebruik_fields, ';', '"' );
		foreach ( $gebruik as $datum => $tijden ) {
			$beschikbaarheid = $this->beschikbaarheid( $datum );
			$reserveringen   = get_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), [] );
			foreach ( array_keys( $tijden ) as $tijd ) {
				$aanwezig_ids = [];
				foreach ( $beschikbaarheid as $blokdeel => $blok ) {
					if ( $blok['T'] === $tijd ) {
						foreach ( $reserveringen[ $blokdeel ] as $werk ) {
							$aanwezig_ids = array_merge( $aanwezig_ids, $werk );
						}
						if ( $blok['M']['id'] ) {
							$aanwezig_ids[] = $blok['M']['id'];
						}
					}
				}
				$aanwezig = [];
				foreach ( array_unique( $aanwezig_ids ) as $id ) {
					$aanwezig[] = get_user_by( 'id', $id )->display_name;
				}
				fputcsv(
					$this->file_handle,
					[
						date( 'd-m-Y', $datum ),
						$tijd,
						implode( ',', $aanwezig ),
					],
					';',
					'"'
				);
			}
		}
	}

}
