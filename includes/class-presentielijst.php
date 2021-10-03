<?php
/**
 * De  class voor het aanmaken van een presentielijst.
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
class Presentielijst extends FPDF {

	/**
	 * Start de pagina.
	 *
	 * @param string $titel De titel van de pagina.
	 */
	private function start( string $titel ) {
		$this->SetLeftMargin( 25 );
		$this->AddPage();
		$this->setFont( 'Arial', 'B', 24 );
		$this->Cell( 0, 32, 'Presentielijst' );
		$this->ln( 20 );
		$this->setFont( 'Arial', 'B', 12 );
		$this->Cell( 0, 16, $titel );
		$this->setXY( 240, 10 );
		$this->Image( plugin_dir_path( dirname( __FILE__ ) ) . 'public/images/logo kleistad-email.jpg' );
	}

	/**
	 * Toon de matrix van cursisten en cursus datums.
	 *
	 * @param array $cursisten De namen van de cursisten.
	 * @param array $lesdatums De lesdatums.
	 */
	private function matrix( array $cursisten, array $lesdatums ) {
		$this->SetY( 45 );
		$this->SetLeftMargin( 25 );
		$fontheight = 8;
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 50, $fontheight, 'Cursist', 1, 0, 'L' );
		$this->setFont( 'Arial', 'B', 10 );
		sort( $lesdatums );
		foreach ( $lesdatums as $lesdatum ) {
			$this->Cell( 12, $fontheight, strftime( '%d-%m', $lesdatum ), 1, 0, 'C' );
		}
		$this->setFont( 'Arial' );
		$this->ln();
		foreach ( $cursisten as $cursist ) {
			$this->Cell( 50, $fontheight, utf8_decode( $cursist ), 1, 0, 'L' );
			$columns = count( $lesdatums );
			while ( 0 < $columns-- ) {
				$this->Cell( 12, $fontheight, '', 1, 0, 'C' );
			}
			$this->ln();
		}
		$this->ln();
		$this->Cell( 0, $fontheight, strftime( '%d-%m-%Y' ) );
	}

	/**
	 * Maak de presentielijst aan.
	 *
	 * @param Cursus $cursus    De cursus waarvoor een presentielijst moet worden aangemaakt.
	 * @param array  $cursisten De namen van de cursisten.
	 * @return string Pad naar de presentielijst.
	 */
	public function run( Cursus $cursus, array $cursisten ) : string {
		$upload_dir = wp_get_upload_dir();
		$file       = sprintf( '%s-%s.pdf', $cursus->code, uniqid() );
		$this->SetCreator( get_site_url() );
		$this->SetAuthor( 'Kleistad' );
		$this->SetTitle( "Presentielijst $cursus->code $cursus->naam" );
		$this->start( "$cursus->code $cursus->naam" );
		$this->matrix( $cursisten, $cursus->lesdatums );
		$this->Output( 'F', $upload_dir['basedir'] . '/' . $file );
		return $upload_dir['baseurl'] . '/' . $file;
	}

}
