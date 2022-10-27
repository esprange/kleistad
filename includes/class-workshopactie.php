<?php
/**
 * Definieer de workshop actie class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad WorkshopActie class.
 *
 * @since 6.14.7
 */
class WorkshopActie {

	public const GEREAGEERD = 'reactie';
	public const NIEUW      = 'nieuw';
	public const VRAAG      = 'vraag';

	/**
	 * Het workshop object
	 *
	 * @var Workshop $workshop De workshop.
	 */
	private Workshop $workshop;

	/**
	 * De communicatie met de klant
	 *
	 * @var array $communicatie Bevat de gevoerde communicatie
	 */
	public array $communicatie = [];

	/**
	 * Constructor
	 *
	 * @param Workshop $workshop De workshop.
	 */
	public function __construct( Workshop $workshop ) {
		$this->workshop = $workshop;
	}

	/**
	 * Zeg de gemaakte afspraak voor de workshop af.
	 *
	 * @since 5.0.0
	 */
	public function afzeggen() : void {
		$this->workshop->vervallen = true;
		$this->workshop->save();
	}

	/**
	 * Annuleer de workshop.
	 *
	 * @return void
	 */
	public function annuleer() : void {
		$order = new Order( $this->workshop->get_referentie() );
		if ( $order->id ) {
			$this->workshop->verzend_email( '_afzegging', $order->annuleer( 0, 'Annulering workshop' ) );
			return;
		}
		$this->workshop->actie->afzeggen();
		if ( $this->workshop->definitief ) {
			Werkplekken::verwijder_werkplekken( $this->workshop->code, $this->workshop->datum );
			$this->workshop->verzend_email( '_afzegging' );
		}
	}

	/**
	 * Verwerk een workshop aanvraag.
	 *
	 * @param array $parameters De input parameters.
	 *
	 * @return void
	 */
	public function aanvraag( array $parameters ) : void {
		$dagdelen                     = [
			strtolower( OCHTEND )  => [
				'start' => '09:30',
				'eind'  => '11:30',
			],
			strtolower( MIDDAG )   => [
				'start' => '13:00',
				'eind'  => '15:00',
			],
			strtolower( NAMIDDAG ) => [
				'start' => '16:30',
				'eind'  => '18:30',
			],
			strtolower( AVOND )    => [
				'start' => '19:00',
				'eind'  => '22:00',
			],
		];
		$this->workshop->email        = $parameters['user_email'];
		$this->workshop->contact      = $parameters['contact'];
		$this->workshop->aantal       = $parameters['aantal'];
		$this->workshop->datum        = $parameters['datum'];
		$this->workshop->start_tijd   = strtotime( $dagdelen[ $parameters['dagdeel'] ]['start'], $parameters['datum'] );
		$this->workshop->eind_tijd    = strtotime( $dagdelen[ $parameters['dagdeel'] ]['eind'], $parameters['datum'] );
		$this->workshop->telnr        = $parameters['telnr'];
		$this->workshop->naam         = $parameters['naam'];
		$this->workshop->technieken   = $parameters['technieken'];
		$this->workshop->vraag        = $parameters['opmerking'] ?? '';
		$this->workshop->dagdeel      = $parameters['dagdeel'];
		$this->workshop->communicatie = [
			[
				'type'    => self::NIEUW,
				'from'    => $this->workshop->email,
				'subject' => "Aanvraag {$this->workshop->naam} op " . date( 'd-m-Y', $this->workshop->datum ),
				'tekst'   => $this->workshop->vraag,
				'tijd'    => current_time( 'd-m-Y H:i' ),
			],
		];
		$this->workshop->save();
		$this->workshop->verzend_email( '_aanvraag_bevestiging' );
	}

	/**
	 * Zet de status naar reactie zonder iets te verzenden.
	 *
	 * @return void
	 */
	public function nulactie() : void {
		$this->workshop->communicatie = array_merge(
			[
				[
					'type'    => self::GEREAGEERD,
					'from'    => wp_get_current_user()->display_name,
					'subject' => "Reactie op {$this->workshop->naam} vraag",
					'tekst'   => 'Geen reactie nodig',
					'tijd'    => current_time( 'd-m-Y H:i' ),
				],
			],
			$this->workshop->communicatie
		);
		$this->workshop->save();
	}

	/**
	 * Voeg een reactie toe en email de aanvrager.
	 *
	 * @param null|string $reactie     De reactie op de vraag.
	 */
	public function reactie( ?string $reactie = null ) : void {
		$subject                      = is_null( $reactie ) ? 'Geen reactie nodig' : "Reactie op {$this->workshop->naam} vraag";
		$this->workshop->reactie      = nl2br( $reactie ?? '' );
		$this->workshop->communicatie = array_merge(
			[
				[
					'type'    => self::GEREAGEERD,
					'from'    => wp_get_current_user()->display_name,
					'subject' => $subject,
					'tekst'   => $reactie,
					'tijd'    => current_time( 'd-m-Y H:i' ),
				],
			],
			$this->workshop->communicatie
		);
		$this->workshop->save();
		if ( ! is_null( $reactie ) ) {
			$this->workshop->verzend_email( '_reactie' );
		}
	}

	/**
	 * Geef aan dat de workshop betaald moet worden
	 */
	public function vraag_betaling() : void {
		$this->workshop->betaling_email = true;
		$this->workshop->save();
		$order = new Order( $this->workshop->get_referentie() );
		$this->workshop->verzend_email( '_betaling', $order->bestel() );
	}

	/**
	 * Bevestig de workshop.
	 *
	 * @return array Eventueel bericht voor de gebruiker.
	 * @since 5.0.0
	 */
	public function bevestig(): array {
		$informeer_klant = true;
		if ( $this->workshop->id && $this->workshop->definitief ) {
			$workshop_ref = new Workshop( $this->workshop->id );
			$verschillend = false;
			$relevant     = [
				'datum',
				'start_tijd',
				'eind_tijd',
				'organisatie',
				'organisatie_adres',
				'organisatie_email',
				'contact',
				'email',
				'programma',
				'kosten',
				'aantal',
			];
			foreach ( $relevant as $property ) {
				if ( $workshop_ref->$property !== $this->workshop->$property ) {
					$verschillend = true;
					break;
				}
			}
			$informeer_klant = $verschillend;
		}
		$herbevestiging             = $this->workshop->definitief;
		$this->workshop->definitief = true;
		if ( $informeer_klant ) {
			$this->workshop->communicatie = array_merge(
				[
					[
						'type'    => self::GEREAGEERD,
						'from'    => wp_get_current_user()->display_name,
						'subject' => "Reactie op {$this->workshop->naam} vraag",
						'tekst'   => 'Bevestiging afspraak verstuurd',
						'tijd'    => current_time( 'd-m-Y H:i' ),
					],
				],
				$this->workshop->communicatie
			);
		}
		$this->workshop->save();
		Werkplekken::verwijder_werkplekken( $this->workshop->code, $this->workshop->datum );
		$level   = 1;
		$bericht = Werkplekken::reserveer_werkplekken(
			$this->workshop->code,
			$this->workshop->naam,
			$this->workshop->werkplekken,
			$this->workshop->datum,
			bepaal_dagdelen( $this->workshop->start_tijd, $this->workshop->eind_tijd )[0]
		);
		if ( $bericht ) {
			$level    = -1;
			$bericht .= ', ';
		}
		if ( ! $informeer_klant ) {
			return [
				'level'  => $level,
				'status' => $bericht . 'de gegevens zijn opgeslagen',
			];
		}
		if ( ! $herbevestiging ) {
			$this->workshop->verzend_email( '_bevestiging' );
			return [
				'level'  => $level,
				'status' => $bericht . 'een bevestiging is verstuurd',
			];
		}
		$order = new Order( $this->workshop->get_referentie() );
		if ( $order->id ) { // Als er al een factuur is aangemaakt, pas dan de order en factuur aan.
			$this->workshop->verzend_email( '_betaling', $order->wijzig( $this->workshop->get_referentie(), 'Correctie op eerdere factuur ' ) );
			return [
				'level'  => $level,
				'status' => $bericht . 'een herbevestiging inclusief eventueel aangepaste factuur is verstuurd',
			];
		}
		$this->workshop->verzend_email( '_herbevestiging' );
		return [
			'level'  => $level,
			'status' => $bericht . 'een herbevestiging is verstuurd',
		];
	}

	/**
	 * Verwerk een ontvangen email.
	 *
	 * @param array $email De ontvangen email.
	 */
	public static function verwerk( array $email ) : void {
		$emailer = new Email();
		/**
		 * Zoek eerst op basis van het case nummer in subject.
		 */
		$workshop = null;
		if ( 2 === sscanf( $email['subject'], '%*[^[WA#][WA#%u]', $aanvraag_id ) ) {
			// Haal de workshop of obv het aanvraag id. Backwards compatibiliteit.
			$workshop = new Workshop( intval( $aanvraag_id ) );
		} elseif ( 2 === sscanf( $email['subject'], '%*[^[WS#][WS#%u]', $workshop_id ) ) {
			// Haal de workshop op obv het workshop id.
			$workshop = new Workshop( intval( $workshop_id ) );
		}
		if ( is_null( $workshop ) ) {
			/**
			 * Als niet gevonden probeer dan doorzenden naar info adres.
			 */
			$email['to']      = "Kleistad <$emailer->info@$emailer->domein>";
			$email['subject'] = 'FW: ' . $email['subject'];
			$emailer->send( $email );
			return;
		}
		$emailer->send(
			[
				'to'      => "Workshop mailbox <$emailer->info@$emailer->domein>",
				'subject' => ucfirst( $workshop->naam ) . ' vraag',
				'content' => "<p>Er is een reactie ontvangen van {$email['from-name']}</p>",
				'sign'    => 'Workshop mailbox',
			]
		);
		$workshop->communicatie = array_merge(
			[
				[
					'type'    => self::VRAAG,
					'from'    => $email['from-name'],
					'subject' => htmlspecialchars( $email['subject'] ),
					'tekst'   => htmlspecialchars( $email['content'] ),
					'tijd'    => $email['tijd'],
				],
			],
			$workshop->communicatie,
		);
		$workshop->save();
	}

}
