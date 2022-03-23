<?php
/**
 * De definitie van de los artikel class
 *
 * @link       https://www.kleistad.nl
 * @since      6.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Klasse voor het beheren van losse artikelen verkoop.
 *
 * @property string      code
 * @property Orderregels orderregels
 * @property array       klant
 * @property float       prijs
 */
class LosArtikel extends Artikel {

	public const DEFINITIE = [
		'prefix'       => 'X',
		'naam'         => 'overige verkoop',
		'pcount'       => 1,
		'annuleerbaar' => false,
	];
	private const META     = 'kleistad_losartikel_';

	/**
	 * De constructor
	 *
	 * @since      6.2.0
	 *
	 * @param string|int $verkoop_id Een uniek id van de verkoop.
	 */
	public function __construct( int|string $verkoop_id = 0 ) {
		$this->betaling = new LosArtikelBetaling( $this );
		if ( is_string( $verkoop_id ) ) {
			$verkoop_id = sscanf( $verkoop_id, 'X%d' );
		}
		if ( $verkoop_id ) {
			$this->code = "X$verkoop_id";
			$result     = get_transient( self::META . $this->code );
			if ( $result ) {
				$this->data = $result;
				return;
			}
		}
		$verkoop_id = intval( get_option( 'kleistad_losnr', 0 ) );
		update_option( 'kleistad_losnr', ++$verkoop_id );
		$this->code = "X$verkoop_id";
		$this->data = [
			'klant'       => [
				'naam'  => '',
				'adres' => '',
				'email' => '',
			],
			'prijs'       => 0.0,
			'code'        => $this->code,
			'orderregels' => new Orderregels(),
		];
	}

	/**
	 * Getter magic functie
	 *
	 * @since      6.2.0
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt opgevraagd.
	 * @return mixed De waarde.
	 */
	public function __get( string $attribuut ) {
		return $this->data[ $attribuut ];
	}

	/**
	 * Setter magic functie
	 *
	 * @since      6.2.0
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt aangepast.
	 * @param mixed  $waarde De nieuwe waarde.
	 */
	public function __set( string $attribuut, mixed $waarde ) {
		$this->data[ $attribuut ] = is_string( $waarde ) ? trim( $waarde ) : $waarde;
	}

	/**
	 * Geef de code terug.
	 *
	 * @return string
	 */
	public function get_referentie() : string {
		return $this->code;
	}

	/**
	 * Klant gegevens voor op de factuur.
	 *
	 * @return array De naw gegevens.
	 */
	public function naw_klant() : array {
		return $this->klant;
	}

	/**
	 * Verzenden van de verkoop email.
	 *
	 * @since      6.2.0
	 *
	 * @param string $type    Direct betaald of melding van storting.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return bool succes of falen van verzending email.
	 */
	public function verzend_email( string $type = '', string $factuur = '' ) : bool {
		$emailer = new Email();
		return $emailer->send(
			[
				'to'          => "{$this->klant['naam']} <{$this->klant['email']}>",
				'subject'     => 'Bestelling Kleistad op ' . date( 'd-m-Y' ),
				'slug'        => 'bestelling' . $type,
				'attachments' => $factuur ?: [],
				'parameters'  => [
					'naam'        => $this->klant['naam'],
					'bedrag'      => number_format_i18n( $this->prijs, 2 ),
					'bestel_link' => $this->maak_betaal_link(),
				],
			]
		);
	}

	/**
	 * Voeg een bestelregel toe.
	 *
	 * @param string $artikel De artikelnaam.
	 * @param float  $aantal  Het aantal artikelen.
	 * @param float  $prijs   De bruto prijs per artikel.
	 */
	public function bestelregel( string $artikel, float $aantal, float $prijs ) {
		$this->orderregels->toevoegen( new Orderregel( $artikel, $aantal, $prijs ) );
		$this->prijs += $aantal * $prijs;
	}

	/**
	 * De factuur regels.
	 *
	 * @return Orderregels
	 */
	public function get_factuurregels() : Orderregels {
		return $this->orderregels;
	}

	/**
	 * Bewaar het artikel.
	 *
	 * @return void
	 */
	public function save() {
		set_transient( self::META . $this->code, $this->data, YEAR_IN_SECONDS );
	}

}
