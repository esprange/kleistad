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

/**
 * De class maakt gebruik van de fdpf class, zie ook http://www.fpdf.org.
 */
class OmzetRapport extends PDF {

	/**
	 * Toon de omzet tabel.
	 *
	 * @param array $omzet De omzet.
	 */
	private function tabel( array $omzet ) {
		$this->SetY( 75 );
		$hoogte = 8;
		$this->SetFont( self::CSET, 'B', self::NORMAAL );
		$this->Cell( 50, $hoogte, 'Omzet', 'B', 0, 'L' );
		$this->Cell( 50, $hoogte, 'Netto', 'B', 0, 'R' );
		$this->Cell( 50, $hoogte, 'BTW', 'B', 0, 'R' );
		$this->Ln();
		$this->SetFont( self::CSET );
		$totaal_netto = 0;
		$totaal_btw   = 0;
		foreach ( $omzet as $naam => $omzetregel ) {
			if ( 0.0 !== $omzetregel['netto'] ) {
				$totaal_netto += $omzetregel['netto'];
				$totaal_btw   += $omzetregel['btw'];
				$this->Cell( 50, $hoogte, $naam, 0, 0, 'L' );
				$this->Cell( 50, $hoogte, number_format_i18n( $omzetregel['netto'], 2 ), 0, 0, 'R' );
				$this->Cell( 50, $hoogte, number_format_i18n( $omzetregel['btw'], 2 ), 0, 0, 'R' );
				$this->Ln();
			}
		}
		$this->SetFont( self::CSET, 'B', self::NORMAAL );
		$this->Cell( 50, $hoogte, 'Totaal', 'T', 0, 'L' );
		$this->Cell( 50, $hoogte, number_format_i18n( $totaal_netto, 2 ), 'T', 0, 'R' );
		$this->Cell( 50, $hoogte, number_format_i18n( $totaal_btw, 2 ), 'T', 0, 'R' );
	}

	/**
	 * Toon de omzet details.
	 *
	 * @param string $naam         De omzet naam.
	 * @param array  $omzetdetails De details.
	 */
	private function details( string $naam, array $omzetdetails ) {
		$this->SetY( 30 );
		$this->SetFont( self::CSET, 'B', self::GROOT );
		$this->Cell( 0, 24, $naam );
		$this->SetY( 50 );
		$hoogte = 8;
		$this->SetFont( self::CSET, 'B', self::NORMAAL );
		$this->Cell( 40, $hoogte, 'Code', 'B', 0, 'L' );
		$this->Cell( 50, $hoogte, 'Klant', 'B', 0, 'L' );
		$this->Cell( 20, $hoogte, 'Datum', 'B', 0, 'L' );
		$this->Cell( 20, $hoogte, 'Bedrag', 'B', 0, 'R' );
		$this->Cell( 20, $hoogte, 'BTW', 'B', 0, 'R' );
		$this->Ln();
		$this->SetFont( self::CSET );
		foreach ( $omzetdetails as $omzetdetail ) {
			$this->Cell( 40, $hoogte, substr( $omzetdetail['code'], 0, 20 ), 0, 0, 'L' );
			$this->Cell( 50, $hoogte, utf8_decode( substr( $omzetdetail['klant'], 0, 25 ) ), 0, 0, 'L' );
			$this->Cell( 20, $hoogte, wp_date( 'd-m-Y', $omzetdetail['datum'] ), 0, 0, 'L' );
			$this->Cell( 20, $hoogte, number_format_i18n( $omzetdetail['netto'], 2 ), 0, 0, 'R' );
			$this->Cell( 20, $hoogte, number_format_i18n( $omzetdetail['btw'], 2 ), 0, 0, 'R' );
			$this->Ln();
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
		$this->init( $file, 'Omzet rapport periode ' . wp_date( 'F', mktime( 0, 0, 0, $maand, 1, 2020 ) ) . " $jaar" );
		$omzet = $rapportage->maandrapport( $maand, $jaar );
		$this->tabel( $omzet );
		foreach ( $omzet as $naam => $omzetregel ) {
			if ( 0.0 !== $omzetregel['netto'] ) {
				$this->addPage();
				$omzetdetails = $rapportage->maanddetails( $maand, $jaar, $omzetregel['key'] );
				$this->details( $naam, $omzetdetails );
			}
		}
		$this->Output( 'F', $upload_dir['basedir'] . '/' . $file );
		return $upload_dir['baseurl'] . '/' . $file;
	}
}
