<?php
/**
 * De definitie van de inschrijvingen class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad inschrijvingen class.
 *
 * @since 6.11.0
 */
class Inschrijvingen implements Countable, Iterator {

	/**
	 * De inschrijvingen
	 *
	 * @var array $inschrijvingen De inschrijvingen.
	 */
	private $inschrijvingen = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param int $select_cursus_id De cursus id.
	 */
	public function __construct( int $select_cursus_id = null ) {
		$cursisten = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Inschrijving::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
			]
		);
		foreach ( $cursisten as $cursist ) {
			$inschrijvingen = (array) get_user_meta( $cursist->ID, Inschrijving::META_KEY, true );
			if ( ! is_null( $select_cursus_id ) ) {
				if ( ! isset( $inschrijvingen[ $select_cursus_id ] ) ) {
					continue;
				}
				$this->inschrijvingen[] = new Inschrijving( $select_cursus_id, $cursist->ID );
				continue;
			}
			foreach ( array_keys( $inschrijvingen ) as $cursus_id ) {
				$this->inschrijvingen[] = new Inschrijving( $cursus_id, $cursist->ID );
			}
		}
	}

	/**
	 * Geef het aantal inschrijvingen terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->inschrijvingen );
	}

	/**
	 * Geef de huidige inschrijving terug.
	 *
	 * @return inschrijving De inschrijving.
	 */
	public function current(): Inschrijving {
		return $this->inschrijvingen[ $this->current_index ];
	}

	/**
	 * Geef de sleutel terug.
	 *
	 * @return int De sleutel.
	 */
	public function key(): int {
		return $this->current_index;
	}

	/**
	 * Ga naar de volgende in de lijst.
	 */
	public function next() {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 */
	public function rewind() {
		$this->current_index = 0;
	}

	/**
	 * Bepaal of het element bestaat.
	 *
	 * @return bool Of het bestaat of niet.
	 */
	public function valid(): bool {
		return isset( $this->inschrijvingen[ $this->current_index ] );
	}

		/**
		 * Controleer of er betalingsverzoeken verzonden moeten worden.
		 *
		 * @since 6.1.0
		 */
	public static function doe_dagelijks() {
		$vandaag = strtotime( 'today' );
		foreach ( new self() as $inschrijving ) {
			/**
			 * Geen acties voor medecursisten, oude of vervallen cursus deelnemers of die zelf geannuleerd hebben.
			 */
			if ( 0 === $inschrijving->aantal ||
				$inschrijving->geannuleerd ||
				$inschrijving->cursus->vervallen ||
				$vandaag > $inschrijving->cursus->eind_datum
			) {
				continue;
			}
			/**
			 * Wachtlijst emails, voor cursisten die nog niet ingedeeld zijn en alleen als de cursus nog niet gestart is.
			 * Laatste wachtdatum is de datum er ruimte is ontstaan of 0 als er geen ruimte is. Als gisteren de ruimte ontstond is de datum dus nu.
			 * Iedereen die vooraf 'nu' wacht krijgt de email en die wacht op vervolg als er iets vrijkomt na morgen 0:00.
			 */
			if ( ! $inschrijving->ingedeeld ) {
				if ( $inschrijving->wacht_datum && $vandaag < $inschrijving->cursus->start_datum && $inschrijving->wacht_datum < $inschrijving->cursus->ruimte_datum ) {
					$inschrijving->wacht_datum = strtotime( 'tomorrow' );
					$inschrijving->betaal_link = $inschrijving->maak_link( [ 'code' => $inschrijving->code ], 'wachtlijst' );
					$inschrijving->save();
					$inschrijving->verzend_email( '_ruimte' );
				}
				continue;
			}
			/**
			 * Restant betaal emails, alleen voor cursisten die ingedeeld zijn en de cursus binnenkort start.
			 */
			if ( ! $inschrijving->restant_email && $inschrijving->cursus->is_binnenkort() ) {
				$order = new Order( $inschrijving->geef_referentie() );
				if ( $order->id && ! $order->gesloten ) {
					$inschrijving->artikel_type  = 'cursus';
					$inschrijving->restant_email = true;
					$inschrijving->betaal_link   = $inschrijving->maak_link(
						[
							'order' => $order->id,
							'art'   => $inschrijving->artikel_type,
						],
						'betaling'
					);
					$inschrijving->save();
					$inschrijving->verzend_email( '_restant' );
				}
			}
		}
	}

}
