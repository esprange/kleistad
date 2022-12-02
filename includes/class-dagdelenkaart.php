<?php
/**
 * Definieer de dagdelenkaart class
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad dagdelenkaart.
 *
 * @property string code
 * @property int    datum
 * @property bool   geannuleerd
 * @property int    start_datum
 * @property int    eind_datum
 * @property string opmerking
 */
class Dagdelenkaart extends Artikel {

	public const DEFINITIE  = [
		'prefix'       => 'K',
		'naam'         => 'dagdelenkaart',
		'pcount'       => 1,
		'annuleerbaar' => true,
	];
	public const KAART_DUUR = 3;
	public const META_KEY   = 'kleistad_dagdelenkaart_v2';

	/**
	 * De beginwaarden van een dagdelenkaart.
	 *
	 * @access private
	 * @var array $default_data de standaard waarden bij het aanmaken van een dagdelenkaart.
	 */
	private array $default_data = [
		'code'        => '',
		'datum'       => 0,
		'start_datum' => 0,
		'geannuleerd' => false,
		'opmerking'   => '',
	];

	/**
	 * Het volgnummer van de dagdelenkaart.
	 *
	 * @access private
	 * @var int $volgnr Het volgnummer.
	 */
	private int $volgnr;

	/**
	 * Constructor
	 *
	 * @param int $klant_id wp id van de gebruiker.
	 */
	public function __construct( int $klant_id ) {
		$this->klant_id  = $klant_id;
		$dagdelenkaarten = get_user_meta( $this->klant_id, self::META_KEY, true ) ?: $this->default_data;
		$this->volgnr    = count( /* @scrutinizer ignore-type */ $dagdelenkaarten );
		$this->data      = wp_parse_args( end( /* @scrutinizer ignore-type */ $dagdelenkaarten ), $this->default_data );
		$this->betaling  = new DagdelenkaartBetaling( $this );
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) {
		if ( 'eind_datum' === $attribuut ) {
			return strtotime( self::KAART_DUUR . ' month', $this->data['start_datum'] );
		}
		return array_key_exists( $attribuut, $this->data ) ? $this->data[ $attribuut ] : null;
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( string $attribuut, mixed $waarde ) {
		$this->data[ $attribuut ] = $waarde;
	}

	/**
	 * Verwijder de dagdelenkaart, niet alleen de laatste maar ook alle voorgaande.
	 *
	 * @param bool $alle Moeten alle dagdelenkaarten verwijderd worden of alleen de huidige.
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag )
	 */
	public function erase( bool $alle = true ) {
		if ( ! $alle ) {
			$dagdelenkaarten = get_user_meta( $this->klant_id, self::META_KEY, true );
			if ( is_array( $dagdelenkaarten ) ) {
				unset( $dagdelenkaarten[ $this->volgnr ] );
				if ( count( $dagdelenkaarten ) ) {
					update_user_meta( $this->klant_id, self::META_KEY, $dagdelenkaarten );
					return;
				}
			}
		}
		delete_user_meta( $this->klant_id, self::META_KEY );
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
	 * Verzenden van de welkomst email.
	 *
	 * @param string $type    Welke email er verstuurd moet worden.
	 * @param string $factuur Bij te sluiten factuur.
	 * @return bool succes of falen van verzending email.
	 */
	public function verzend_email( string $type, string $factuur = '' ) : bool {
		$emailer   = new Email();
		$gebruiker = get_userdata( $this->klant_id );
		return $emailer->send(
			[
				'to'          => "$gebruiker->display_name <$gebruiker->user_email>",
				'subject'     => 'Welkom bij Kleistad',
				'slug'        => "dagdelenkaart$type",
				'attachments' => $factuur ?: [],
				'parameters'  => [
					'voornaam'                => $gebruiker->first_name,
					'achternaam'              => $gebruiker->last_name,
					'start_datum'             => wp_date( 'd-m-Y', $this->start_datum ),
					'dagdelenkaart_code'      => $this->code,
					'dagdelenkaart_opmerking' => empty( $this->opmerking ) ? '' : "De volgende opmerking heb je doorgegeven: $this->opmerking",
					'dagdelenkaart_prijs'     => number_format_i18n( opties()['dagdelenkaart'], 2 ),
					'dagdelenkaart_link'      => $this->get_betaal_link(),
				],
			]
		);
	}

	/**
	 * Geef de verval datum
	 *
	 * @return int
	 */
	public function get_verval_datum(): int {
		return $this->start_datum;
	}

	/**
	 * Geef de factuur regels.
	 *
	 * @return Orderregels De regels.
	 */
	public function get_factuurregels() : Orderregels {
		$orderregels = new Orderregels();
		$orderregels->toevoegen( new Orderregel( 'dagdelenkaart, start datum ' . wp_date( 'd-m-Y', $this->start_datum ), 1, opties()['dagdelenkaart'] ) );
		return $orderregels;
	}

	/**
	 * Bewaar de dagdelenkaart als metadata in de database.
	 */
	public function save() {
		$dagdelenkaarten                  = get_user_meta( $this->klant_id, self::META_KEY, true ) ?: [];
		$dagdelenkaarten[ $this->volgnr ] = $this->data;
		update_user_meta( $this->klant_id, self::META_KEY, $dagdelenkaarten );
	}

	/**
	 * Voeg een nieuwe dagdelenkaart toe.
	 *
	 * @param int    $start_datum De datum waarop de kaart in gaat.
	 * @param string $opmerking Een eventuele opmerking.
	 */
	public function nieuw( int $start_datum, string $opmerking ) {
		$this->volgnr++;
		$this->datum       = strtotime( 'today' );
		$this->start_datum = $start_datum;
		$this->opmerking   = $opmerking;
		$datum             = wp_date( 'ymd', $this->datum );
		$this->code        = "K$this->klant_id-$datum-$this->volgnr";
		$this->save();
	}

	/**
	 * Geef de status van het abonnement als een tekst terug.
	 *
	 * @param  bool $uitgebreid Uitgebreide tekst of korte tekst.
	 * @return string De status tekst.
	 */
	public function get_statustekst( bool $uitgebreid ) : string {
		$vandaag = strtotime( 'today' );
		if ( $this->start_datum > $vandaag ) {
			return $uitgebreid ? 'gaat starten per ' . wp_date( 'd-m-Y', $this->start_datum ) : 'nieuw';
		} elseif ( strtotime( self::KAART_DUUR . ' month', $this->start_datum ) <= $vandaag ) {
			return $uitgebreid ? 'actief tot ' . wp_date( 'd-m-Y', strtotime( self::KAART_DUUR . ' month', $this->start_datum ) ) : 'actief';
		}
		return $uitgebreid ? 'voltooid per ' . wp_date( 'd-m-Y', strtotime( self::KAART_DUUR . ' month', $this->start_datum ) ) : 'voltooid';
	}

}
