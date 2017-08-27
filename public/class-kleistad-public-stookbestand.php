<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Stookbestand extends Kleistad_Public_Shortcode {

	/**
	 *
	 * prepareer 'stookbestand' form
	 *
	 * @return array
	 *
	 * @since   4.0.0
	 */
	public function prepare( $data = null ) {
		$gebruiker_id = get_current_user_id();
		return compact( 'gebruiker_id' );
	}

	/**
	 *
	 * valideer/sanitize 'stookbestand' form
	 *
	 * @return array
	 *
	 * @since   4.0.0
	 */
	public function validate() {
		$vanaf_datum = strtotime( filter_input( INPUT_POST, 'kleistad_vanaf_datum', FILTER_SANITIZE_STRING ) );
		$tot_datum = strtotime( filter_input( INPUT_POST, 'kleistad_tot_datum', FILTER_SANITIZE_STRING ) );
		$gebruiker_id = filter_input( INPUT_POST, 'kleistad_gebruiker_id', FILTER_SANITIZE_NUMBER_INT );
		return compact( 'vanaf_datum', 'tot_datum', 'gebruiker_id' );
	}

	/**
	 *
	 * bewaar 'stookbestand' form gegevens
	 *
	 * @return string
	 *
	 * @since   4.0.0
	 */
	public function save( $data ) {
		$error = new WP_Error();

		extract( $data );
		$gebruiker = get_userdata( $gebruiker_id );

		$upload_dir = wp_upload_dir();
		$bijlage = $upload_dir['basedir'] . '/stookbestand_' . date( 'Y_m_d' ) . '.csv';
		$f = fopen( $bijlage, 'w' );

		$ovenStore = new Kleistad_Ovens();
		$ovens = $ovenStore->get();
		$reserveringStore = new Kleistad_Reserveringen();
		$reserveringen = $reserveringStore->get();
		$regelingStore = new Kleistad_Regelingen();

		$medestokers = [];
		foreach ( $reserveringen as $reservering ) {
			$datum = strtotime( $reservering->jaar . '-' . $reservering->maand . '-' . $reservering->dag );
			if ( ($datum < $vanaf_datum) || ($datum > $tot_datum) ) {
				continue;
			}
			for ( $i = 0; $i < 5; $i++ ) {
				$medestoker_id = $reservering->verdeling[ $i ]['id'];
				if ( $medestoker_id > 0 ) {
					if ( ! array_key_exists( $medestoker_id, $medestokers ) ) {
						$medestoker = get_userdata( $medestoker_id );
						if ( $medestoker ) {
							$medestokers[ $medestoker_id ] = $medestoker->display_name;
						}
					}
				}
			}
		}

		asort( $medestokers );
		$fields = [ 'Stoker', 'Datum', 'Oven', 'Kosten', 'Soort Stook', 'Temperatuur', 'Programma' ];
		for ( $i = 1; $i <= 2; $i++ ) {
			foreach ( $medestokers as $medestoker ) {
				$fields[] = $medestoker;
			}
		}
		$fields[] = 'Totaal';
		fputcsv( $f, $fields, ';', '"' );

		foreach ( $reserveringen as $reservering ) {
			$stoker = get_userdata( $reservering->gebruiker_id );
			$stoker_naam = ( ! $stoker) ? 'onbekend' : $stoker->display_name;
			$totaal = 0;
			$values = [
				$stoker_naam,
				$reservering->dag . '-' . $reservering->maand . '-' . $reservering->jaar,
				$ovens[ $reservering->oven_id ]->naam,
				$ovens[ $reservering->oven_id ]->kosten,
				$reservering->soortstook,
				$reservering->temperatuur,
				$reservering->programma,
			];
			foreach ( $medestokers as $id => $medestoker ) {
				$percentage = 0;
				for ( $i = 0; $i < 5; $i ++ ) {
					if ( $reservering->verdeling[ $i ]['id'] == $id ) {
						$percentage = $percentage + $reservering->verdeling[ $i ]['perc'];
					}
				}
				$values [] = ($percentage == 0) ? '' : $percentage;
			}
			foreach ( $medestokers as $id => $medestoker ) {
				$percentage = 0;
				for ( $i = 0; $i < 5; $i ++ ) {
					if ( $reservering->verdeling[ $i ]['id'] == $id ) {
						$percentage = $percentage + $reservering->verdeling[ $i ]['perc'];
					}
				}
				if ( $percentage > 0 ) {
					// als er een speciale regeling / tarief is afgesproken, dan geldt dat tarief
					$regeling = $regelingStore->get( $id, $reservering->oven_id );
					$kosten = round( ($percentage * ( ( is_null( $regeling ) ) ? $ovens[ $reservering->oven_id ]->kosten : $regeling )) / 100, 2 );
					$totaal += $kosten;
				}
				$values [] = ($percentage == 0) ? '' : number_format( $kosten, 2, ',', '' );
			}
			$values [] = number_format( $totaal, 2, ',', '' );
			fputcsv( $f, $values, ';', '"' );
		}

		fclose( $f );

		$to = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
		$message = '<p>Bijgaand het bestand in .CSV formaat met alle transacties tussen ' . date( 'd-m-Y', $vanaf_datum ) . ' en ' . date( 'd-m-Y', $tot_datum ) . '.</p>';
		$attachments = [ $bijlage ];
		if ( self::compose_email( $to, 'Kleistad stookbestand', $message, [], $attachments ) ) {
			return 'Het bestand is per email verzonden.';
		} else {
			$error->add( '', 'Er is een fout opgetreden' );
			return $error;
		}
	}

}
