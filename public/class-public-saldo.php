<?php
/**
 * Shortcode saldo (aanvullen saldo door lid).
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;
use PHP_IBAN\IBAN;

/**
 * De kleistad saldo class.
 */
class Public_Saldo extends Public_Bestelling {

	/**
	 * Prepareer 'saldo' form
	 *
	 * @return string
	 */
	protected function prepare() : string {
		$this->data['saldo'] = new Saldo( get_current_user_id() );
		if ( $this->data['saldo']->terugboeking ) {
			$this->data['terugstorttekst'] = 'een terugstorting is al aangevraagd';
			$this->data['terugstortbaar']  = false;
		} elseif ( $this->data['saldo']->bedrag > opties()['administratiekosten'] ) {
			$this->data['terugstorttekst'] = sprintf( 'ik wil mijn openstaand saldo terug laten storten. Administratiekosten (€ %s ) worden in rekening gebracht', number_format_i18n( opties()['administratiekosten'], 2 ) );
			$this->data['terugstortbaar']  = true;
		} else {
			$this->data['terugstorttekst'] = sprintf( 'het terugstorten van een openstaand saldo is vanwege administratiekosten alleen mogelijk als dit meer dan € %s bedraagt', number_format_i18n( opties()['administratiekosten'], 2 ) );
			$this->data['terugstortbaar']  = false;
		}
		if ( ! isset( $this->data['input'] ) ) {
			$this->data['input'] = [
				'iban'  => '',
				'rnaam' => '',
			];
		}
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'saldo' form
	 *
	 * @since   4.0.87
	 *
	 * @return array
	 */
	public function process() : array {

		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
				'bedrag'       => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'betaal'       => FILTER_SANITIZE_STRING,
				'iban'         => FILTER_SANITIZE_STRING,
				'rnaam'        => FILTER_SANITIZE_STRING,
			]
		);
		if ( ! empty( $this->data['input']['iban'] ) ) {
			$iban = new IBAN( $this->data['input']['iban'] );
			if ( ! $iban->Verify() ) {
				return $this->melding( new WP_Error( 'onjuist_iban', 'het IBAN bankrekeningnummer is geen geldig IBAN' ) );
			}
			$this->data['input']['iban'] = $iban->HumanFormat();
		}
		if ( opties()['minsaldostorting'] > floatval( $this->data['input']['bedrag'] ) || opties()['maxsaldostorting'] < floatval( $this->data['input']['bedrag'] ) ) {
			return $this->melding(
				new WP_Error(
					'onjuist_bedrag',
					sprintf(
						'Het bedrag moet tussen %d en %d euro liggen',
						number_format_i18n( opties()['minsaldostorting'], 2 ),
						number_format_i18n( opties()['maxsaldostorting'], 2 )
					)
				)
			);
		}
		return $this->save();
	}

	/**
	 * Bewaar 'saldo' form gegevens
	 *
	 * @return array
	 *
	 * @since   4.0.87
	 */
	protected function save() : array {
		if ( 'terugboeking' === $this->data['input']['betaal'] ) {
			return $this->verlagen();
		}
		return $this->verhogen();
	}

	/**
	 * Verhoog het saldo
	 *
	 * @return array
	 */
	private function verhogen() : array {
		$saldo  = new Saldo( intval( $this->data['input']['gebruiker_id'] ) );
		$result = $saldo->actie->nieuw( floatval( $this->data['input']['bedrag'] ), $this->data['input']['betaal'] );
		if ( false === $result ) {
			return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		if ( is_string( $result ) ) {
			return [ 'redirect_uri' => $result ];
		}
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Er is een email verzonden met nadere informatie over de betaling' ),
		];
	}

	/**
	 * Stort het openstaand bedrag terug
	 *
	 * @return array
	 */
	private function verlagen() : array {
		$saldo = new Saldo( intval( $this->data['input']['gebruiker_id'] ) );
		if ( opties()['administratiekosten'] > $saldo->bedrag ) {
			return [ 'status' => $this->status( new WP_Error( 'saldo', 'Het openstaand bedrag is kleiner dan ' . opties()['administratiekosten'] . ' euro, vanwege de administratiekosten is terugstorting niet mogelijk' ) ) ];
		}
		$result = $saldo->actie->doe_restitutie( $this->data['input']['iban'], $this->data['input']['rnaam'] );
		if ( false === $result ) {
			return [ 'status' => $this->status( new WP_Error( 'saldo', 'Terugstorting was niet mogelijk, neem eventueel contact op met Kleistad' ) ) ];
		}
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Er is een email verzonden met nadere informatie over het terug te storten bedrag' ),
		];
	}
}
