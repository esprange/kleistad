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

define( 'EURO', chr( 128 ) );

/**
 * De class voor email, maakt gebruik van de fdpf class, zie ook http://www.fpdf.org.
 */
class Factuur extends \FPDF {

	/**
	 * Start de pagina.
	 *
	 * @param string $titel De titel van de pagina.
	 */
	private function start( $titel ) {
		$w = 100;
		$h = 32;
		$this->AddPage();
		$this->setFont( 'Arial', 'B', 24 );
		$this->Cell( $w, $h, $titel );
		$this->setX( 150 );
		$this->Image( plugin_dir_path( dirname( __FILE__ ) ) . 'public/images/logo kleistad-email.jpg' );
	}

	/**
	 * Toon de klant informatie
	 *
	 * @param array $args De te tonen informatie.
	 */
	private function klant( $args ) {
		$w = 100;
		$h = 6;
		$this->setY( 70 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $w, $h, $args['naam'], 0, 1 );
		$this->setFont( 'Arial', '', 10 );
		$this->MultiCell( $w, $h, $args['adres'] );
	}

	/**
	 * Toon het info veld over factuurnr, datum en Kleistad info.
	 *
	 * @param string $factuurnr  Het nummer van de factuur.
	 * @param string $referentie De referentie.
	 */
	private function info( $factuurnr, $referentie ) {
		$w = 130;
		$h = 6;
		$this->setY( 70 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $w, $h, 'Factuurdatum', 0, 1, 'R' );
		$this->setFont( 'Arial', '', 10 );
		$this->Cell( $w, $h, strftime( '%d-%m-%y' ), 0, 1, 'R' );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $w, $h, 'Factuurnummer', 0, 1, 'R' );
		$this->setFont( 'Arial', '', 10 );
		$this->Cell( $w, $h, $factuurnr, 0, 1, 'R' );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $w, $h, 'Referentie', 0, 1, 'R' );
		$this->setFont( 'Arial', '', 10 );
		$this->Cell( $w, $h, $referentie, 0, 0, 'R' );
		$this->Line( 143, 70, 143, 70 + 7 * $h );

		$w = 100;
		$this->setXY( 145, 70 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $w, $h, 'Stichting Kleistad', 0, 2 );
		$this->setFont( 'Arial', '', 10 );
		$this->MultiCell( $w, $h, "Neonweg 12\n3812 RH Amersfoort\n\nKvK 68731248\nBTW nr NL857567044B01\nIBAN NL10 RABO 0191913308" );
	}

	/**
	 * Toon de bestelling.
	 *
	 * @param array $regels  De factuur regels behorende bij de bestelling.
	 * @param float $betaald Wat er al betaald is.
	 */
	private function order( $regels, $betaald ) {
		$this->SetY( 130 );
		$w = [ 110, 30, 30 ];
		$h = 6;
		$this->Ln();
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $w[0], $h, 'Omschrijving', 1, 0, 'C' );
		$this->Cell( $w[1], $h, 'Aantal', 1, 0, 'C' );
		$this->Cell( $w[2], $h, 'Bedrag', 1, 0, 'C' );
		$this->Ln();
		$this->setFont( 'Arial', '', 10 );
		$totaal = 0.0;
		foreach ( $regels as $regel ) {
			$prijs   = $regel['aantal'] * $regel['prijs'];
			$totaal += $prijs;
			$this->Cell( $w[0], $h, $regel['artikel'], 'LR', 0, 'L' );
			$this->Cell( $w[1], $h, $regel['aantal'], 'LR', 0, 'R' );
			$this->Cell( $w[2], $h, EURO . ' ' . number_format_i18n( $prijs, 2 ), 'LR', 0, 'R' );
			$this->Ln();
		}
		$this->Cell( array_sum( $w ), 0, '', 'T' );
		$this->Ln();
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $w[0] + $w[1], $h, 'Totaal', 0, 0, 'R' );
		$this->setFont( 'Arial', '', 10 );
		$this->Cell( $w[2], $h, EURO . ' ' . number_format_i18n( $totaal, 2 ), 1, 0, 'R' );
		$this->Ln();
		$this->Cell( $w[0] + $w[1], $h, 'Inclusief BTW 21%', 0, 0, 'R' );
		$this->Cell( $w[2], $h, EURO . ' ' . number_format_i18n( 21 / ( 100 + 21 ) * $totaal, 2 ), 1, 0, 'R' );
		$this->Ln();
		$this->Cell( $w[0] + $w[1], $h, 'Reeds betaald ', 0, 0, 'R' );
		$this->Cell( $w[2], $h, EURO . ' ' . number_format_i18n( $betaald, 2 ), 1, 0, 'R' );
		$this->Ln();
		$this->setFont( 'Arial', 'B', 10 );
		$verschil = $totaal - $betaald;
		if ( $verschil > 0 ) {
			$this->Cell( $w[0] + $w[1], $h, 'Verschuldigd saldo', 0, 0, 'R' );
			$this->Cell( $w[2], $h, EURO . ' ' . number_format_i18n( $verschil, 2 ), 1, 0, 'R' );
		} elseif ( $verschil < 0 ) {
			$this->Cell( $w[0] + $w[1], $h, 'Te ontvangen', 0, 0, 'R' );
			$this->Cell( $w[2], $h, EURO . ' ' . number_format_i18n( - $verschil, 2 ), 1, 0, 'R' );
		}
	}

	/**
	 * Toon het opmerkingen veld.
	 *
	 * @param string $arg De te tonen tekst.
	 */
	private function opmerking( $arg ) {
		$w = 130;
		$h = 6;
		$this->Ln();
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( $w, $h, 'Opmerkingen', 0, 1 );
		$this->setFont( 'Arial', '', 10 );
		$this->MultiCell( $w, $h, $arg );
	}

	/**
	 * Maak de factuur aan.
	 *
	 * @param string $factuurnr  Het factuur nummer.
	 * @param array  $klant      De klant.
	 * @param string $referentie De referentie.
	 * @param array  $regels     De factuur regels.
	 * @param float  $betaald    Wat er al betaald is.
	 * @param string $opmerking  De eventuele opmerking op de factuur.
	 * @param bool   $correctie  Of het een correctie factuur betreft.
	 * @param bool   $credit     Of het een credit factuur betreft.
	 * @return string Pad naar de factuur.
	 */
	public function run( $factuurnr, $klant, $referentie, $regels, $betaald, $opmerking, $correctie, $credit ) {
		$upload_dir  = wp_get_upload_dir();
		$factuur_dir = sprintf( '%s/facturen', $upload_dir['basedir'] );
		if ( ! is_dir( $factuur_dir ) ) {
			mkdir( $factuur_dir, 0644, true );
		}
		$file   = sprintf( '%s/%s-%s', $factuur_dir, $credit ? 'creditfactuur' : 'factuur', $factuurnr );
		$versie = '';
		if ( file_exists( "$file.pdf" ) ) {
			$versie = 0;
			do {
				$versie++;
			} while ( file_exists( "$file.$versie.pdf" ) );
			$file = "$file.$versie.pdf";
		} else {
			$file = "$file.pdf";
		}
		$this->SetCreator( get_site_url() );
		$this->SetAuthor( 'Kleistad' );
		$this->SetTitle( ( $credit ? 'Credit factuur ' : 'Factuur ' ) . $factuurnr . '.' . $versie );
		$this->start( $credit ? 'CREDIT FACTUUR' : ( $correctie ? 'CORRECTIE FACTUUR' : 'FACTUUR' ) );
		$this->klant( $klant );
		$this->info( $factuurnr, $referentie );
		$this->order( $regels, $betaald );
		$this->opmerking( $opmerking );
		$this->Output( 'F', $file );
		return $file;
	}

	/**
	 * Geef de facturen terug behorend bij factuurnr.
	 *
	 * @param string $factuurnr Het factuur nummer.
	 * @return array De url's van de facturen.
	 */
	public static function facturen( $factuurnr ) {
		$upload_dir = wp_get_upload_dir();
		$pattern    = sprintf( '%s/facturen/factuur-%s*', $upload_dir['basedir'], $factuurnr );
		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], glob( $pattern ) );
	}

}
