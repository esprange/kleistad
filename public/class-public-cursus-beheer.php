<?php
/**
 * Shortcode cursus beheer.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;

/**
 * De Cursus beheer class.
 */
class Public_Cursus_Beheer extends ShortcodeForm {

	/**
	 * Prepareer 'cursus_beheer' toevoegen form
	 *
	 * @return string
	 */
	protected function prepare_toevoegen() : string {
		$this->data['docenten'] = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'role'    => [ DOCENT ],
				'orderby' => 'display_name',
			]
		);
		if ( ! isset( $this->data['cursus'] ) ) {
			$this->data['cursus'] = new Cursus();
		}
		return $this->content();
	}

	/**
	 * Prepareer 'cursus_beheer' wijzigen form
	 *
	 * @return string
	 */
	protected function prepare_wijzigen() : string {
		$this->data['docenten'] = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'role'    => [ DOCENT ],
				'orderby' => 'display_name',
			]
		);
		if ( ! isset( $this->data['cursus'] ) ) {
			$this->data['cursus'] = new Cursus( $this->data['id'] );
		}
		return $this->content();
	}

	/**
	 * Prepareer 'cursus_beheer' standaard overzicht
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		$this->data['cursussen'] = new Cursussen();
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'cursus_beheer' form
	 *
	 * @since   4.0.87
	 *
	 * @return array
	 */
	public function process() :array {
		$error                = new WP_Error();
		$this->data['input']  = filter_input_array(
			INPUT_POST,
			[
				'cursus_id'       => FILTER_SANITIZE_NUMBER_INT,
				'naam'            => FILTER_SANITIZE_STRING,
				'docent'          => FILTER_SANITIZE_STRING,
				'start_datum'     => FILTER_SANITIZE_STRING,
				'eind_datum'      => FILTER_SANITIZE_STRING,
				'lesdatums'       => FILTER_SANITIZE_STRING,
				'start_tijd'      => FILTER_SANITIZE_STRING,
				'eind_tijd'       => FILTER_SANITIZE_STRING,
				'techniekkeuze'   => FILTER_SANITIZE_STRING,
				'werkplekken'     => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_REQUIRE_ARRAY,
					'options' => [ 'default' => [] ],
				],
				'vervallen'       => FILTER_SANITIZE_STRING,
				'inschrijfkosten' => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'cursuskosten'    => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'inschrijfslug'   => FILTER_SANITIZE_STRING,
				'indelingslug'    => FILTER_SANITIZE_STRING,
				'technieken'      => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'maximum'         => FILTER_SANITIZE_NUMBER_INT,
				'meer'            => FILTER_SANITIZE_STRING,
				'tonen'           => FILTER_SANITIZE_STRING,
			]
		);
		$cursus_id            = $this->data['input']['cursus_id'];
		$this->data['cursus'] = $cursus_id > 0 ? new Cursus( $cursus_id ) : new Cursus();
		if ( 'verwijderen' === $this->form_actie ) {
			return $this->save();
		}
		if ( is_null( $this->data['input']['technieken'] ) ) {
			$this->data['input']['technieken'] = [];
		}
		if ( is_null( $this->data['input']['werkplekken'] ) ) {
			$this->data['input']['werkplekken'] = [];
		}
		if ( $this->data['input']['cursuskosten'] < $this->data['input']['inschrijfkosten'] ) {
			$error->add( 'Invoerfout', 'Als er inschrijfkosten zijn dan kunnen de cursuskosten niet lager zijn' );
		}
		if ( ! is_null( $this->data['input']['tonen'] ) ) {
			foreach ( [ $this->data['input']['inschrijfslug'], $this->data['input']['indelingslug'] ] as $slug ) {
				if ( is_null( get_page_by_title( $slug, OBJECT, Email::POST_TYPE ) ) ) { // phpcs:ignore
					$error->add( 'Invoerfout', 'Er bestaat nog geen pagina met de naam ' . $this->data['input']['inschrijfslug'] );
				}
			}
		}
		if ( ! is_null( $this->data['input']['vervallen'] ) && $this->data['cursus']->maximum !== $this->data['cursus']->get_ruimte() ) {
			$error->add( 'Invoerfout', 'Er zijn nog cursisten ingedeeld op de cursus. Annuleer de inschrijvingen eerst' );
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $this->melding( $error );
		}
		return $this->save();
	}

	/**
	 * Verwijder de cursus
	 *
	 * @return array
	 */
	protected function verwijderen() : array {
		$cursus = new Cursus( $this->data['input']['cursus_id'] );
		if ( count( new Inschrijvingen( $cursus->id, true ) ) ) {
			return [
				'status' => $this->status( new WP_Error( 'ingedeeld', 'Er zijn al cursisten inschrijvingen, de cursus kan niet verwijderd worden' ) ),
			];
		}
		$cursus->verwijder_werkplekken( $cursus->code );
		$cursus->erase();
		return [
			'status'  => $this->status( 'De cursus informatie is verwijderd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Bewaar de cursus
	 *
	 * @return array
	 */
	protected function bewaren() : array {
		$cursus_id               = $this->data['input']['cursus_id'];
		$cursus                  = $cursus_id > 0 ? new Cursus( $cursus_id ) : new Cursus();
		$cursus->naam            = $this->data['input']['naam'];
		$cursus->docent          = $this->data['input']['docent'];
		$cursus->start_datum     = strtotime( $this->data['input']['start_datum'] );
		$cursus->eind_datum      = strtotime( $this->data['input']['eind_datum'] );
		$cursus->lesdatums       = array_map(
			function( $lesdatum ) {
				return strtotime( $lesdatum );
			},
			explode( ';', $this->data['input']['lesdatums'] )
		);
		$cursus->start_tijd      = strtotime( $this->data['input']['start_tijd'] );
		$cursus->eind_tijd       = strtotime( $this->data['input']['eind_tijd'] );
		$cursus->techniekkeuze   = '' != $this->data['input']['techniekkeuze']; // phpcs:ignore
		$cursus->vervallen       = '' != $this->data['input']['vervallen']; // phpcs:ignore
		$cursus->inschrijfkosten = $this->data['input']['inschrijfkosten'];
		$cursus->cursuskosten    = $this->data['input']['cursuskosten'];
		$cursus->inschrijfslug   = $this->data['input']['inschrijfslug'];
		$cursus->indelingslug    = $this->data['input']['indelingslug'];
		$cursus->technieken      = $this->data['input']['technieken'];
		$cursus->werkplekken     = $this->data['input']['werkplekken'];
		$cursus->maximum         = $this->data['input']['maximum'];
		$cursus->meer            = '' != $this->data['input']['meer']; // phpcs:ignore
		$cursus->tonen           = '' != $this->data['input']['tonen']; // phpcs:ignore
		$cursus->save();
		$bericht = $cursus->update_werkplekken();
		return [
			'status'  => [
				'level'  => $bericht ? -1 : 1,
				'status' => $bericht ? "$bericht, de gegevens zijn opgeslagen" : 'De gegevens zijn opgeslagen',
			],
			'content' => $this->display(),
		];
	}

}
