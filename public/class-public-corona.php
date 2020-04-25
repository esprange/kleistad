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

/**
 * De kleistad cursus inschrijving class.
 */
class Public_Corona extends ShortcodeForm {

	/**
	 * Haal de beschikbaarheid op
	 *
	 * @todo Beschikbaarheid ophalen uit optie kleistad_corona.
	 *
	 * @param  int $datum De datum.
	 * @return array De beschikbaarheid.
	 */
	private function beschikbaarheid( $datum ) {
		$beschikbaarheid = get_option( 'kleistad_corona_beschikbaarheid', [] );
		if ( isset( $beschikbaarheid[ $datum ] ) ) {
			return $beschikbaarheid[ $datum ];
		}
		return [];
	}

	/**
	 * Bepaald de eerstmogelijke datum om te reserveren.
	 *
	 * @return array De datums.
	 */
	private function mogelijke_datums() {
		$beschikbaarheid = get_option( 'kleistad_corona_beschikbaarheid', [] );
		$datum_lijst     = [];
		foreach ( $beschikbaarheid as $mogelijke_datum => $niet_gebruikt ) {
			if ( $mogelijke_datum >= strtotime( 'today 0:00' ) ) {
				$datum_lijst[] = $mogelijke_datum;
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
			$aanwezig      = false;
			$reserveringen = get_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), [] );
			foreach ( $reserveringen as $index => $reservering ) {
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
	 * @return bool|\WP_Error
	 *
	 * @since   6.3.4
	 */
	protected function prepare( &$data ) {
		$atts = shortcode_atts(
			[ 'actie' => '' ],
			$this->atts,
			'kleistad_corona'
		);
		if ( ! empty( $atts['actie'] ) && current_user_can( 'bestuur' ) ) {
			$data['actie'] = $atts['actie'];
			if ( 'gebruikers' === $data['actie'] ) {
				$data['gebruikers'] = get_users(
					[
						'role__in' => [ 'bestuur', 'leden', 'cursist-1' ],
						'fields'   => [
							'ID',
							'display_name',
						],
					]
				);
				$data['id']         = intval( filter_input( INPUT_GET, 'gebruiker' ) );
				$data['gebruik']    = $this->gebruik( $data['id'] );
				return true;
			} elseif ( 'overzicht' === $data['actie'] ) {
				$data['overzicht'] = $this->bezetting();
				return true;
			}
		}
		$datums = $this->mogelijke_datums();
		if ( empty( $datums ) ) {
			return new \WP_Error( 'werkplek', 'Er is geen enkele beschikbaarheid' );
		}
		wp_add_inline_style( 'kleistad', '.kleistad_shortcode td, th { padding:0;text-align:center; }' );
		$datum_str       = filter_input( INPUT_GET, 'datum' );
		$datum           = is_null( $datum_str ) ? $datums[0] : strtotime( $datum_str );
		$current_user_id = get_current_user_id();
		if ( $current_user_id ) {
			$data = [
				'actie'           => 'reserveren',
				'input'           => [
					'naam'  => get_user_by( 'id', $current_user_id )->first_name,
					'id'    => $current_user_id,
					'datum' => $datum,
				],
				'beschikbaarheid' => $this->beschikbaarheid( $datum ),
				'reserveringen'   => $this->reserveringen( $datum ),
				'datums'          => $datums,
			];
		} else {
			return new \WP_Error( 'werkplek', 'Je moet ingelogd zijn om deze functie te gebruiken' );
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'corona' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   6.3.4
	 */
	protected function validate( &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'datum' => FILTER_SANITIZE_STRING,
				'res'   => [
					'filter' => FILTER_DEFAULT,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'id'    => FILTER_SANITIZE_NUMBER_INT,
			]
		);
		return true;
	}

	/**
	 *
	 * Bewaar 'corona' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_Error|array
	 *
	 * @since   6.3.4
	 */
	protected function save( $data ) {
		$datum           = strtotime( $data['input']['datum'] );
		$beschikbaarheid = $this->beschikbaarheid( $datum );
		$reserveringen   = get_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), [] );
		$aanpassingen    = $data['input']['res'] ?: [];
		$id              = intval( $data['input']['id'] );
		foreach ( $aanpassingen as $index => $aanpassing ) {
			foreach ( $aanpassing as $werk => $check ) {
				if ( ! in_array( $id, $reserveringen[ $index ][ $werk ] ?? [], true ) ) {
					if ( count( $reserveringen[ $index ][ $werk ] ?? [] ) < $beschikbaarheid[ $index ][ $werk ] ) {
						$reserveringen[ $index ][ $werk ][] = $id;
					} else {
						return [
							'status' => $this->status( new \WP_Error( 'werkplek', 'De reservering kon niet worden opgeslagen, probeer het opnieuw' ) ),
						];
					}
				}
			}
		}
		foreach ( $reserveringen as $index => $reservering ) {
			foreach ( $reservering as $werk => $ids ) {
				if ( ! isset( $aanpassingen[ $index ][ $werk ] ) && in_array( $id, $ids, true ) ) {
					$reserveringen[ $index ][ $werk ] = array_diff( $reserveringen[ $index ][ $werk ], [ $id ] );
				}
			}
		}
		update_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), $reserveringen );
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
		$titels       = [
			'H' => 'Handvormen',
			'D' => 'Draaien',
			'B' => 'Bovenruimte',
		];
		$gebruiker_id = filter_input( INPUT_GET, 'gebruiker', FILTER_SANITIZE_NUMBER_INT );
		$gebruik      = $this->gebruik( intval( $gebruiker_id ) );
		ksort( $gebruik );

		$gebruik_fields = [
			'Datum',
			'Tijd',
			'Gebruik',
		];
		fputcsv( $this->file_handle, $gebruik_fields, ';', '"' );
		foreach ( $gebruik as $datum => $tijden ) {
			foreach ( $tijden as $tijd => $werk ) {
				fputcsv(
					$this->file_handle,
					[
						date( 'd-m-Y', $datum ),
						$tijd,
						$titels[ $werk ],
					],
					';',
					'"'
				);
			}
		}
	}

}
