<?php
/**
 * Definieer de workshop actie class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Exception;

/**
 * Kleistad WorkshopActie class.
 *
 * @since 6.14.7
 */
class WorkshopActie {

	/**
	 * Het workshop object
	 *
	 * @var Workshop $workshop De workshop.
	 */
	private Workshop $workshop;

	/**
	 * Constructor
	 *
	 * @param Workshop $workshop De workshop.
	 */
	public function __construct( Workshop $workshop ) {
		$this->workshop = $workshop;
	}

		/**
		 * Zeg de gemaakte afspraak voor de workshop af.
		 *
		 * @since 5.0.0
		 */
	public function afzeggen() : bool {
		if ( ! $this->workshop->vervallen ) {
			$this->workshop->vervallen = true;
			$this->workshop->save();
			try {
				$event = new Event( $this->workshop->event_id );
				$event->delete();
			} catch ( Exception $exceptie ) {
				unset( $exceptie ); // phpcs:ignore
			}
		}
		if ( $this->workshop->definitief ) {
			$this->workshop->verzend_email( '_afzegging' );
		}
		return true;
	}

	/**
	 * Geef aan dat de workshop betaald moet worden
	 */
	public function vraag_betaling() {
		$this->workshop->betaling_email = true;
		$this->workshop->save();
		$this->workshop->verzend_email( '_betaling', $this->workshop->bestel_order( 0.0, $this->workshop->datum ) );
	}

		/**
		 * Bevestig de workshop.
		 *
		 * @since 5.0.0
		 */
	public function bevestig() {
		$herbevestiging             = $this->workshop->definitief;
		$this->workshop->definitief = true;
		$this->workshop->save();
		if ( ! $herbevestiging ) {
			return $this->workshop->verzend_email( '_bevestiging' );
		}
		$order = new Order( $this->workshop->geef_referentie() );
		if ( $order->id ) { // Als er al een factuur is aangemaakt, pas dan de order en factuur aan.
			$factuur = $this->workshop->wijzig_order( $order->id );
			if ( false === $factuur ) { // De factuur is aangemaakt in een periode die boekhoudkundig geblokkeerd is, correctie is niet mogelijk.
				return false;
			} elseif ( ! empty( $factuur ) ) { // Er was al een factuur die nog gecorrigeerd mag worden.
				return $this->workshop->verzend_email( '_betaling', $factuur );
			}
		}
		return $this->workshop->verzend_email( '_herbevestiging' );
	}


}
