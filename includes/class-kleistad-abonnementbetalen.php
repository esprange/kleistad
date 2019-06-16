<?php
/**
 * Definieer de abonnement betalen class
 *
 * @link       https://www.kleistad.nl
 * @since      5.5.1
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Class abonnement, alle acties voor het betalen van abonnementen
 */
class Kleistad_AbonnementBetalen extends Kleistad_betalen {

	/**
	 * Het abonnee id
	 *
	 * @since 5.1.0
	 * @access private
	 * @var int $abonnee_id.
	 */
	private $abonnee_id;

	/**
	 * Het psp incasso id
	 *
	 * @since 5.1.0
	 * @access private
	 * @var string $subscriptie_id.
	 */
	private $subscriptie_id;

	/**
	 * Constructor
	 *
	 * Maak het abonnement object .
	 *
	 * @since 5.5.1
	 *
	 * @param int    $abonnee_id     Wp id van de abonnee.
	 * @param string $subscriptie_id Incasso id van de psp.
	 */
	public function __construct( $abonnee_id, $subscriptie_id ) {
		$this->abonnee_id     = $abonnee_id;
		$this->subscriptie_id = $subscriptie_id;
		parent::__construct();
	}

	/**
	 * Controleer of er een incasso actief is
	 *
	 * @since 5.5.1
	 *
	 * @return bool Als true, dan is incasso actief.
	 */
	public function incasso_actief() {
		return self::actief( $this->abonnee_id, $this->subscriptie_id );
	}

	/**
	 * Maak de betaalorder. In de callback wordt de automatische incasso gestart.
	 *
	 * @since 5.5.1
	 *
	 * @param float  $bedrag Het bedrag dat betaald moet worden.
	 * @param string $omschrijving Omschrijving op het bankafschrift.
	 */
	public function betalen( $bedrag, $omschrijving ) {
		$this->order(
			$this->abonnee_id,
			__CLASS__ . '-' . $this->code . '-incasso',
			$bedrag,
			$omschrijving,
			'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging',
			true
		);
	}

	/**
	 * Plan de incasso per datum in.
	 *
	 * @since 5.5.1
	 *
	 * @param int    $datum        de datum waarop de incasso start.
	 * @param float  $bedrag       het incasso bedrag.
	 * @param string $omschrijving de tekst bij de incasso.
	 * @return string    het subscriptie id.
	 */
	public function incasso_inplannen( $datum, $bedrag, $omschrijving ) {
		$this->subscriptie_id = $this->annuleer( $this->abonnee_id, $this->subscriptie_id );
		if ( $this->heeft_mandaat( $this->abonnee_id ) ) {
			$this->subscriptie_id = $this->herhaalorder(
				$this->abonnee_id,
				$bedrag,
				$omschrijving,
				$datum
			);
		}
		return $this->subscriptie_id;
	}

	/**
	 * Annuleer het abonnement per datum.
	 *
	 * @since 5.5.1
	 *
	 * @return string    het subscriptie id.
	 */
	public function incasso_stoppen() {
		$this->subscriptie_id = $this->annuleer( $this->abonnee_id, $this->subscriptie_id );
		$this->verwijder_mandaat( $this->abonnee_id );
		return $this->subscriptie_id;
	}

	/**
	 * (Her)activeer een abonnement. Wordt aangeroepen vanuit de betaal callback.
	 *
	 * @since 4.3.0
	 *
	 * @param array  $parameters De parameters 0: gebruiker-id, 1: de melddatum.
	 * @param string $bedrag     Geeft aan of het een eerste start of een herstart betreft.
	 * @param bool   $betaald    Of er werkelijk betaald is.
	 */
	public static function callback( $parameters, $bedrag, $betaald = true ) {
		if ( $betaald ) {
			$abonnement = new Kleistad_Abonnement( intval( $parameters[0] ) );
			$abonnement->autoriseer( true );
			if ( 'incasso' === $parameters[1] ) {
				// Een succesvolle betaling van een vervolg.
				$betalen = new static( $abonnement->abonnee_id, $abonnement->subscriptie_id );
				if ( $abonnement->herstart_datum > $abonnement->incasso_datum ) {
					$abonnement->subscriptie_id = $betalen->incasso_inplannen( $abonnement->herstart_datum );
				} else {
					$abonnement->subscriptie_id = $betalen->incasso_inplannen( $abonnement->incasso_datum );
				}
				$email = '_betaalwijze_ideal';
			} else {
				$email = '_' . $parameters[1];
			}
			$abonnement->save();
			$abonnement->email( $email );
		}
	}

}
