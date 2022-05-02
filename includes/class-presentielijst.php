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

/**
 * De class voor email, maakt gebruik van de fdpf class, zie ook http://www.fpdf.org.
 */
class Presentielijst extends PDF {

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
		$this->SetFont( self::CSET, 'B', self::NORMAAL );
		$this->Cell( 50, $fontheight, 'Cursist', 1, 0, 'L' );
		sort( $lesdatums );
		foreach ( $lesdatums as $lesdatum ) {
			$this->Cell( 12, $fontheight, wp_date( 'd-m', $lesdatum ), 1, 0, 'C' );
		}
		$this->setFont( self::CSET );
		$this->Ln();
		foreach ( $cursisten as $cursist ) {
			$this->Cell( 50, $fontheight, utf8_decode( $cursist ), 1, 0, 'L' );
			$columns = count( $lesdatums );
			while ( 0 < $columns-- ) {
				$this->Cell( 12, $fontheight, '', 1, 0, 'C' );
			}
			$this->Ln();
		}
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
		$this->init( $file, $this->trunc( "Presentielijst $cursus->code $cursus->naam", 60 ) );
		$this->matrix( $cursisten, $cursus->lesdatums );
		$this->Output( 'F', $upload_dir['basedir'] . '/' . $file );
		return $upload_dir['baseurl'] . '/' . $file;
	}

}
