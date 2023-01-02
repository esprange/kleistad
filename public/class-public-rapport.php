<?php
/**
 * Shortcode rapport (persoonlijke stookgegevens).
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_User;

/**
 * De kleistad rapport class.
 */
class Public_Rapport extends Shortcode {

	/**
	 * Prepareer het overzicht rapport van de gebruiker
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		$this->data = array_merge( $this->data, $this->rapport( wp_get_current_user() ) );
		return $this->content();
	}

	/**
	 * Prepareer het overzicht over alle gebruikers
	 *
	 * @return string
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function prepare_gebruikers() : string {
		if ( ! current_user_can( BESTUUR ) ) {
			return '';
		}
		$this->data['stokers']        = [];
		$this->data['negatief_saldo'] = 0.0;
		$this->data['positief_saldo'] = 0.0;
		foreach ( new Stokers() as $stoker ) {
			$this->data['stokers'][] = [
				'naam'  => $stoker->display_name,
				'saldo' => $stoker->saldo->bedrag,
				'id'    => $stoker->ID,
			];
			if ( 0 > $stoker->saldo->bedrag ) {
				$this->data['negatief_saldo'] += $stoker->saldo->bedrag;
			} else {
				$this->data['positief_saldo'] += $stoker->saldo->bedrag;
			}
		}
		return $this->content();
	}

	/**
	 * Maak het rapport op voor de specifieke gebruiker
	 *
	 * @return string
	 */
	protected function prepare_rapport_gebruiker() : string {
		$gebruiker = get_user_by( 'ID', $this->data['id'] );
		if ( ! current_user_can( BESTUUR ) || false === $gebruiker ) {
			return '';
		}
		$this->data = array_merge( $this->data, $this->rapport( $gebruiker ) );
		return $this->content();
	}

	/**
	 * Haal de rapport data op.
	 *
	 * @param WP_User $gebruiker De gebruiker.
	 */
	private function rapport( WP_User $gebruiker ) : array {
		$data          = [];
		$saldo         = new Saldo( $gebruiker->ID );
		$data['naam']  = $gebruiker->display_name;
		$data['saldo'] = number_format_i18n( $saldo->bedrag, 2 );
		$data['items'] = [];
		foreach ( new Ovens() as $oven ) {
			$stoken = new Stoken( $oven, 0 );
			foreach ( $stoken as $stook ) {
				if ( ! $stook->is_gereserveerd() ) {
					continue;
				}
				foreach ( $stook->stookdelen as $stookdeel ) {
					if ( $stookdeel->medestoker === $gebruiker->ID ) {
						$stoker          = get_userdata( $stook->hoofdstoker_id );
						$data['items'][] = [
							'datum'     => $stook->datum,
							'oven'      => $oven->naam,
							'stoker'    => false === $stoker ? 'onbekend' : $stoker->display_name,
							'stook'     => $stook->soort,
							'temp'      => $stook->temperatuur > 0 ? $stook->temperatuur : '',
							'prog'      => $stook->programma > 0 ? $stook->programma : '',
							'perc'      => $stookdeel->percentage,
							'bedrag'    => number_format_i18n(
								- $stookdeel->prijs ?? $oven->get_stookkosten( $gebruiker->ID, $stookdeel->percentage, $stook->temperatuur ),
								2
							),
							'voorlopig' => ! $stook->verwerkt,
						];
					}
				}
			}
		}
		foreach ( $saldo->mutaties as $mutatie ) {
			if ( $mutatie->code ) {
				$data['items'][] = [
					'datum'     => $mutatie->datum,
					'bedrag'    => number_format_i18n( $mutatie->bedrag, 2 ),
					'status'    => $mutatie->status,
					'gewicht'   => 0.0 < $mutatie->gewicht ? number_format_i18n( $mutatie->gewicht, 2 ) : '',
					'voorlopig' => ! empty( $mutatie->status ),
				];
			}
		}
		return $data;
	}

	/**
	 * Schrijf de gebruikers saldo naar het bestand.
	 */
	protected function saldi() {
		$fields = [
			'Naam',
			'Saldo',
		];
		fputcsv( $this->filehandle, $fields, ';' );
		foreach ( new Stokers() as $stoker ) {
			fputcsv(
				$this->filehandle,
				[
					$stoker->display_name,
					number_format_i18n( $stoker->saldo->bedrag, 2 ),
				],
				';'
			);
		}
	}

}
