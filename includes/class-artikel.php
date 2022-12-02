<?php
/**
 * De definitie van de artikel class
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Artikel class.
 *
 * @since 6.1.0
 */
abstract class Artikel {

	public const DEFINITIE = [
		'prefix'       => '',
		'naam'         => '',
		'pcount'       => 0,
		'annuleerbaar' => false,
	];

	/**
	 * De artikel data
	 *
	 * @access protected
	 * @var array $data welke de attributen van het artikel bevat.
	 */
	protected array $data = [];

	/**
	 * De klant.
	 *
	 * @var int $klant_id
	 */
	public int $klant_id = 0;

	/**
	 * Bij artikelen kan aangegeven worden welk type order afgehandeld moet worden.
	 *
	 * @var string $artikel_type Bijvoorbeeld bij abonnementen het type start, overbrugging of regulier.
	 */
	public string $artikel_type = '';

	/**
	 * Het Betaling object
	 *
	 * @var object $betaling De betaling acties.
	 */
	public object $betaling;

	/**
	 * Geef de referentie van het artikel
	 *
	 * @return string De referentie.
	 */
	abstract public function get_referentie(): string;

	/**
	 * Bestelling
	 *
	 * @return Orderregels Een array van orderregels of maar één regel.
	 */
	abstract public function get_factuurregels(): Orderregels;

	/**
	 * Geef de naam van het artikel, kan nader ingevuld worden.
	 *
	 * @return string
	 */
	public function get_artikelnaam(): string {
		return static::DEFINITIE['naam'];
	}

	/**
	 * Geef de vervaldatum dat het artikel moet zijn betaald, kan nader ingevuld worden.
	 *
	 * @return int
	 */
	public function get_verval_datum() : int {
		return strtotime( '+ 14 days 00:00' );
	}

	/**
	 * Klant gegevens voor op de factuur, kan eventueel aangepast worden zoals bijvoorbeeld voor de contact van een workshop.
	 *
	 * @return array De naw gegevens.
	 */
	public function get_naw_klant() : array {
		$klant = get_userdata( $this->klant_id );

		/**
		 * De adres elementen zijn onderdeel gemaakt van het object.
		 *
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		return [
			'naam'  => "$klant->first_name  $klant->last_name",
			'adres' => "$klant->straat $klant->huisnr\n$klant->pcode $klant->plaats",
			'email' => "$klant->display_name <$klant->user_email>",
		];
	}

	/**
	 * Maak een controle string aan.
	 *
	 * @return string Hash string.
	 * @since  6.1.0
	 */
	public function get_controle() : string {
		return hash( 'sha256', sprintf( KLEISTAD_CONTROLE, strtok( $this->get_referentie(), '-' ) ) );
	}

	/**
	 * De link die in een email als parameter meegegeven kan worden.
	 *
	 * @param array  $args       Een array met parameters.
	 * @param string $pagina     De pagina waar geland moet worden.
	 * @param string $verwijzing De tekst in de link.
	 *
	 * @return string De html link.
	 */
	public function get_link( array $args, string $pagina, string $verwijzing = 'Kleistad pagina' ) : string {
		$url = add_query_arg( array_merge( $args, [ 'hsh' => $this->get_controle() ] ), home_url( "/kleistad-$pagina" ) );
		return "<a href=\"$url\" target=\"_blank\" >$verwijzing</a>";
	}

	/**
	 * Maak een betaal link.
	 *
	 * @return string De link.
	 */
	public function get_betaal_link() : string {
		return $this->get_link( [ 'order' => $this->get_referentie() ], 'betaling' );
	}

}
