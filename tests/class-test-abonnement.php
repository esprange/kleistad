<?php
/**
 * Class Abonnement Test
 *
 * @package Kleistad
 */

namespace Kleistad;

/**
 * Abonnement test case.
 */
class Test_Abonnement extends Kleistad_UnitTestCase {

	/**
	 * Maak een abonnement
	 *
	 * @return Abonnement
	 */
	private function maak_abonnement(): Abonnement {

		$role = add_role( LID, 'Kleistad abonnee' );
		if ( is_object( $role ) ) {
			$role->add_cap( RESERVEER, true );
		}

		$abonnee_id = $this->factory->user->create();
		$abonnement = $this->getMockBuilder( Abonnement::class )->setMethods( [ 'maak_factuur' ] )->setConstructorArgs(
			[
				$abonnee_id,
			]
		)->getMock();
		$abonnement->method( 'maak_factuur' )->willReturn( 'file' );

		return $abonnement;
	}

	/**
	 * Test creation and modification of an abonnement.
	 */
	public function test_abonnement() {
		$abonnement = $this->maak_abonnement();
		$this->assertTrue( $abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dinsdag', 'dit is een test', 'bank' ), 'abonnement start bank incorrect' );
		$this->assertTrue( user_can( $abonnement->klant_id, LID ), 'abonnement rol incorrect' );
	}

	/**
	 * Test function erase
	 */
	public function test_erase() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dinsdag', 'dit is een test', 'bank' );
		$abonnement->erase();
		$this->assertFalse( user_can( $abonnement->klant_id, LID ), 'erase rol incorrect' );
	}

	/**
	 * Test function is_gepauzeerd
	 */
	public function test_is_gepauzeerd() {
		$abonnement = $this->maak_abonnement();
		$this->assertFalse( $abonnement->is_gepauzeerd(), 'is_gepauzeerd tijdens actief incorrect' );
		$abonnement->pauze_datum    = strtotime( 'yesterday' );
		$abonnement->herstart_datum = strtotime( 'tomorrow' );
		$this->assertTrue( $abonnement->is_gepauzeerd(), 'is_gepauzeerd tijdens pauze incorrect' );
	}

	/**
	 * Test function is_geannuleerd
	 */
	public function test_is_geannuleerd() {
		$abonnement = $this->maak_abonnement();
		$this->assertFalse( $abonnement->is_geannuleerd(), 'is_geannuleerd tijdens actief incorrect' );
		$abonnement->eind_datum = strtotime( 'yesterday' );
		$this->assertTrue( $abonnement->is_geannuleerd(), 'is_geannuleerd na einde incorrect' );
	}

	/**
	 * Test function geef_referentie
	 */
	public function test_geef_referentie() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dinsdag', 'dit is een test', 'bank' );
		$this->assertRegExp( '~A[0-9]+-start-2021[0-9]{2}~', $abonnement->geef_referentie(), 'referentie incorrect' );
		$abonnement->artikel_type = 'regulier';
		$this->assertRegExp( '~A[0-9]+-regulier-2021[0-9]{2}~', $abonnement->geef_referentie(), 'referentie incorrect' );
	}

	/**
	 * Test function geef_overbrugging_fractie en geef_pauze_fractie
	 */
	public function test_geef_fractie() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( 'first day of this month 0:00' ), 'beperkt', 'dinsdag', 'dit is een test', 'bank' );
		$this->assertTrue( 0.0 < $abonnement->geef_overbrugging_fractie(), 'overbrugging fractie incorrect' );

		$abonnement->pauze_datum    = strtotime( 'first day of this month 0:00' );
		$abonnement->herstart_datum = strtotime( '+ 10 days', $abonnement->pauze_datum );
		$this->assertTrue( 0.0 < $abonnement->geef_pauze_fractie(), 'pauze fractie incorrect' );
		$abonnement->herstart_datum = strtotime( 'first day of next month 0:00' );
		$this->assertTrue( 0.0 === $abonnement->geef_pauze_fractie(), 'pauze fractie incorrect' );
	}

	/**
	 * Test start_incasso function
	 */

	/**
	 * Test stop_incasso function
	 */

	/**
	 * Test pauzeren function
	 */

	/**
	 * Test starten function
	 */

	/**
	 * Test stoppen function
	 */

	/**
	 * Test wijzigen function
	 */

	/**
	 * Test overbrugging function
	 */

	/**
	 * Test factureer function
	 */

	/**
	 * Test autoriseer function
	 */

}
