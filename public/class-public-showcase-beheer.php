<?php
/**
 * Shortcode showcase beheer.
 *
 * @link       https://www.kleistad.nl
 * @since      7.6.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * Include voor image file upload.
 */
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

/**
 * De kleistad showcase beheer class.
 */
class Public_Showcase_Beheer extends ShortcodeForm {

	/**
	 * Prepareer 'showcase' toevoegen form
	 *
	 * @return string
	 */
	protected function prepare_toevoegen() : string {
		$this->data['id']       = 0;
		$this->data['showcase'] = new Showcase();
		return $this->content();
	}

	/**
	 * Prepareer 'showcase' wijzigen form
	 *
	 * @return string
	 */
	protected function prepare_wijzigen() : string {
		$this->data['showcase'] = new Showcase( $this->data['id'] );
		return $this->content();
	}

	/**
	 * Prepareer 'showcase' overzicht
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		$this->data['showcases'] = new Showcases(
			[
				'author'      => get_current_user_id(),
				'post_status' => [
					Showcase::VERKOCHT,
					Showcase::BESCHIKBAAR,
					Showcase::CONCEPT,
				],
			]
		);
		return $this->content();
	}

	/**
	 * Prepareer 'showcase' overzicht
	 *
	 * @return string
	 */
	protected function prepare_verkoop() : string {
		$this->data['showcases'] = new Showcases(
			[
				'post_status' => [
					Showcase::VERKOCHT,
					Showcase::BESCHIKBAAR,
				],
			]
		);
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'showcase' form
	 *
	 * @return array
	 */
	public function process() : array {
		$this->data['showcase']       = filter_input_array(
			INPUT_POST,
			[
				'id'           => FILTER_SANITIZE_NUMBER_INT,
				'titel'        => FILTER_SANITIZE_STRING,
				'positie'      => FILTER_SANITIZE_STRING,
				'breedte'      => FILTER_SANITIZE_NUMBER_INT,
				'diepte'       => FILTER_SANITIZE_NUMBER_INT,
				'hoogte'       => FILTER_SANITIZE_NUMBER_INT,
				'prijs'        => FILTER_SANITIZE_NUMBER_FLOAT,
				'jaar'         => FILTER_SANITIZE_NUMBER_INT,
				'beschrijving' => FILTER_SANITIZE_STRING,
				'shows'        => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_FORCE_ARRAY,
					'options' => [ 'default' => [] ],
				],
			]
		);
		$this->data['showcase']['id'] = intval( $this->data['showcase']['id'] );
		foreach ( $this->data['showcase']['shows'] ?? [] as $key => $show ) {
			list( $start, $eind )                    = explode( ';', $show );
			$this->data['showcase']['shows'][ $key ] = [
				'start' => $start,
				'eind'  => $eind,
			];
		}
		return $this->save();
	}

	/**
	 * Showcase moet verwijderd worden.
	 *
	 * @return array
	 */
	protected function verwijderen(): array {
		$showcase = new Showcase( $this->data['showcase']['id'] );
		$showcase->erase();
		return [
			'status'  => $this->status( 'Het werkstuk is verwijderd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Showcase aanmelden
	 *
	 * @return array
	 */
	protected function aanmelden(): array {
		return $this->wijzigen( Showcase::BESCHIKBAAR, 'Het werkstuk is aangemeld' );
	}

	/**
	 * Showcase tentoonstellen
	 *
	 * @return array
	 */
	protected function tentoonstellen(): array {
		$showcase        = new Showcase( $this->data['showcase']['id'] );
		$showcase->shows = $this->data['showcase']['shows'] ?? [];
		$showcase->save();
		return [
			'status'  => $this->status( 'Gegevens zijn opgeslagen' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Showcase verkocht melden
	 *
	 * @return array
	 */
	protected function verkochtmelden(): array {
		return $this->wijzigen( Showcase::VERKOCHT, 'Het werkstuk is verkocht' );
	}

	/**
	 * Showcase afvoeren
	 *
	 * @return array
	 */
	protected function afvoeren(): array {
		return $this->wijzigen( Showcase::BESCHIKBAAR, 'Het werkstuk is terug naar keramist' );
	}

	/**
	 * Showcase moet worden opgeslagen
	 *
	 * @return array
	 */
	protected function bewaren(): array {
		$showcase               = new Showcase( $this->data['showcase']['id'] );
		$showcase->titel        = $this->data['showcase']['titel'];
		$showcase->beschrijving = $this->data['showcase']['beschrijving'] ?? '';
		$showcase->breedte      = intval( $this->data['showcase']['breedte'] ) ?? 0;
		$showcase->diepte       = intval( $this->data['showcase']['diepte'] ) ?? 0;
		$showcase->hoogte       = intval( $this->data['showcase']['hoogte'] ) ?? 0;
		$showcase->positie      = $this->data['showcase']['positie'] ?? $showcase->positie;
		$showcase->prijs        = floatval( $this->data['showcase']['prijs'] ) ?? $showcase->prijs;
		$showcase->save();
		if ( $_FILES['foto']['size'] ) {
			$result = media_handle_upload( 'foto', $showcase->id );
			if ( is_wp_error( $result ) ) {
				return [ 'status' => $this->status( $result ) ];
			}
		}
		return [
			'status'  => $this->status( 'Gegevens zijn opgeslagen' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Showcase status moet gewijzigd worden.
	 *
	 * @param string $status  De nieuwe status.
	 * @param string $melding De de tonen melding.
	 *
	 * @return array
	 */
	private function wijzigen( string $status, string $melding ): array {
		$showcase         = new Showcase( $this->data['showcase']['id'] );
		$showcase->status = $status;
		$showcase->save();
		return [
			'status'  => $this->status( $melding ),
			'content' => $this->display(),
		];
	}

}
