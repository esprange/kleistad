<?php
/**
 * Definieer de saldo actie class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad SaldoActie class.
 *
 * @since 6.14.7
 */
class SaldoActie {

	/**
	 * Het Saldo object
	 *
	 * @var Saldo $saldo Het saldo.
	 */
	private Saldo $saldo;

	/**
	 * Constructor
	 *
	 * @param Saldo $saldo Het saldo.
	 */
	public function __construct( Saldo $saldo ) {
		$this->saldo = $saldo;
	}

	/**
	 * Voeg een nieuw saldo toe.
	 *
	 * @param float  $bedrag      Het toe te voegen bedrag.
	 * @param string $betaalwijze De betaalwijze, ideal of bank.
	 * @return bool|string redirect url of true.
	 */
	public function nieuw( float $bedrag, string $betaalwijze ): bool|string {
		$this->saldo->mutaties->toevoegen(
			new SaldoMutatie(
				$this->saldo->get_referentie(),
				$bedrag,
			)
		);
		$this->saldo->artikel_type = 'saldo';
		$this->saldo->save();
		if ( 'ideal' === $betaalwijze ) {
			return $this->saldo->betaling->doe_ideal( 'Bedankt voor de betaling! Het saldo wordt aangepast en er wordt een email verzonden met bevestiging', $bedrag, $this->saldo->get_referentie() );
		}
		$order = new Order( $this->saldo->get_referentie() );
		$this->saldo->verzend_email( '_bank', $order->bestel() );
		return true;
	}

	/**
	 * Annulering van de storting.
	 */
	public function afzeggen() : void {
		$this->saldo->remove_mutatie();
		$this->saldo->save();
	}

	/**
	 * Stort het saldo terug.
	 *
	 * @param string $iban  Het iban bankrekening nummer waarop terug geboekt moet worden.
	 * @param string $rnaam De naam behorende bij de bankrekening.
	 * @return bool
	 */
	public function doe_restitutie( string $iban, string $rnaam ) : bool {
		if ( $this->saldo->restitutie_actief ) {
			return false;
		}
		$huidig_saldo              = $this->saldo->bedrag;
		$this->saldo->artikel_type = 'restitutie';
		$this->saldo->mutaties->toevoegen(
			new SaldoMutatie(
				$this->maak_code( 'adminkosten' ),
				-opties()['administratiekosten'],
				'administratiekosten ivm restitutie saldo'
			)
		);
		$this->saldo->mutaties->toevoegen(
			new SaldoMutatie(
				$this->saldo->get_referentie() . "-{$this->saldo->artikel_type}",
				- $this->saldo->bedrag + opties()['administratiekosten'],
			)
		);
		$this->saldo->bedrag           -= opties()['administratiekosten'];
		$this->saldo->restitutie_actief = true;
		$this->saldo->save( 'restitutie saldo' );
		$order = new Order( $this->saldo->get_referentie() );
		$this->saldo->verzend_email(
			'_terugboeking',
			$order->restitueren(
				$huidig_saldo,
				opties()['administratiekosten'],
				'terugstorting restant saldo',
				sprintf( 'Het bedrag wordt teruggestort op rekening %s t.n.v. %s', $iban, $rnaam )
			)
		);
		return true;
	}

	/**
	 * Correctie van het uitstaand saldo door een beheerder/
	 *
	 * @param float $nieuw_saldo Nieuw saldo.
	 */
	public function correctie( float $nieuw_saldo ) : void {
		$verschil            = $nieuw_saldo - $this->saldo->bedrag;
		$corrector           = wp_get_current_user()->display_name;
		$this->saldo->bedrag = $nieuw_saldo;
		$this->saldo->mutaties->toevoegen(
			new SaldoMutatie(
				$this->maak_code( 'correctie' ),
				$verschil,
				"correctie door $corrector"
			)
		);
		$this->saldo->save( "correctie door $corrector" );
	}

	/**
	 * Registreer een materialen verbruik
	 *
	 * @param int    $verbruik Het verbruik in gram.
	 * @param string $cursus   De cursus waar het verbruik plaatsvindt.
	 *
	 * @return void
	 */
	public function verbruik( int $verbruik, string $cursus ) : void {
		$kosten = opties()['materiaalprijs'] * $verbruik / 1000;
		if ( 0.01 > $kosten ) {
			return;
		}
		$this->saldo->bedrag -= $kosten;
		$this->saldo->mutaties->toevoegen(
			new SaldoMutatie(
				$this->maak_code( 'verbruik' ),
				- $kosten,
				"$verbruik gram materialen: $cursus",
				$verbruik
			)
		);
		$this->saldo->save( "$cursus verbruik materialen geregisteerd door " . wp_get_current_user()->display_name );
		if ( 0 > $this->saldo->bedrag ) {
			$this->saldo->verzend_email( '_negatief' );
		}
	}

	/**
	 * Registreer een aankoop op saldo
	 *
	 * @param float  $bedrag Het bedrag van de aankoop.
	 * @param string $reden  Het artikel.
	 *
	 * @return void
	 */
	public function verkoop( float $bedrag, string $reden ) : void {
		if ( 0.01 > $bedrag ) {
			return;
		}
		$this->saldo->bedrag -= $bedrag;
		$this->saldo->mutaties->toevoegen(
			new SaldoMutatie(
				$this->maak_code( 'overig' ),
				- $bedrag,
				"koop $reden"
			)
		);
		$this->saldo->save( $reden );
		if ( 0 > $this->saldo->bedrag ) {
			$this->saldo->verzend_email( '_negatief' );
		}
	}

	/**
	 * Maak een code aan voor een mutatie zonder order.
	 *
	 * @param string $postfix Achtervoegsel.
	 *
	 * @return string
	 */
	private function maak_code( string $postfix ) : string {
		return sprintf( '%s%d-%s', Saldo::DEFINITIE['prefix'], $this->saldo->klant_id, $postfix );
	}
}
