<?php
/**
 * De  class voor het maken van pdf's.
 *
 * @link       https://www.kleistad.nl
 * @since      7.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use FPDF;

/**
 * De class voor het aanmaken van een pdf, zie ook http://www.fpdf.org.
 */
class PDF extends FPDF {

	protected const NORMAAL   = 10;
	protected const KLEIN     = 8;
	protected const GROOT     = 20;
	protected const HALFGROOT = 17;
	protected const H_NORMAAL = 6;
	protected const H_KLEIN   = 5;
	protected const CSET      = 'Arial';

	/**
	 * De bestandsnaam
	 *
	 * @var string $footer De bestandsnaam.
	 */
	private string $footer;

	/**
	 * Titel
	 *
	 * @var string $titel De titel van de pdf.
	 */
	private string $titel;

	/**
	 * Omdat de constructor niet gewijzigd kan worden, op deze manier de parameters doorgeven.
	 *
	 * @param string $filenaam De bestandsnaam.
	 * @param string $titel    Titel vvan het bestand.
	 *
	 * @return void
	 */
	public function init( string $filenaam, string $titel ) : void {
		$this->footer = "Pagina %d \n" . sprintf( 'bestand %s aangemaakt op %s', $filenaam, wp_date( 'd-m-Y, H:i' ) );
		$this->titel  = $titel;
		$this->SetCreator( get_site_url() );
		$this->SetAuthor( 'Kleistad' );
		$this->SetTitle( $this->titel );
		$this->AddPage();
		$this->SetLeftMargin( 25 );
		$this->SetFont( self::CSET );
	}

	/**
	 * Algemene Header, voor alle output
	 *
	 * @return void
	 */
	public function Header() : void {
		$this->SetFont( self::CSET, 'B', 25 > strlen( $this->titel ) ? self::GROOT : self::HALFGROOT );
		$this->Text( 25, 30, $this->titel );
		$this->SetXY( -60, 15 ); // Relatief t.o.v. rechterkant, voor portrait en landscape modus.
		$this->Image( plugin_dir_path( dirname( __FILE__ ) ) . 'public/images/logo kleistad-email.jpg' );
	}

	/**
	 * Algemene footer, voor alle output.
	 *
	 * @return void
	 */
	public function Footer() : void {
		$this->SetY( -25 );
		$this->SetFont( self::CSET, 'I', self::KLEIN );
		$this->MultiCell( 0, self::H_KLEIN, sprintf( $this->footer, $this->PageNo() ), 0, 'C' );
	}

	/**
	 * Output het getal als een euro bedrag.
	 *
	 * @param float $bedrag Het bedrag.
	 * @return string;
	 */
	protected function euro( float $bedrag ) : string {
		return chr( 128 ) . ' ' . number_format_i18n( $bedrag, 2 );
	}

	/**
	 * Kort een tekst af als deze te lang is voor een veld.
	 *
	 * @param string $tekst  De tekst.
	 * @param int    $maxlen De maximale lengte van de tekst.
	 * @return string
	 */
	protected function trunc( string $tekst, int $maxlen ) : string {
		$tekst = trim( $tekst );
		if ( strlen( $tekst ) <= $maxlen ) {
			return $tekst;
		}
		$_tekst  = strrev( substr( $tekst, 0, $maxlen - 1 ) );
		$matches = [];
		preg_match( '/\s/', $_tekst, $matches );
		$_tekst = strrev( substr( $_tekst, strpos( $_tekst, $matches[0] ) ) );
		return $_tekst . '...';
	}
}
