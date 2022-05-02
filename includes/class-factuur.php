<?php
/**
 * De  class voor het aanmaken van een factuur.
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * De class voor factuur.
 */
class Factuur extends PDF {

	/**
	 * Maak de factuur aan.
	 *
	 * @param Order $order De order.
	 * @return string Locatie van het bestand.
	 */
	public function run( Order $order ) : string {
		$type      = $order->orderregels->get_bruto() < 0 ? 'credit' : '';
		$factuurnr = $order->get_factuurnummer();
		$file      = $this->filenaam( $factuurnr, $type );
		$this->init( basename( $file ), strtoupper( $type ) . ' FACTUUR' );
		$this->klant( $order->klant );
		$this->info( $factuurnr, $order->datum, $order->referentie );
		$this->order_info( $order->orderregels, $order->betaald, $order->get_te_betalen() );
		$this->opmerking( $order->opmerking );
		$this->Output( 'F', $file );
		return $file;
	}

	/**
	 * Geef de bestaande factuur terug.
	 *
	 * @param Order $order De order.
	 * @return array Locatie en url van het bestand.
	 */
	public function get( Order $order ) : array {
		$factuurnr = $order->get_factuurnummer();
		$type      = $order->credit ? 'credit' : '';
		$bestand   = sprintf( '%s-%s.pdf', "{$type}factuur", $factuurnr );
		if ( defined( 'KLEISTAD_TEST' ) ) {
			return [
				'locatie' => sys_get_temp_dir() . "/$bestand",
				'url'     => '',
			];
		}
		return [
			'locatie' => wp_get_upload_dir()['basedir'] . "/facturen/$bestand",
			'url'     => wp_get_upload_dir()['baseurl'] . "/facturen/$bestand",
		];
	}

	/**
	 * Geef een overzicht van alle facturen behorende bij een factuur nummer.
	 *
	 * @param Order $order De order.
	 *
	 * @return array
	 */
	public function overzicht( Order $order ) : array {
		$factuurnr = $order->get_factuurnummer();
		$pattern   = 'local' === wp_get_environment_type() ?
			sprintf( '%s/*factuur-%s.*', sys_get_temp_dir(), $factuurnr ) :
			sprintf( '%s/facturen/*factuur-%s.*', wp_get_upload_dir()['basedir'], $factuurnr );
		$files     = glob( $pattern );
		usort(
			$files,
			function( $links, $rechts ) {
				return filemtime( $rechts ) <=> filemtime( $links ); // Nieuwste factuur bovenaan.
			}
		);
		return 'local' === wp_get_environment_type() ? $files : str_replace( wp_get_upload_dir()['basedir'], wp_get_upload_dir()['baseurl'], $files );
	}

	/**
	 * Bepaal de filenaam van de factuur
	 *
	 * @param string $factuurnr Het factuur nr.
	 * @param string $type      Het type factuur.
	 *
	 * @return string
	 */
	private function filenaam( string $factuurnr, string $type ) : string {
		$filenaam = 'local' === wp_get_environment_type() ?
			sprintf( '%s/%s-%s', sys_get_temp_dir(), "{$type}factuur", $factuurnr ) :
			sprintf( '%s/facturen/%s-%s', wp_get_upload_dir()['basedir'], "{$type}factuur", $factuurnr );
		$file     = "$filenaam.pdf";
		if ( file_exists( $file ) ) {
			$versie = 0;
			do {
				$versie++;
				$file = "$filenaam.$versie.pdf";
			} while ( file_exists( $file ) );
		}
		return $file;
	}

	/**
	 * Toon de klant informatie
	 *
	 * @param array $args De te tonen informatie.
	 */
	private function klant( array $args ) : void {
		$this->SetY( 65 );
		$this->SetFont( self::CSET, 'B', self::NORMAAL );
		$this->Cell( 0, self::H_NORMAAL, utf8_decode( $args['naam'] ), 0, 1 );
		$this->SetFont( self::CSET, '', self::NORMAAL );
		$this->MultiCell( 0, self::H_NORMAAL, utf8_decode( $args['adres'] ) );
	}

	/**
	 * Toon het info veld over factuurnr, datum en Kleistad info.
	 *
	 * @param string $factuurnr  Het nummer van de factuur.
	 * @param int    $datum      De factuur datum.
	 * @param string $referentie De referentie.
	 */
	private function info( string $factuurnr, int $datum, string $referentie ) : void {
		$info = [
			'Factuurdatum'  => wp_date( 'd-m-Y', $datum ),
			'Factuurnummer' => $factuurnr,
			'Referentie'    => $referentie,
		];
		$this->SetY( 65 );
		foreach ( $info as $key => $value ) {
			$this->SetFont( self::CSET, 'B', self::NORMAAL );
			$this->SetX( 90 );
			$this->Cell( 35, self::H_NORMAAL, $key, 0, 1, 'R' );
			$this->SetFont( self::CSET, '', self::NORMAAL );
			$this->SetX( 90 );
			$this->Cell( 35, self::H_NORMAAL, $value, 0, 1, 'R' );
		}
		$this->Line( 130, 65, 130, 65 + 6 * self::H_NORMAAL );
		$this->SetXY( 135, 65 );
		$this->SetFont( self::CSET, 'B', self::NORMAAL );
		$this->Cell( 0, self::H_NORMAAL, 'Stichting Kleistad', 0, 1 );
		$this->SetX( 135 );
		$this->SetFont( self::CSET, '', self::KLEIN );
		$this->MultiCell( 45, self::H_KLEIN, "Neonweg 12\n3812 RH Amersfoort\n\nKvK 68731248\nBTW nr NL857567044B01\nIBAN NL10 RABO 0191913308" );
	}

	/**
	 * Toon de bestelling.
	 *
	 * @param Orderregels $orderregels         De order regels behorende bij de bestelling.
	 * @param float       $betaald        Wat er al betaald is.
	 * @param float       $nog_te_betalen Wat er nog betaald moet worden ingeval van een credit_factuur.
	 */
	private function order_info( Orderregels $orderregels, float $betaald, float $nog_te_betalen ) : void {
		$this->SetY( 120 );
		$breedte = [
			'aantal'       => 15,
			'artikel'      => 105,
			'stuksprijs'   => 20,
			'prijs'        => 20,
			'samenvatting' => 15 + 105 + 20,
			'volledig'     => 15 + 105 + 20 + 20,
		];
		$this->Ln();
		$this->setFont( self::CSET, 'B', self::NORMAAL );
		$this->Cell( $breedte['aantal'], self::H_NORMAAL, 'Aantal', 'TB', 0, 'C' );
		$this->Cell( $breedte['artikel'], self::H_NORMAAL, 'Omschrijving', 'TB', 0, 'L' );
		$this->Cell( $breedte['stuksprijs'], self::H_NORMAAL, 'Stuksprijs', 'TB', 0, 'C' );
		$this->Cell( $breedte['prijs'], self::H_NORMAAL, 'Prijs', 'TB', 1, 'C' );
		$this->SetFont( self::CSET );
		foreach ( $orderregels as $orderregel ) {
			$this->Cell( $breedte['aantal'], self::H_NORMAAL, $orderregel->aantal, 0, 0, 'C' );
			$this->Cell( $breedte['artikel'], self::H_NORMAAL, utf8_decode( $this->trunc( $orderregel->artikel, 60 ) ), 0, 0, 'L' );
			$this->Cell( $breedte['stuksprijs'], self::H_NORMAAL, $this->euro( $orderregel->prijs + $orderregel->btw ), 0, 0, 'R' );
			$this->Cell( $breedte['prijs'], self::H_NORMAAL, $this->euro( $orderregel->aantal * ( $orderregel->prijs + $orderregel->btw ) ), 0, 1, 'R' );
		}
		$this->Ln( self::H_NORMAAL * 2 );
		$this->Cell( $breedte['volledig'], 0, '', 'T', 1 );
		$this->Cell( $breedte['samenvatting'], self::H_NORMAAL, 'Totaal', 0, 0, 'R' );
		$this->Cell( $breedte['prijs'], self::H_NORMAAL, $this->euro( $orderregels->get_bruto() ), 0, 1, 'R' );
		$this->Cell( $breedte['samenvatting'], self::H_NORMAAL, 'Inclusief BTW ' . BTW . '%', 0, 0, 'R' );
		$this->Cell( $breedte['prijs'], self::H_NORMAAL, $this->euro( $orderregels->get_btw() ), 'B', 1, 'R' );
		$this->Cell( $breedte['samenvatting'], self::H_NORMAAL, 'Reeds betaald ', 0, 0, 'R' );
		$this->Cell( $breedte['prijs'], self::H_NORMAAL, $this->euro( $betaald ), 'B', 1, 'R' );
		$this->SetFont( self::CSET, 'B' );
		if ( 0 <= $nog_te_betalen ) {
			$this->Cell( $breedte['samenvatting'], self::H_NORMAAL, 'Verschuldigd saldo', 0, 0, 'R' );
			$this->Cell( $breedte['prijs'], self::H_NORMAAL, $this->euro( $nog_te_betalen ), 0, 1, 'R' );
			return;
		}
		$this->Cell( $breedte['samenvatting'], self::H_NORMAAL, 'Na verrekening te ontvangen', 0, 0, 'R' );
		$this->Cell( $breedte['prijs'], self::H_NORMAAL, $this->euro( - $nog_te_betalen ), 0, 1, 'R' );
	}

	/**
	 * Toon het opmerkingen veld.
	 *
	 * @param string $arg De te tonen tekst.
	 */
	private function opmerking( string $arg ) : void {
		if ( ! empty( $arg ) ) {
			$this->Ln( 2 * self::H_NORMAAL );
			$this->SetFont( self::CSET, 'B', self::NORMAAL );
			$this->Cell( 0, self::H_NORMAAL, 'Opmerkingen', 0, 1 );
			$this->SetFont( self::CSET );
			$this->MultiCell( 0, self::H_NORMAAL, utf8_decode( $arg ) );
		}
	}

}
