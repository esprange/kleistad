<?php
/**
 * Shortcode cursus inschrijving.
 *
 * @link       https://www.kleistad.nl
 * @since      6.6.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad cursus extra cursisten class.
 */
class Public_Cursus_Extra extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'cursus_extra' form
	 *
	 * @param array $data data voor display.
	 * @return \WP_Error|bool
	 *
	 * @since   6.6.0
	 */
	protected function prepare( &$data ) {
		$param = filter_input_array(
			INPUT_GET,
			[
				'code' => FILTER_SANITIZE_STRING,
				'hsh'  => FILTER_SANITIZE_STRING,
			]
		);
		if ( empty( $param['code'] ) || empty( $param['hsh'] ) ) {
			return new \WP_Error( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
		}
		$inschrijving = Inschrijving::vind( $param['code'] );
		if ( ! is_null( $inschrijving ) && $param['hsh'] === $inschrijving->controle() && 1 < $inschrijving->aantal ) {
			if ( $inschrijving->geannuleerd ) {
				return new \WP_Error( 'Geannuleerd', 'Deelname aan de cursus is geannuleerd.' );
			}
			$data['cursus_naam']  = $inschrijving->cursus->naam;
			$data['cursist_code'] = $inschrijving->code;
			$data['cursist_naam'] = get_user_by( 'id', $inschrijving->klant_id )->display_name;
			$index                = 1;
			if ( ! isset( $data['input'] ) ) {
				foreach ( $inschrijving->extra_cursisten as $extra_cursist_id ) {
					$extra_cursist = get_user_by( 'id', $extra_cursist_id );
					if ( false === $extra_cursist ) {
						continue;
					}
					$data['input']['extra'][ $index ] = [
						'first_name' => $extra_cursist->first_name,
						'last_name'  => $extra_cursist->last_name,
						'user_email' => $extra_cursist->user_email,
						'id'         => $extra_cursist_id,
					];
					$index++;
				}
				while ( $index < $inschrijving->aantal ) {
					$data['input']['extra'][ $index ] = [
						'first_name' => '',
						'last_name'  => '',
						'user_email' => '',
						'id'         => 0,
					];
					$index++;
				}
			}
			return true;
		} else {
			return new \WP_Error( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
		}
	}

	/**
	 * Valideer/sanitize 'cursus_extra' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   6.6.0
	 */
	protected function validate( &$data ) {
		$error                = new \WP_Error();
		$data['input']        = filter_input_array(
			INPUT_POST,
			[
				'extra_cursist' => [
					'filter' => FILTER_DEFAULT,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'code'          => FILTER_SANITIZE_STRING,
			]
		);
		$data['inschrijving'] = Inschrijving::vind( $data['input']['code'] );
		$emails               = [ strtolower( get_user_by( 'id', $data['inschrijving']->klant_id )->user_email ) ];
		foreach ( $data['input']['extra_cursist'] as &$extra_cursist ) {
			if ( ! empty( $extra_cursist['user_email'] ) ) {
				$emails[] = strtolower( $extra_cursist['user_email'] );
			} else {
				continue;
			}
			if ( ! $this->validate_email( $extra_cursist['user_email'] ) ) {
				$error->add( 'verplicht', 'De invoer ' . $extra_cursist['user_email'] . ' is geen geldig E-mail adres.' );
				$extra_cursist['user_email'] = '';
			}
			if ( ! $this->validate_naam( $extra_cursist['first_name'] ) ) {
				$error->add( 'verplicht', 'Een voornaam (een of meer alfabetische karakters) is verplicht' );
				$extra_cursist['first_name'] = '';
			}
			if ( ! $this->validate_naam( $extra_cursist['last_name'] ) ) {
				$error->add( 'verplicht', 'Een achternaam (een of meer alfabetische karakters) is verplicht' );
				$extra_cursist['last_name'] = '';
			}
		}
		if ( count( $emails ) !== count( array_flip( $emails ) ) ) {
			$error->add( 'emails', 'Elk email adres moet uniek zijn en niet gelijk aan het eigen email adres' );
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 *
	 * Bewaar 'cursus_extra' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_Error|array
	 *
	 * @since   6.6.0
	 */
	protected function save( $data ) {
		$extra_cursisten  = [];
		$emails_verzonden = false;
		foreach ( $data['input']['extra_cursist'] as $extra_cursist ) {
			if ( empty( $extra_cursist['user_email'] ) ) {
				continue;
			}
			$extra_cursist_id = intval( $extra_cursist['id'] );
			if ( 0 === $extra_cursist_id ) {
				$extra_cursist_id = email_exists( $extra_cursist['user_email'] );
				if ( false === $extra_cursist_id ) {
					$extra_cursist_id = upsert_user(
						[
							'ID'         => null,
							'first_name' => $extra_cursist['first_name'],
							'last_name'  => $extra_cursist['last_name'],
							'user_email' => $extra_cursist['user_email'],
						]
					);
				}
			}
			if ( ! is_int( $extra_cursist_id ) ) {
				return [
					'status' => $this->status( new \WP_Error( 'intern', 'Er is een interne fout opgetreden, probeer het eventueel later opnieuw.' ) ),
				];
			}
			$extra_cursisten[]  = $extra_cursist_id;
			$extra_inschrijving = new Inschrijving( $data['inschrijving']->cursus->id, $extra_cursist_id );
			if ( $extra_inschrijving->ingedeeld ) {
				if ( 0 < $extra_inschrijving->aantal ) {
					return [
						'status' => $this->status( new \WP_Error( 'dubbel', 'Volgens onze administratie heeft ' . $extra_cursist['first_name'] . ' ' . $extra_cursist['last_name'] . ' zichzelf al opgegeven voor deze cursus. Neem eventueel contact op met Kleistad.' ) ),
					];
				}
			} else {
				$extra_inschrijving->hoofd_cursist_id = $data['inschrijving']->klant_id;
				$extra_inschrijving->email( '_extra' );
				$emails_verzonden              = true;
				$extra_inschrijving->ingedeeld = true;
				$extra_inschrijving->aantal    = 0;
				$extra_inschrijving->datum     = strtotime( 'today' );
			}
			$extra_inschrijving->save();
		}
		$data['inschrijving']->extra_cursisten = $extra_cursisten;
		$data['inschrijving']->save();
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'De gegevens zijn opgeslagen' . ( $emails_verzonden ? ' en welkomst email is verstuurd' : '' ) ),
		];
	}

}
