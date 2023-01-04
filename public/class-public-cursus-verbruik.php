<?php
/**
 * Shortcode cursus verbruik.
 *
 * @link       https://www.kleistad.nl
 * @since      7.4.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad cursus materialen verbruik class.
 */
class Public_Cursus_Verbruik extends ShortcodeForm {

	/**
	 * Prepareer 'cursus_overzicht' cursisten form
	 *
	 * @return string
	 */
	protected function prepare_cursisten() : string {
		$this->data['cursus']    = new Cursus( $this->data['id'] );
		$this->data['cursisten'] = $this->cursistenlijst( $this->data['cursus'] );
		return $this->content();
	}

	/**
	 * Prepareer 'cursus_overzicht' form
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		$this->data['cursussen'] = new Cursussen( strtotime( '-3 month 0:00' ) );
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'cursus_overzicht' form
	 *
	 * @since   5.4.0
	 *
	 * @return array
	 */
	public function process() : array {
		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'cursus_id'  => FILTER_SANITIZE_NUMBER_INT,
				'cursist_id' => [
					'filter' => FILTER_SANITIZE_NUMBER_INT,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'verbruik'   => [
					'filter' => FILTER_SANITIZE_NUMBER_INT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION | FILTER_REQUIRE_ARRAY,
				],
			]
		);
		return $this->save();
	}

	/**
	 * Verwerk het verbruik.
	 *
	 * @return array
	 */
	protected function verbruik() : array {
		$cursus = new Cursus( $this->data['input']['cursus_id'] );
		foreach ( $this->data['input']['cursist_id'] as $index => $cursist_id ) {
			$saldo = new Saldo( $cursist_id );
			$saldo->actie->verbruik( intval( $this->data['input']['verbruik'][ $index ] ), $cursus->naam );
		}
		return [
			'status'  => $this->status( 'Het verbruik is geregistreerd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Overzicht cursisten op cursus
	 *
	 * @param Cursus $cursus     De cursus.
	 *
	 * @return array De cursisten.
	 */
	private function cursistenlijst( Cursus $cursus ) : array {
		$cursisten = [];
		foreach ( new Inschrijvingen( $cursus->id, true ) as $inschrijving ) {
			if ( ! $inschrijving->ingedeeld ) {
				continue;
			}
			$cursist = get_userdata( $inschrijving->klant_id );
			$saldo   = new Saldo( $inschrijving->klant_id );
			$saldo->mutaties->filter_by_code( 'verbruik' )->sort_by_date( false );
			$cursisten[] = [
				'id'    => $inschrijving->klant_id,
				'naam'  => $cursist->display_name . $inschrijving->toon_aantal(),
				'saldo' => $saldo,
			];
			usort(
				$cursisten,
				function( $links, $rechts ) {
					return $links['naam'] <=> $rechts['naam'];
				}
			);
		}
		return $cursisten;
	}

}
