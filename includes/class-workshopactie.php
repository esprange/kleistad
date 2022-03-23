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
	public function afzeggen() {
		if ( ! $this->workshop->vervallen ) {
			$this->workshop->vervallen = true;
			$this->workshop->save();
		}
		// Als de betaling email al verstuurd is, dan vind de annulering plaats vanuit de boekhouding.
		if ( $this->workshop->definitief && ! $this->workshop->betaling_email ) {
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
	public function aanvraag( array $parameters ) {
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
				'tekst'   => $parameters['opmerking'] ?? '',
				'tijd'    => current_time( 'd-m-Y H:i' ),
			],
		];
		$this->workshop->save();
		$this->workshop->verzend_email( '_aanvraag_bevestiging' );
	}

	/**
	 * Voeg een reactie toe en email de aanvrager.
	 *
	 * @param string $reactie     De reactie op de vraag.
	 */
	public function reactie( string $reactie ) {
		$this->workshop->reactie      = nl2br( $reactie );
		$this->workshop->communicatie = array_merge(
			[
				[
					'type'    => self::GEREAGEERD,
					'from'    => wp_get_current_user()->display_name,
					'subject' => "Reactie op {$this->workshop->naam} vraag",
					'tekst'   => $reactie,
					'tijd'    => current_time( 'd-m-Y H:i' ),
				],
			],
			$this->workshop->communicatie
		);
		$this->workshop->save();
		$this->workshop->verzend_email( '_reactie' );
	}

	/**
	 * Geef aan dat de workshop betaald moet worden
	 */
	public function vraag_betaling() {
		$this->workshop->betaling_email = true;
		$this->workshop->save();
		$order = new Order( $this->workshop->get_referentie() );
		$this->workshop->verzend_email( '_betaling', $order->actie->bestel( 0.0, $this->workshop->datum ) );
	}

	/**
	 * Bevestig de workshop.
	 *
	 * @return bool
	 * @since 5.0.0
	 */
	public function bevestig(): bool {
		$herbevestiging               = $this->workshop->definitief;
		$this->workshop->definitief   = true;
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
		$this->workshop->save();
		if ( ! $herbevestiging ) {
			return $this->workshop->verzend_email( '_bevestiging' );
		}
		$order = new Order( $this->workshop->get_referentie() );
		if ( $order->id ) { // Als er al een factuur is aangemaakt, pas dan de order en factuur aan.
			$factuur = $order->actie->wijzig( $this->workshop->get_referentie() );
			if ( false === $factuur ) { // De factuur is aangemaakt in een periode die boekhoudkundig geblokkeerd is, correctie is niet mogelijk.
				return false;
			} elseif ( ! empty( $factuur ) ) { // Er was al een factuur die nog gecorrigeerd mag worden.
				return $this->workshop->verzend_email( '_betaling', $factuur );
			}
		}
		return $this->workshop->verzend_email( '_herbevestiging' );
	}

	/**
	 * Verwerk een ontvangen email.
	 *
	 * @param array $email De ontvangen email.
	 */
	public static function verwerk( array $email ) {
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
