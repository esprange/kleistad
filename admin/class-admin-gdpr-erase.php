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
 * GDPR Erase class
 */
class Admin_GDPR_Erase {

	/**
	 * Erase / verwijder persoonlijke data. Om de consistentie van de database te waarborgen doen we in feite een anonimisering.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Het email adres van de te verwijderen persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 */
	public function eraser( string $email, int $page = 1 ) : array {
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
	public function erase_old_privacy_data() : void {
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
	private function erase_cursussen( int $datum ) : void {
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
	private function erase_dagdelenkaarten( int $datum ) : void {
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
	private function erase_abonnementen( int $datum ) : void {
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
	private function erase_workshops( int $datum ) : void {
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
	private function erase_orders( int $datum ) : void {
		$orders = new Orders();
		foreach ( $orders as $order ) {
			if ( $datum > $order->datum ) {
				$order->erase();
			}
		}
	}

}
