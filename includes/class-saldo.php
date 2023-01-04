<?php
/**
 * De definitie van de (stook) saldo class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Klasse voor het beheren van de stook saldo.
 *
 * @property string code
 * @property float  bedrag
 * @property array  storting
 * @property float  prijs
 * @property bool   terugboeking actief
 */
class Saldo extends Artikel {

	public const DEFINITIE = [
		'prefix'       => 'S',
		'naam'         => 'stook en materialen',
		'pcount'       => 3,
		'annuleerbaar' => true,
	];
	public const META_KEY  = 'kleistad_stooksaldo';

	private const EMAIL_SUBJECT = [
		'_bank'          => 'Betaling saldo per bankstorting',
		'_ideal'         => 'Betaling saldo per ideal', // In dit geval wordt een factuur meegezonden.
		'_ideal_betaald' => 'Betaling saldo per ideal',
		'_terugboeking'  => 'Terugboeking restant saldo',
		'_negatief'      => 'Saldo tekort',
	];

	/**
	 * Het saldo bedrag
	 *
	 * @var float $bedrag Het saldo bedrag.
	 */
	public float $bedrag = 0.0;

	/**
	 * Registratie van de mutaties.
	 *
	 * @var SaldoMutaties Het register.
	 */
	public SaldoMutaties $mutaties;

	/**
	 * Indicatie voor restitutie
	 *
	 * @var bool False als er geen restitutie is.
	 */
	public bool $restitutie_actief = false;

	/**
	 * Het actie object
	 *
	 * @var SaldoActie $actie De saldo acties.
	 */
	public SaldoActie $actie;

	/**
	 * De referentie voor storting of restitutie
	 *
	 * @var string De referentie van de storting of restitutie order
	 */
	private string $referentie;

	/**
	 * De constructor
	 *
	 * @since      4.0.87
	 *
	 * @param int    $klant_id De gebruiker waarvoor het saldo wordt gemaakt.
	 * @param string $datum    De datum (ymd) waarop er een storting heeft plaatsgevonden.
	 * @param string $volgnr   Het volgnummer.
	 */
	public function __construct( int $klant_id, string $datum = '', string $volgnr = '' ) {
		$this->klant_id          = $klant_id;
		$this->actie             = new SaldoActie( $this );
		$this->betaling          = new SaldoBetaling( $this );
		$this->mutaties          = new SaldoMutaties();
		$saldo_data              = get_user_meta( $this->klant_id, self::META_KEY, true ) ?: [];
		$saldo_data              = wp_parse_args(
			$saldo_data,
			[
				'storting'   => [],
				'bedrag'     => 0.0,
				'restitutie' => false,
			]
		);
		$this->bedrag            = $saldo_data['bedrag'];
		$this->restitutie_actief = $saldo_data['restitutie'] ?? false;
		foreach ( $saldo_data['storting'] as $mutatie_data ) {
			$this->mutaties->toevoegen(
				new SaldoMutatie(
					$mutatie_data['code'],
					$mutatie_data['prijs'],
					$mutatie_data['status'] ?? '',
					$mutatie_data['gewicht'] ?? 0,
					strtotime( $mutatie_data['datum'] )
				)
			);
		}
		$this->referentie = ( $datum ) ?
			sprintf( '%s%d-%s-%d', self::DEFINITIE['prefix'], $klant_id, $datum, (int) $volgnr ) :
			sprintf( '%s%d-%s-%d', self::DEFINITIE['prefix'], $klant_id, date( 'ymd' ), count( $this->mutaties ) );
	}

	/**
	 * Update de status van de mutatie
	 *
	 * @param string $status     De status.
	 *
	 * @return void
	 */
	public function update_mutatie_status( string $status ) : void {
		foreach ( $this->mutaties as $mutatie ) {
			if ( str_contains( $mutatie->code, $this->referentie ) ) {
				$mutatie->status = $status;
			}
		}
	}

	/**
	 * Verwijder de mutatie (bijv. de betaling is niet gelukt).
	 *
	 * @return void
	 */
	public function remove_mutatie() : void {
		foreach ( $this->mutaties as $mutatie ) {
			if ( str_contains( $mutatie->code, $this->referentie ) ) {
				$mutatie->code = '';
				return;
			}
		}
	}

	/**
	 * Geef de code terug.
	 *
	 * @return string
	 */
	public function get_referentie() : string {
		return $this->referentie ?: $this->mutaties->end()->code;
	}

	/**
	 * Verzenden van de saldo verhoging email.
	 *
	 * @since      4.0.87
	 *
	 * @param string $type    Direct betaald of melding van storting.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return bool succes of falen van verzending email.
	 */
	public function verzend_email( string $type, string $factuur = '' ) : bool {
		$emailer   = new Email();
		$gebruiker = get_userdata( $this->klant_id );
		return $emailer->send(
			[
				'to'          => "$gebruiker->display_name <$gebruiker->user_email>",
				'subject'     => self::EMAIL_SUBJECT[ $type ],
				'slug'        => 'saldo' . $type,
				'attachments' => $factuur ?: [],
				'parameters'  => [
					'voornaam'   => $gebruiker->first_name,
					'achternaam' => $gebruiker->last_name,
					'bedrag'     => number_format_i18n( $this->mutaties->end()->bedrag, 2 ),
					'saldo'      => number_format_i18n( $this->bedrag, 2 ),
					'saldo_link' => $this->get_betaal_link(),
				],
			]
		);
	}

	/**
	 * Bewaar het aangepaste saldo
	 *
	 * @since      4.0.87
	 *
	 * @param string $reden Tekst voor de logging.
	 * @return bool True als saldo is aangepast.
	 */
	public function save( string $reden = '' ) : bool {
		$mutatie_data = [];
		foreach ( $this->mutaties as $mutatie ) {
			$mutatie_data[] = [
				'code'    => $mutatie->code,
				'prijs'   => round( $mutatie->bedrag, 2 ),
				'status'  => $mutatie->status,
				'gewicht' => $mutatie->gewicht,
				'datum'   => date( 'Y-m-d', $mutatie->datum ),
			];
		}
		$saldo_mutatie_data = [
			'bedrag'     => round( $this->bedrag, 2 ),
			'restitutie' => $this->restitutie_actief,
			'storting'   => $mutatie_data,
		];
		$saldo_data         = get_user_meta( $this->klant_id, self::META_KEY, true );
		if ( $saldo_data === $saldo_mutatie_data ) {
			return true;
		}
		$vorig_saldo = $saldo_data['bedrag'] ?? 0.0;
		if ( update_user_meta( $this->klant_id, self::META_KEY, $saldo_mutatie_data ) ) {
			if ( $vorig_saldo !== $this->bedrag ) {
				$tekst = get_userdata( $this->klant_id )->display_name . ' nu: ' . number_format_i18n( $vorig_saldo, 2 ) . ' naar: ' . number_format_i18n( $this->bedrag, 2 ) . ' vanwege ' . $reden;
				file_put_contents(  // phpcs:ignore
					wp_upload_dir()['basedir'] . '/stooksaldo.log',
					date( 'c' ) . " : $tekst\n",
					FILE_APPEND
				);
			}
			return true;
		}
		return false;
	}

	/**
	 * Geef de status van het artikel als een tekst terug.
	 *
	 * @return string De status tekst.
	 */
	public function get_statustekst() : string {
		return 0 < $this->bedrag ? 'saldo' : '';
	}

	/**
	 * Verwijder het saldo
	 */
	public function erase() {
		delete_user_meta( $this->klant_id, self::META_KEY );
	}

	/**
	 * De factuur regels.
	 *
	 * @return Orderregels
	 */
	public function get_factuurregels() : Orderregels {
		$orderregels = new Orderregels();
		$orderregels->toevoegen( new Orderregel( 'stook of materialen saldo', 1, $this->mutaties->end()->bedrag ) );
		return $orderregels;
	}

}
