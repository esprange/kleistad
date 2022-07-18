<?php
/**
 * Shortcode registratie overzicht.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_REST_Request;
use WP_REST_Response;
use WP_User;
/**
 * De kleistad registratie overzicht class.
 */
class Public_Registratie_Overzicht extends Shortcode {

	/**
	 * Register rest URI's.
	 *
	 * @since 7.2.4
	 */
	public static function register_rest_routes() {
		register_rest_route(
			KLEISTAD_API,
			'/registratie',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_show' ],
				'args'                => [
					'gebruiker_id' => [
						'required' => true,
						'type'     => 'int',
					],
				],
				'permission_callback' => function () {
					return is_user_logged_in() && current_user_can( BESTUUR );
				},
			]
		);
	}

	/**
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response Ajax response.
	 */
	public static function callback_show( WP_REST_Request $request ) : WP_REST_Response {
		$gebruiker_id = intval( $request->get_param( 'gebruiker_id' ) );
		$gebruiker    = get_user_by( 'ID', $gebruiker_id );
		return new WP_REST_Response(
			[
				'content' => self::toon_gebruiker( $gebruiker ),
				'naam'    => html_entity_decode( $gebruiker->display_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			]
		);
	}

	/**
	 *
	 * Prepareer 'registratie_overzicht' form
	 *
	 * @since   4.0.87
	 *
	 * @return string
	 */
	protected function prepare() : string {
		$this->data = [
			'registraties' => $this->get_registraties(),
			'cursussen'    => $this->get_cursussen(),
		];
		return $this->content();
	}

	/**
	 * Schrijf cursisten informatie naar het bestand.
	 */
	protected function cursisten() {
		$cursus_fields = [
			'Voornaam',
			'Achternaam',
			'Email',
			'Straat',
			'Huisnr',
			'Postcode',
			'Plaats',
			'Telefoon',
			'Lid',
			'Cursus',
			'Cursus code',
			'Inschrijf datum',
			'Inschrijf status',
			'Aantal',
			'Technieken',
			'Opmerking',
		];
		fputcsv( $this->filehandle, $cursus_fields, ';' );
		foreach ( new Cursisten() as $cursist ) {
			$inschrijvingen = [];
			foreach ( $cursist->inschrijvingen as $inschrijving ) {
				$inschrijvingen[] = [
					'inschrijfdatum' => $inschrijving->datum,
					'data'           => array_merge(
						[
							'C' . $inschrijving->cursus->id . '-' . $inschrijving->cursus->naam,
							$inschrijving->code,
							date( 'd-m-Y', $inschrijving->datum ),
							$inschrijving->geannuleerd ? 'geannuleerd' : ( $inschrijving->ingedeeld ? 'ingedeeld' : 'wacht op betaling' ),
							$inschrijving->aantal,
							implode( ' ', $inschrijving->technieken ),
							$inschrijving->opmerking,
						]
					),
				];
			}
			foreach ( $inschrijvingen as $inschrijving ) {
				fputcsv(
					$this->filehandle,
					array_merge(
						[
							$cursist->first_name,
							$cursist->last_name,
							$cursist->user_email,
							$cursist->straat,
							$cursist->huisnr,
							$cursist->pcode,
							$cursist->plaats,
							$cursist->telnr,
							user_can( $cursist->ID, LID ) ? 'Ja' : 'Nee',
						],
						$inschrijving['data']
					),
					';'
				);
			}
		}
	}

	/**
	 * Schrijf abonnees informatie naar het bestand.
	 */
	protected function abonnees() {
		$abonnee_fields = [
			'Achternaam',
			'Voornaam',
			'Email',
			'Straat',
			'Huisnr',
			'Postcode',
			'Plaats',
			'Telefoon',
			'Status',
			'Inschrijf datum',
			'Start_datum',
			'Pauze_datum',
			'Eind_datum',
			'Abonnee code',
			'Abonnement_soort',
			'Abonnement_extras',
			'Opmerking',
		];
		fputcsv( $this->filehandle, $abonnee_fields, ';' );
		foreach ( new Abonnees() as $abonnee ) {
			$abonnee_gegevens = [
				$abonnee->last_name,
				$abonnee->first_name,
				$abonnee->user_email,
				$abonnee->straat,
				$abonnee->huisnr,
				$abonnee->pcode,
				$abonnee->plaats,
				$abonnee->telnr,
				$abonnee->abonnement->get_statustekst( false ),
				date( 'd-m-Y', $abonnee->abonnement->datum ),
				date( 'd-m-Y', $abonnee->abonnement->start_datum ),
				$abonnee->abonnement->pauze_datum ? date( 'd-m-Y', $abonnee->abonnement->pauze_datum ) : '',
				$abonnee->abonnement->eind_datum ? date( 'd-m-Y', $abonnee->abonnement->eind_datum ) : '',
				$abonnee->abonnement->code,
				$abonnee->abonnement->soort,
				implode( ', ', $abonnee->abonnement->extras ),
				$abonnee->abonnement->opmerking,
			];
			fputcsv( $this->filehandle, $abonnee_gegevens, ';' );
		}
	}

	/**
	 * Schrijf dagdelenkaart informatie naar het bestand.
	 */
	protected function dagdelenkaarten() {
		$dagdelenkaart_fields = [
			'Achternaam',
			'Voornaam',
			'Email',
			'Straat',
			'Huisnr',
			'Postcode',
			'Plaats',
			'Telefoon',
			'Dagdelenkaart code',
			'Start_datum',
			'Eind_datum',
			'Opmerking',
		];
		fputcsv( $this->filehandle, $dagdelenkaart_fields, ';' );
		foreach ( new Dagdelengebruikers() as $dagdelengebruiker ) {
			fputcsv(
				$this->filehandle,
				[
					$dagdelengebruiker->last_name,
					$dagdelengebruiker->first_name,
					$dagdelengebruiker->user_email,
					$dagdelengebruiker->straat,
					$dagdelengebruiker->huisnr,
					$dagdelengebruiker->pcode,
					$dagdelengebruiker->plaats,
					$dagdelengebruiker->telnr,
					$dagdelengebruiker->dagdelenkaart->code,
					date( 'd-m-Y', $dagdelengebruiker->dagdelenkaart->start_datum ),
					date( 'd-m-Y', $dagdelengebruiker->dagdelenkaart->eind_datum ),
					$dagdelengebruiker->dagdelenkaart->opmerking,
				],
				';'
			);
		}
	}

	/**
	 * Haal de registratie data op
	 *
	 * @return array de registraties.
	 */
	private function get_registraties() : array {
		$registraties = [];
		foreach ( get_users( [ 'orderby' => 'display_name' ] ) as $gebruiker ) {
			$abonnement                     = new Abonnement( $gebruiker->ID );
			$dagdelenkaart                  = new Dagdelenkaart( $gebruiker->ID );
			$cursist                        = new Cursist( $gebruiker->ID );
			$registraties[ $gebruiker->ID ] = [
				'is_abonnee'       => boolval( $abonnement->start_datum ),
				'is_dagdelenkaart' => boolval( $dagdelenkaart->start_datum ),
				'is_cursist'       => count( $cursist->inschrijvingen ),
				'voornaam'         => $gebruiker->first_name,
				'achternaam'       => $gebruiker->last_name,
				'telnr'            => $gebruiker->telnr,
				'email'            => $gebruiker->user_email,
			];
		}
		return $registraties;
	}

	/**
	 * Geef de cursussen in omgekeerde volgorde.
	 *
	 * @return array
	 */
	private function get_cursussen() : array {
		$cursussen = [];
		foreach ( new Cursussen() as $cursus ) {
			$cursussen[ $cursus->id ] = [
				'code' => $cursus->code,
				'naam' => $cursus->naam,
			];
		}
		krsort( $cursussen );
		return $cursussen;
	}

	/**
	 * De details van de gebruiker
	 *
	 * @param WP_User $gebruiker Het WP user.
	 *
	 * @return string De html tekst.
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	private static function toon_gebruiker( WP_User $gebruiker ) : string {
		$html  = <<< EOT
			<div class="kleistad-row">
				<div class="kleistad-col-1 kleistad-label">Adres</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-6">
					$gebruiker->straat $gebruiker->huisnr
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-6">
					 $gebruiker->pcode $gebruiker->plaats
				 </div>
			</div>
		EOT;
		$html .= self::cursist( $gebruiker );
		$html .= self::abonnement( $gebruiker );
		$html .= self::dagdelenkaart( $gebruiker );
		return $html;
	}

	/**
	 * De cursus details van de gebruiker
	 *
	 * @param WP_User $gebruiker Het WP user.
	 *
	 * @return string De html tekst.
	 */
	private static function cursist( WP_User $gebruiker ) : string {
		$html          = '';
		$cursus_header = false;
		$cursist       = new Cursist( $gebruiker->ID );
		foreach ( $cursist->inschrijvingen as $inschrijving ) {
			if ( ! $cursus_header ) {
				$cursus_header = true;
				$html         .= <<< EOT
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">Cursus</div>
				<div class="kleistad-col-1 kleistad-label">Code</div>
				<div class="kleistad-col-2 kleistad-label">Ingedeeld</div>
				<div class="kleistad-col-2 kleistad-label">Geannuleerd</div>
				<div class="kleistad-col-2 kleistad-label">Technieken</div>
			</div>
		EOT;
			}
			$code_string = $inschrijving->cursus->code . ( 1 < $inschrijving->aantal ? " ( $inschrijving->aantal )" : '' );
			$ingedeeld   = $inschrijving->ingedeeld ? '<span class="dashicons dashicons-yes"></span>' : '';
			$geannuleerd = $inschrijving->geannuleerd ? '<span class="dashicons dashicons-yes"></span>' : '';
			$technieken  = implode( ', ', $inschrijving->technieken ?? [] );
			$html       .= <<< EOT
			<div class="kleistad-row">
				<div class="kleistad-col-3" style="overflow-x: hidden">{$inschrijving->cursus->naam}</div>
				<div class="kleistad-col-1">$code_string</div>
				<div class="kleistad-col-2">$ingedeeld</div>
				<div class="kleistad-col-2">$geannuleerd</div>
				<div class="kleistad-col-2" style="overflow-x: hidden">$technieken</div>
			</div>
		EOT;
		}
		return $html;
	}

	/**
	 * De abonnement details van de gebruiker
	 *
	 * @param WP_User $gebruiker Het WP user.
	 *
	 * @return string De html tekst.
	 */
	private static function abonnement( WP_User $gebruiker ) : string {
		$html       = '';
		$abonnement = new Abonnement( $gebruiker->ID );
		if ( $abonnement->start_datum ) {
			$extras   = count( $abonnement->extras ) ? ( '<br/>' . implode( ', ', $abonnement->extras ) ) : '';
			$start    = date( 'd-m-Y', $abonnement->start_datum );
			$pauze    = $abonnement->pauze_datum ? date( 'd-m-Y', $abonnement->start_datum ) : '';
			$herstart = $abonnement->herstart_datum ? date( 'd-m-Y', $abonnement->herstart_datum ) : '';
			$eind     = $abonnement->eind_datum ? date( 'd-m-Y', $abonnement->eind_datum ) : '';
			$html    .= <<< EOT
			<div class="kleistad-row">
				<div class="kleistad-col-2 kleistad-label">Abonnement</div>
				<div class="kleistad-col-2 kleistad-label">Start datum</div>
				<div class="kleistad-col-2 kleistad-label">Pauze datum</div>
				<div class="kleistad-col-2 kleistad-label">Herstart datum</div>
				<div class="kleistad-col-2 kleistad-label">Eind datum</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-2">$abonnement->soort $extras</div>
				<div class="kleistad-col-2">$start</div>
				<div class="kleistad-col-2">$pauze</div>
				<div class="kleistad-col-2">$herstart</div>
				<div class="kleistad-col-2">$eind</div>
			</div>
		EOT;
		}
		return $html;
	}

	/**
	 * De dagdelenkaart details van de gebruiker
	 *
	 * @param WP_User $gebruiker Het WP user.
	 *
	 * @return string De html tekst.
	 */
	private static function dagdelenkaart( WP_User $gebruiker ) : string {
		$html          = '';
		$dagdelenkaart = new Dagdelenkaart( $gebruiker->ID );
		if ( $dagdelenkaart->start_datum ) {
			$start  = date( 'd-m-Y', $dagdelenkaart->start_datum );
			$actief = $dagdelenkaart->start_datum > strtotime( '- ' . Dagdelenkaart::KAART_DUUR . ' month' ) ? '<span class="dashicons dashicons-yes"></span>' : '';
			$html  .= <<< EOT
			<div class="kleistad-row">
				<div class="kleistad-col-2 kleistad-label">Dagdelenkaart</div>
				<div class="kleistad-col-2 kleistad-label">Start datum</div>
				<div class="kleistad-col-2 kleistad-label">Actief</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-2">$dagdelenkaart->code</div>
				<div class="kleistad-col-2">$start</div>
				<div class="kleistad-col-2">$actief</div>
			</div>
		EOT;
		}
		return $html;
	}
}
