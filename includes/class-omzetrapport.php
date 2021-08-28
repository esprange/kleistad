<?php
/**
 * De  class voor het aanmaken van een omzetrapport.
 *
 * @link       https://www.kleistad.nl
 * @since      6.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use FPDF;

/**
 * De class maakt gebruik van de fdpf class, zie ook http://www.fpdf.org.
 */
class OmzetRapport extends FPDF {

	/**
	 * Start de pagina.
	 *
	 * @param string $titel De titel van de pagina.
	 */
	private function start( string $titel ) {
		$this->SetLeftMargin( 25 );
		$this->AddPage();
		$this->setFont( 'Arial', 'B', 24 );
		$this->Cell( 0, 32, 'Omzet rapport' );
		$this->ln( 20 );
		$this->setFont( 'Arial', 'B', 12 );
		$this->Cell( 0, 16, $titel );
		$this->setX( 150 );
		$this->Image( plugin_dir_path( dirname( __FILE__ ) ) . 'public/images/logo kleistad-email.jpg' );
	}

	/**
	 * Maakt automatisch een footer aan.
	 */
	public function Footer() {
		$this->SetY( -15 );
		$this->SetFont( 'Arial', 'I', 8 );
		$this->Cell( 0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C' );
	}

	/**
	 * Toon de omzet tabel.
	 *
	 * @param array $omzet De omzet.
	 */
	private function tabel( array $omzet ) {
		$this->SetY( 75 );
		$this->SetLeftMargin( 25 );
		$hoogte = 8;
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 50, $hoogte, 'Omzet', 'B', 0, 'L' );
		$this->Cell( 50, $hoogte, 'Netto', 'B', 0, 'R' );
		$this->Cell( 50, $hoogte, 'BTW', 'B', 0, 'R' );
		$this->ln();
		$this->setFont( 'Arial' );
		$totaal_netto = 0;
		$totaal_btw   = 0;
		foreach ( $omzet as $naam => $omzetregel ) {
			if ( 0.0 !== $omzetregel['netto'] ) {
				$totaal_netto += $omzetregel['netto'];
				$totaal_btw   += $omzetregel['btw'];
				$this->Cell( 50, $hoogte, $naam, 0, 0, 'L' );
				$this->Cell( 50, $hoogte, number_format_i18n( $omzetregel['netto'], 2 ), 0, 0, 'R' );
				$this->Cell( 50, $hoogte, number_format_i18n( $omzetregel['btw'], 2 ), 0, 0, 'R' );
				$this->ln();
			}
		}
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 50, $hoogte, 'Totaal', 'T', 0, 'L' );
		$this->Cell( 50, $hoogte, number_format_i18n( $totaal_netto, 2 ), 'T', 0, 'R' );
		$this->Cell( 50, $hoogte, number_format_i18n( $totaal_btw, 2 ), 'T', 0, 'R' );
		$this->ln( 25 );
		$this->setFont( 'Arial' );
		$this->Cell( 0, $hoogte, strftime( '%d-%m-%Y' ) );
	}

	/**
	 * Toon de omzet details.
	 *
	 * @param string $naam         De omzet naam.
	 * @param array  $omzetdetails De details.
	 */
	private function details( string $naam, array $omzetdetails ) {
		$this->setFont( 'Arial', 'B', 20 );
		$this->Cell( 0, 24, $naam );
		$this->SetY( 50 );
		$hoogte = 8;
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 30, $hoogte, 'Code', 'B', 0, 'L' );
		$this->Cell( 50, $hoogte, 'Klant', 'B', 0, 'L' );
		$this->Cell( 20, $hoogte, 'Datum', 'B', 0, 'L' );
		$this->Cell( 20, $hoogte, 'Bedrag', 'B', 0, 'R' );
		$this->Cell( 20, $hoogte, 'BTW', 'B', 0, 'R' );
		$this->ln();
		$this->setFont( 'Arial' );
		foreach ( $omzetdetails as $omzetdetail ) {
			$this->Cell( 30, $hoogte, substr( $omzetdetail['code'], 0, 13 ), 0, 0, 'L' );
			$this->Cell( 50, $hoogte, utf8_decode( substr( $omzetdetail['klant'], 0, 25 ) ), 0, 0, 'L' );
			$this->Cell( 20, $hoogte, strftime( '%d-%m-%Y', $omzetdetail['datum'] ), 0, 0, 'L' );
			$this->Cell( 20, $hoogte, number_format_i18n( $omzetdetail['netto'], 2 ), 0, 0, 'R' );
			$this->Cell( 20, $hoogte, number_format_i18n( $omzetdetail['btw'], 2 ), 0, 0, 'R' );
			$this->ln();
		}
	}

	/**
	 * Maak het rapport aan.
	 *
	 * @param int $maand De maand van het rapport.
	 * @param int $jaar  De jaar van het rapport.
	 * @return string Pad naar het rapport.
	 */
	public function run( int $maand, int $jaar ) : string {
		$upload_dir = wp_get_upload_dir();
		$rapportage = new Orderrapportage();
		$file       = sprintf( 'omzet_%d-%d-%s.pdf', $jaar, $maand, uniqid() );
		$this->SetCreator( get_site_url() );
		$this->SetAuthor( 'Kleistad' );
		$this->SetTitle( 'Omzet rapport' );
		$this->start( 'periode ' . strftime( '%B', mktime( 0, 0, 0, $maand, 1, 2020 ) ) . " $jaar" );
		$omzet = $rapportage->maandrapport( $maand, $jaar );
		$this->tabel( $omzet );
		foreach ( $omzet as $naam => $omzetregel ) {
			if ( 0 !== $omzetregel['netto'] ) {
				$this->addPage();
				$omzetdetails = $rapportage->maanddetails( $maand, $jaar, $omzetregel['key'] );
				$this->details( $naam, $omzetdetails );
			}
		}
		$this->Output( 'F', $upload_dir['basedir'] . '/' . $file );
		return $upload_dir['baseurl'] . '/' . $file;
	}
}
