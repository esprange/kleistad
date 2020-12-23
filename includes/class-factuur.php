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

use FPDF;

/**
 * De class voor email, maakt gebruik van de fdpf class, zie ook http://www.fpdf.org.
 */
class Factuur extends FPDF {

	/**
	 * Output het getal als een euro bedrag.
	 *
	 * @param float $bedrag Het bedrag.
	 * @return string;
	 */
	private function euro( float $bedrag ) : string {
		return chr( 128 ) . ' ' . number_format_i18n( $bedrag, 2 );
	}

	/**
	 * Kort een tekst af als deze te lang is voor een veld.
	 *
	 * @param string $tekst  De tekst.
	 * @param int    $maxlen De maximale lengte.
	 * @return string
	 */
	private function trunc( string $tekst, int $maxlen ) : string {
		$ellip = '...';
		$tekst = trim( $tekst );
		if ( strlen( $tekst ) <= $maxlen ) {
			return $tekst;
		}
		$_tekst  = strrev( substr( $tekst, 0, $maxlen - strlen( $ellip ) ) );
		$matches = [];
		preg_match( '/\s/', $_tekst, $matches );
		$_tekst = strrev( substr( $_tekst, strpos( $_tekst, $matches[0] ) ) );
		return $_tekst . $ellip;
	}

	/**
	 * Start de pagina.
	 *
	 * @param string $titel De titel van de pagina.
	 */
	private function start( string $titel ) : void {
		$h = 32;
		$this->SetLeftMargin( 25 );
		$this->AddPage();
		$this->setFont( 'Arial', 'B', 24 );
		$this->Cell( 0, $h, $titel );
		$this->setX( 150 );
		$this->Image( plugin_dir_path( dirname( __FILE__ ) ) . 'public/images/logo kleistad-email.jpg' );
	}

	/**
	 * Toon de klant informatie
	 *
	 * @param array $args De te tonen informatie.
	 */
	private function klant( array $args ) : void {
		$h = 6;
		$this->setY( 65 );
		$this->SetLeftMargin( 25 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 0, $h, utf8_decode( $args['naam'] ), 0, 1 );
		$this->setFont( 'Arial', '', 10 );
		$this->MultiCell( 0, $h, utf8_decode( $args['adres'] ) );
	}

	/**
	 * Toon het info veld over factuurnr, datum en Kleistad info.
	 *
	 * @param string $factuurnr  Het nummer van de factuur.
	 * @param int    $datum      De factuur datum.
	 * @param string $referentie De referentie.
	 */
	private function info( string $factuurnr, int $datum, string $referentie ) : void {
		$h = 6;
		$this->setY( 65 );
		$this->SetRightMargin( 75 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 0, $h, 'Factuurdatum', 0, 1, 'R' );
		$this->setFont( 'Arial' );
		$this->Cell( 0, $h, strftime( '%d-%m-%Y', $datum ), 0, 1, 'R' );
		$this->setFont( 'Arial', 'B' );
		$this->Cell( 0, $h, 'Factuurnummer', 0, 1, 'R' );
		$this->setFont( 'Arial' );
		$this->Cell( 0, $h, $factuurnr, 0, 1, 'R' );
		$this->setFont( 'Arial', 'B' );
		$this->Cell( 0, $h, 'Referentie', 0, 1, 'R' );
		$this->setFont( 'Arial' );
		$this->Cell( 0, $h, $referentie, 0, 0, 'R' );

		$this->Line( 143, 65, 143, 65 + 6 * $h );

		$h = 5;
		$this->setY( 65 );
		$this->SetLeftMargin( 145 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 0, $h, 'Stichting Kleistad', 0, 2 );
		$this->setFont( 'Arial', '', 8 );
		$this->MultiCell( 45, $h, "Neonweg 12\n3812 RH Amersfoort\n\nKvK 68731248\nBTW nr NL857567044B01\nIBAN NL10 RABO 0191913308" );
	}

	/**
	 * Toon de bestelling.
	 *
	 * @param Orderregels $orderregels         De order regels behorende bij de bestelling.
	 * @param float       $betaald        Wat er al betaald is.
	 * @param float       $nog_te_betalen Wat er nog betaald moet worden ingeval van een credit_factuur.
	 */
	private function order( Orderregels $orderregels, float $betaald, float $nog_te_betalen ) : void {
		$this->SetY( 120 );
		$this->SetLeftMargin( 25 );
		$w = [
			'aantal'       => 15,
			'artikel'      => 105,
			'stuksprijs'   => 20,
			'prijs'        => 20,
			'samenvatting' => 15 + 105 + 20,
			'volledig'     => 15 + 105 + 20 + 20,
		];
		$h = 6;
		$this->ln();
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $w['aantal'], $h, 'Aantal', 'TB', 0, 'C' );
		$this->Cell( $w['artikel'], $h, 'Omschrijving', 'TB', 0, 'L' );
		$this->Cell( $w['stuksprijs'], $h, 'Stuksprijs', 'TB', 0, 'C' );
		$this->Cell( $w['prijs'], $h, 'Prijs', 'TB', 1, 'C' );
		$this->setFont( 'Arial' );
		foreach ( $orderregels as $orderregel ) {
			$this->Cell( $w['aantal'], $h, $orderregel->aantal, 0, 0, 'C' );
			$this->Cell( $w['artikel'], $h, utf8_decode( $this->trunc( $orderregel->artikel, 63 ) ), 0, 0, 'L' );
			$this->Cell( $w['stuksprijs'], $h, $this->euro( $orderregel->prijs + $orderregel->btw ), 0, 0, 'R' );
			$this->Cell( $w['prijs'], $h, $this->euro( $orderregel->aantal * ( $orderregel->prijs + $orderregel->btw ) ), 0, 1, 'R' );
		}
		$this->Ln( $h * 2 );
		$this->Cell( $w['volledig'], 0, '', 'T', 1 );
		$this->Cell( $w['samenvatting'], $h, 'Totaal', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $orderregels->bruto() ), 0, 1, 'R' );
		$this->Cell( $w['samenvatting'], $h, 'Inclusief BTW 21%', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $orderregels->btw() ), 'B', 1, 'R' );
		$this->Cell( $w['samenvatting'], $h, 'Reeds betaald ', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $betaald ), 'B', 1, 'R' );
		$this->setFont( 'Arial', 'B' );
		if ( 0 <= $nog_te_betalen ) {
			$this->Cell( $w['samenvatting'], $h, 'Verschuldigd saldo', 0, 0, 'R' );
			$this->Cell( $w['prijs'], $h, $this->euro( $nog_te_betalen ), 0, 1, 'R' );
			return;
		}
		$this->Cell( $w['samenvatting'], $h, 'Na verrekening te ontvangen', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( - $nog_te_betalen ), 0, 1, 'R' );
	}

	/**
	 * Toon het opmerkingen veld.
	 *
	 * @param string $arg De te tonen tekst.
	 */
	private function opmerking( string $arg ) : void {
		if ( ! empty( $arg ) ) {
			$h = 6;
			$this->SetLeftMargin( 25 );
			$this->Ln( 2 * $h );
			$this->setFont( 'Arial', 'B', 10 );
			$this->Cell( 0, $h, 'Opmerkingen', 0, 1 );
			$this->setFont( 'Arial' );
			$this->MultiCell( 0, $h, utf8_decode( $arg ) );
		}
	}

	/**
	 * Maak de factuur aan.
	 *
	 * @param Order  $order De order.
	 * @param string $type  Het type factuur: gewoon, correctie of credit.
	 * @return string Pad naar de factuur.
	 */
	public function run( Order $order, string $type ) : string {
		$factuurnr  = $order->factuurnummer();
		$upload_dir = wp_get_upload_dir();
		$filenaam   = sprintf( '%s/facturen/%s-%s', $upload_dir['basedir'], "{$type}factuur", $factuurnr );
		$versie     = '';
		$file       = "$filenaam.pdf";
		if ( file_exists( $file ) ) {
			$versie = 0;
			do {
				$versie++;
				$file = "$filenaam.$versie.pdf";
			} while ( file_exists( $file ) );
		}
		$this->SetCreator( get_site_url() );
		$this->SetAuthor( 'Kleistad' );
		$this->SetTitle( ucwords( $type ) . " Factuur $factuurnr.$versie" );
		$this->start( strtoupper( $type ) . ' FACTUUR' );
		$this->klant( $order->klant );
		$this->info( $factuurnr, $order->datum, $order->referentie );
		$this->order( $order->orderregels, $order->betaald, $order->te_betalen() );
		$this->opmerking( $order->opmerking );
		$this->Output( 'F', $file );
		return $file;
	}

	/**
	 * Geef de facturen terug behorend bij factuurnr.
	 *
	 * @param string $factuurnr Het factuur nummer.
	 * @return array De url's van de facturen.
	 */
	public static function facturen( string $factuurnr ) : array {
		$upload_dir = wp_get_upload_dir();
		$files      = glob( sprintf( '%s/facturen/*factuur-%s*', $upload_dir['basedir'], $factuurnr ) ) ?: [];
		usort(
			$files,
			function( $a, $b ) {
				return filemtime( $b ) <=> filemtime( $a ); // Nieuwste factuur bovenaan.
			}
		);
		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $files );
	}

}
