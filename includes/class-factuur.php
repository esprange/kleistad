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
		return chr( 128 ) . ' ' . number_format_i18n( $bedrag, 2 );
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
	 * @param int    $datum      De factuur datum.
	 * @param string $referentie De referentie.
	 */
	private function info( $factuurnr, $datum, $referentie ) {
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
	 * @param array $regels         De factuur regels behorende bij de bestelling.
	 * @param float $betaald        Wat er al betaald is.
	 * @param float $nog_te_betalen Wat er nog betaald moet worden ingeval van een credit_factuur.
	 */
	private function order( $regels, $betaald, $nog_te_betalen ) {
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
			$prijs   = $regel['aantal'] * ( $regel['prijs'] + $regel['btw'] );
			$totaal += $prijs;
			$btw    += $regel['aantal'] * $regel['btw'];
			$this->Cell( $w['aantal'], $h, $regel['aantal'], 0, 0, 'C' );
			$this->Cell( $w['artikel'], $h, $regel['artikel'], 0, 0, 'L' );
			$this->Cell( $w['stuksprijs'], $h, $this->euro( $regel['prijs'] + $regel['btw'] ), 0, 0, 'R' );
			$this->Cell( $w['prijs'], $h, $this->euro( $prijs ), 0, 1, 'R' );
		}
		$this->Ln( $h * 2 );
		$this->Cell( $w['volledig'], 0, '', 'T', 1 );
		$this->Cell( $w['samenvatting'], $h, 'Totaal', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $totaal ), 0, 1, 'R' );
		$this->Cell( $w['samenvatting'], $h, 'Inclusief BTW 21%', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $btw ), 'B', 1, 'R' );
		$this->Cell( $w['samenvatting'], $h, 'Reeds betaald ', 0, 0, 'R' );
		$this->Cell( $w['prijs'], $h, $this->euro( $betaald ), 'B', 1, 'R' );
		$this->setFont( 'Arial', 'B' );
		if ( 0 <= $nog_te_betalen ) {
			$this->Cell( $w['samenvatting'], $h, 'Verschuldigd saldo', 0, 0, 'R' );
			$this->Cell( $w['prijs'], $h, $this->euro( $nog_te_betalen ), 0, 1, 'R' );
		} else {
			$this->Cell( $w['samenvatting'], $h, 'Na verrekening te ontvangen', 0, 0, 'R' );
			$this->Cell( $w['prijs'], $h, $this->euro( - $nog_te_betalen ), 0, 1, 'R' );
		}
	}

	/**
	 * Toon het opmerkingen veld.
	 *
	 * @param string $arg De te tonen tekst.
	 */
	private function opmerking( $arg ) {
		if ( ! empty( $arg ) ) {
			$h = 6;
			$this->SetLeftMargin( 25 );
			$this->Ln( 2 * $h );
			$this->setFont( 'Arial', 'B', 10 );
			$this->Cell( 0, $h, 'Opmerkingen', 0, 1 );
			$this->setFont( 'Arial' );
			$this->MultiCell( 0, $h, $arg );
		}
	}

	/**
	 * Maak de factuur aan.
	 *
	 * @param \Kleistad\Order $order De order.
	 * @param string          $type  Het type factuur: gewoon, correctie of credit.
	 * @return string Pad naar de factuur.
	 */
	public function run( $order, $type ) {
		$factuurnr  = $order->factuurnr();
		$upload_dir = wp_get_upload_dir();
		$file       = sprintf( '%s/facturen/%s-%s', $upload_dir['basedir'], "{$type}factuur", $factuurnr );
		$versie     = '';
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
		$this->SetTitle( ucwords( $type ) . " Factuur $factuurnr.$versie" );
		$this->start( strtoupper( $type ) . ' FACTUUR' );
		$this->klant( $order->klant );
		$this->info( $factuurnr, $order->datum, $order->referentie );
		$this->order( $order->regels, $order->betaald, $order->te_betalen() );
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
	public static function facturen( $factuurnr ) {
		$upload_dir = wp_get_upload_dir();
		$pattern    = sprintf( '%s/facturen/factuur-%s*', $upload_dir['basedir'], $factuurnr );
		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], glob( $pattern ) );
	}

}
