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
	 */
	protected function prepare_gebruikers() : string {
		if ( ! current_user_can( BESTUUR ) ) {
			return '';
		}
		$this->data['stokers'] = [];
		foreach ( new Stokers() as $stoker ) {
			$this->data['stokers'][] = [
				'naam'  => $stoker->display_name,
				'saldo' => number_format_i18n( $stoker->saldo->bedrag, 2 ),
				'id'    => $stoker->ID,
			];
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
		$ovens         = new Ovens();
		foreach ( $ovens as $oven ) {
			$stoken = new Stoken( $oven->id, 0, time() );
			foreach ( $stoken as $stook ) {
				if ( ! $stook->is_gereserveerd() ) {
					continue;
				}
				foreach ( $stook->stookdelen as $stookdeel ) {
					if ( $stookdeel->medestoker === $gebruiker->ID ) {
						$stoker          = get_userdata( $stook->hoofdstoker );
						$data['items'][] = [
							'datum'     => $stook->datum,
							'oven'      => $oven->naam,
							'stoker'    => false === $stoker ? 'onbekend' : $stoker->display_name,
							'stook'     => $stook->soort,
							'temp'      => $stook->temperatuur > 0 ? $stook->temperatuur : '',
							'prog'      => $stook->programma > 0 ? $stook->programma : '',
							'perc'      => $stookdeel->percentage,
							'bedrag'    => number_format_i18n(
								- $stookdeel->prijs ?? $oven->stookkosten( $gebruiker->ID, $stookdeel->percentage, $stook->temperatuur ),
								2
							),
							'voorlopig' => ! $stook->verwerkt,
						];
					}
				}
			}
		}
		foreach ( $saldo->storting as $storting ) {
			$data['items'][] = [
				'datum'     => strtotime( $storting['datum'] ),
				'bedrag'    => number_format_i18n( $storting['prijs'], 2 ),
				'status'    => $storting['status'] ?? '',
				'voorlopig' => ! isset( $storting['status'] ),
			];
		}
		return $data;
	}
}
