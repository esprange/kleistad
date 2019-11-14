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
 * De class voor email, maakt gebruik van de fdpf class, zie ook http://www.fpdf.org.
 */
class Factuur extends \FPDF {

	/**
	 * Output het getal als een euro bedrag.
	 *
	 * @param float $bedrag Het bedrag.
	 */
	private function euro( $bedrag ) {
		return chr( 128 ) . number_format_i18n( $bedrag, 2 );
	}

	/**
	 * Start de pagina.
	 *
	 * @param string $titel De titel van de pagina.
	 */
	private function start( $titel ) {
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
	private function klant( $args ) {
		$h = 6;
		$this->setY( 65 );
		$this->SetLeftMargin( 25 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 0, $h, $args['naam'], 0, 1 );
		$this->setFont( 'Arial', '', 10 );
		$this->MultiCell( 0, $h, $args['adres'] );
	}

	/**
	 * Toon het info veld over factuurnr, datum en Kleistad info.
	 *
	 * @param string $factuurnr  Het nummer van de factuur.
	 * @param string $referentie De referentie.
	 */
	private function info( $factuurnr, $referentie ) {
		$h = 6;
		$this->setY( 65 );
		$this->SetRightMargin( 75 );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 0, $h, 'Factuurdatum', 0, 1, 'R' );
		$this->setFont( 'Arial' );
		$this->Cell( 0, $h, strftime( '%d-%m-%y' ), 0, 1, 'R' );
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
	 * @param array $regels  De factuur regels behorende bij de bestelling.
	 * @param float $betaald Wat er al betaald is.
	 */
	private function order( $regels, $betaald ) {
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
		$totaal = 0.0;
		$btw    = 0.0;
		foreach ( $regels as $regel ) {
			$prijs   = $regel['aantal'] * $regel['prijs'];
			$totaal += $prijs;
			$btw    += $regel['aantal'] * $regel['btw'];
			$this->Cell( $w['aantal'], $h, $regel['aantal'], 0, 0, 'C' );
			$this->Cell( $w['artikel'], $h, $regel['artikel'], 0, 0, 'L' );
			$this->Cell( $w['stuksprijs'], $h, $this->euro( $regel['prijs'] ), 0, 0, 'R' );
			$this->Cell( $w['prijs'], $h, $this->euro( $prijs ), 0, 1, 'R' );
		}
		$this->Ln( $h * 2 );
		$this->Cell( $w['volledig'], 0, '', 'T', 1 );
		$this->Cell( $w['samenvatting'], $h, 'Totaal exclusief BTW', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $totaal ), 0, 1, 'R' );
		$this->Cell( $w['samenvatting'], $h, 'BTW 21%', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $btw ), 'B', 1, 'R' );
		$this->Cell( $w['samenvatting'], $h, 'Totaal', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $totaal + $btw ), 'B', 1, 'R' );
		$this->Cell( $w['samenvatting'], $h, 'Reeds betaald ', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $betaald ), 'B', 1, 'R' );
		$this->setFont( 'Arial', 'B' );
		$verschil = $totaal + $btw - $betaald;
		if ( $verschil >= 0 ) {
			$verschuldigd = $verschil;
			$this->Cell( $w['samenvatting'], $h, 'Verschuldigd saldo', 0, 0, 'R' );
			$this->Cell( $w['prijs'], $h, $this->euro( $verschuldigd ), 0, 1, 'R' );
		} elseif ( $verschil < 0 ) {
			$ontvangen = $verschil;
			$this->Cell( $w['samenvatting'], $h, 'Te ontvangen', 0, 0, 'R' );
			$this->Cell( $w['prijs'], $h, $this->euro( $ontvangen ), 0, 1, 'R' );
		}
	}

	/**
	 * Toon het opmerkingen veld.
	 *
	 * @param string $arg De te tonen tekst.
	 */
	private function opmerking( $arg ) {
		$h = 6;
		$this->SetLeftMargin( 25 );
		$this->Ln( 2 * $h );
		$this->setFont( 'Arial', 'B', 10 );
		$this->Cell( 0, $h, 'Opmerkingen', 0, 1 );
		$this->setFont( 'Arial' );
		$this->MultiCell( 0, $h, $arg );
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
