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
	 * @return string
	 */
	private function trunc( string $tekst ) : string {
		$maxlen = 63;
		$ellip  = '...';
		$tekst  = trim( $tekst );
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
		$hoogte = 32;
		$this->SetLeftMargin( 25 );
		$this->AddPage();
		$this->setFont( 'Arial', 'B', 24 );
		$this->Cell( 0, $hoogte, $titel );
		$this->setX( 150 );
		$this->Image( plugin_dir_path( dirname( __FILE__ ) ) . 'public/images/logo kleistad-email.jpg' );
	}

	/**
	 * Toon de klant informatie
	 *
	 * @param array $args De te tonen informatie.
	 */
	private function klant( array $args ) : void {
		$hoogte = 6;
		$this->setY( 65 );
		$this->SetLeftMargin( 25 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 0, $hoogte, utf8_decode( $args['naam'] ), 0, 1 );
		$this->setFont( 'Arial', '', 10 );
		$this->MultiCell( 0, $hoogte, utf8_decode( $args['adres'] ) );
	}

	/**
	 * Toon het info veld over factuurnr, datum en Kleistad info.
	 *
	 * @param string $factuurnr  Het nummer van de factuur.
	 * @param int    $datum      De factuur datum.
	 * @param string $referentie De referentie.
	 */
	private function info( string $factuurnr, int $datum, string $referentie ) : void {
		$hoogte = 6;
		$this->setY( 65 );
		$this->SetRightMargin( 75 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 0, $hoogte, 'Factuurdatum', 0, 1, 'R' );
		$this->setFont( 'Arial' );
		$this->Cell( 0, $hoogte, strftime( '%d-%m-%Y', $datum ), 0, 1, 'R' );
		$this->setFont( 'Arial', 'B' );
		$this->Cell( 0, $hoogte, 'Factuurnummer', 0, 1, 'R' );
		$this->setFont( 'Arial' );
		$this->Cell( 0, $hoogte, $factuurnr, 0, 1, 'R' );
		$this->setFont( 'Arial', 'B' );
		$this->Cell( 0, $hoogte, 'Referentie', 0, 1, 'R' );
		$this->setFont( 'Arial' );
		$this->Cell( 0, $hoogte, $referentie, 0, 0, 'R' );

		$this->Line( 143, 65, 143, 65 + 6 * $hoogte );

		$hoogte = 5;
		$this->setY( 65 );
		$this->SetLeftMargin( 145 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 0, $hoogte, 'Stichting Kleistad', 0, 2 );
		$this->setFont( 'Arial', '', 8 );
		$this->MultiCell( 45, $hoogte, "Neonweg 12\n3812 RH Amersfoort\n\nKvK 68731248\nBTW nr NL857567044B01\nIBAN NL10 RABO 0191913308" );
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
		$breedte = [
			'aantal'       => 15,
			'artikel'      => 105,
			'stuksprijs'   => 20,
			'prijs'        => 20,
			'samenvatting' => 15 + 105 + 20,
			'volledig'     => 15 + 105 + 20 + 20,
		];
		$hoogte  = 6;
		$this->ln();
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $breedte['aantal'], $hoogte, 'Aantal', 'TB', 0, 'C' );
		$this->Cell( $breedte['artikel'], $hoogte, 'Omschrijving', 'TB', 0, 'L' );
		$this->Cell( $breedte['stuksprijs'], $hoogte, 'Stuksprijs', 'TB', 0, 'C' );
		$this->Cell( $breedte['prijs'], $hoogte, 'Prijs', 'TB', 1, 'C' );
		$this->setFont( 'Arial' );
		foreach ( $orderregels as $orderregel ) {
			$this->Cell( $breedte['aantal'], $hoogte, $orderregel->aantal, 0, 0, 'C' );
			$this->Cell( $breedte['artikel'], $hoogte, utf8_decode( $this->trunc( $orderregel->artikel ) ), 0, 0, 'L' );
			$this->Cell( $breedte['stuksprijs'], $hoogte, $this->euro( $orderregel->prijs + $orderregel->btw ), 0, 0, 'R' );
			$this->Cell( $breedte['prijs'], $hoogte, $this->euro( $orderregel->aantal * ( $orderregel->prijs + $orderregel->btw ) ), 0, 1, 'R' );
		}
		$this->Ln( $hoogte * 2 );
		$this->Cell( $breedte['volledig'], 0, '', 'T', 1 );
		$this->Cell( $breedte['samenvatting'], $hoogte, 'Totaal', 0, 0, 'R' );
		$this->Cell( $breedte['prijs'], $hoogte, $this->euro( $orderregels->get_bruto() ), 0, 1, 'R' );
		$this->Cell( $breedte['samenvatting'], $hoogte, 'Inclusief BTW 21%', 0, 0, 'R' );
		$this->Cell( $breedte['prijs'], $hoogte, $this->euro( $orderregels->get_btw() ), 'B', 1, 'R' );
		$this->Cell( $breedte['samenvatting'], $hoogte, 'Reeds betaald ', 0, 0, 'R' );
		$this->Cell( $breedte['prijs'], $hoogte, $this->euro( $betaald ), 'B', 1, 'R' );
		$this->setFont( 'Arial', 'B' );
		if ( 0 <= $nog_te_betalen ) {
			$this->Cell( $breedte['samenvatting'], $hoogte, 'Verschuldigd saldo', 0, 0, 'R' );
			$this->Cell( $breedte['prijs'], $hoogte, $this->euro( $nog_te_betalen ), 0, 1, 'R' );
			return;
		}
		$this->Cell( $breedte['samenvatting'], $hoogte, 'Na verrekening te ontvangen', 0, 0, 'R' );
		$this->Cell( $breedte['prijs'], $hoogte, $this->euro( - $nog_te_betalen ), 0, 1, 'R' );
	}

	/**
	 * Toon het opmerkingen veld.
	 *
	 * @param string $arg De te tonen tekst.
	 */
	private function opmerking( string $arg ) : void {
		if ( ! empty( $arg ) ) {
			$hoogte = 6;
			$this->SetLeftMargin( 25 );
			$this->Ln( 2 * $hoogte );
			$this->setFont( 'Arial', 'B', 10 );
			$this->Cell( 0, $hoogte, 'Opmerkingen', 0, 1 );
			$this->setFont( 'Arial' );
			$this->MultiCell( 0, $hoogte, utf8_decode( $arg ) );
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
		$factuurnr = $order->get_factuurnummer();
		$filenaam  = 'local' === wp_get_environment_type() ?
			sprintf( '%s/%s-%s', sys_get_temp_dir(), "{$type}factuur", $factuurnr ) :
			sprintf( '%s/facturen/%s-%s', wp_get_upload_dir()['basedir'], "{$type}factuur", $factuurnr );
		$versie    = '';
		$file      = "$filenaam.pdf";
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
		$this->order( $order->orderregels, $order->betaald, $order->get_te_betalen() );
		$this->opmerking( $order->opmerking );
		$this->Output( 'F', $file );
		return $file;
	}

	/**
	 * Geef een overzicht van alle facturen behorende bij een factuur nummer.
	 *
	 * @param string $factuurnr Het order factuur nr.
	 *
	 * @return array
	 */
	public function overzicht( string $factuurnr ) : array {
		$pattern = 'local' === wp_get_environment_type() ?
			sprintf( '%s/*factuur-%s.*', sys_get_temp_dir(), $factuurnr ) :
			sprintf( '%s/facturen/*factuur-%s.*', wp_get_upload_dir()['basedir'], $factuurnr );
		$files   = glob( $pattern );
		usort(
			$files,
			function( $links, $rechts ) {
				return filemtime( $rechts ) <=> filemtime( $links ); // Nieuwste factuur bovenaan.
			}
		);
		return 'local' === wp_get_environment_type() ? $files : str_replace( wp_get_upload_dir()['basedir'], wp_get_upload_dir()['baseurl'], $files );
	}

}
