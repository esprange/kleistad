<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
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
class Kleistad_Public_Reservering extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'saldo' form
	 *
	 * @param array $data data to be prepared.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$error = new WP_Error();
		if ( ! Kleistad_Roles::reserveer() ) {
			$error->add( 'security', 'hiervoor moet je ingelogd zijn' );
			return $error;
		}
		$atts = shortcode_atts(
			[
				'oven' => 'niet ingevuld',
			], $this->atts, 'kleistad_reservering'
		);
		if ( is_numeric( $atts['oven'] ) ) {
			$oven_id = $atts['oven'];

			$oven = new Kleistad_Oven( $oven_id );
			if ( ! intval( $oven->id ) ) {
				$error->add( 'fout', 'oven met id ' . $oven_id . ' is niet bekend in de database !' );
				return $error;
			}

			$gebruikers        = get_users(
				[
					'fields'  => [ 'id', 'display_name' ],
					'orderby' => [ 'nicename' ],
				]
			);
			$huidige_gebruiker = wp_get_current_user();
			$data              = [
				'gebruikers'        => $gebruikers,
				'oven'              => $oven,
				'huidige_gebruiker' => $huidige_gebruiker,
			];
			return true;
		} else {
			$error->add( 'fout', 'de shortcode bevat geen oven nummer tussen 1 en 999 !' );
			return $error;
		}
		return true;
	}

	/**
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response Ajax response.
	 */
	public static function callback_show( WP_REST_Request $request ) {

		$oven_id             = intval( $request->get_param( 'oven_id' ) );
		$maand               = intval( $request->get_param( 'maand' ) );
		$jaar                = intval( $request->get_param( 'jaar' ) );
		$volgende_maand      = $maand < 12 ? $maand + 1 : 1;
		$vorige_maand        = $maand > 1 ? $maand - 1 : 12;
		$volgende_maand_jaar = $maand < 12 ? $jaar : $jaar + 1;
		$vorige_maand_jaar   = $maand > 1 ? $jaar : $jaar - 1;
		$maandnaam           = [
			1  => 'januari',
			2  => 'februari',
			3  => 'maart',
			4  => 'april',
			5  => 'mei',
			6  => 'juni',
			7  => 'juli',
			8  => 'augustus',
			9  => 'september',
			10 => 'oktober',
			11 => 'november',
			12 => 'december',
		];
		$dagnamen            = [
			1 => 'maandag',
			2 => 'dinsdag',
			3 => 'woensdag',
			4 => 'donderdag',
			5 => 'vrijdag',
			6 => 'zaterdag',
			7 => 'zondag',
		];

		$huidige_gebruiker_id = get_current_user_id();
		$huidige_gebruiker    = get_userdata( $huidige_gebruiker_id );
		$oven                 = new Kleistad_Oven( $oven_id );
		$reserveringen        = Kleistad_Reservering::all(
			[
				'jaar'    => $jaar,
				'maand'   => $maand,
				'oven_id' => $oven_id,
			]
		);

		$aantaldagen = date( 't', mktime( 0, 0, 0, $maand, 1, $jaar ) );

		for ( $dag = 1; $dag <= $aantaldagen; $dag++ ) {
			$datum    = mktime( 23, 59, 0, $maand, $dag, $jaar ); // 18:00 uur 's middags
			$row_html = '';
			$weekdag  = date( 'N', $datum );
			if ( $oven->{$dagnamen[ $weekdag ]} ) {
				$kleur            = 'white';
				$verwerkt         = false;
				$datum_verstreken = $datum < time();
				$wijzigbaar       = ! $datum_verstreken || is_super_admin();

				$selectie = [
					'oven_id'       => $oven_id,
					'dag'           => $dag,
					'maand'         => $maand,
					'jaar'          => $jaar,
					'soortstook'    => '',
					'temperatuur'   => '',
					'programma'     => '',
					'verdeling'     => [
						[
							'id'   => $huidige_gebruiker_id,
							'perc' => 100,
						],
						[
							'id'   => 0,
							'perc' => 0,
						],
						[
							'id'   => 0,
							'perc' => 0,
						],
						[
							'id'   => 0,
							'perc' => 0,
						],
						[
							'id'   => 0,
							'perc' => 0,
						],
					],
					'gereserveerd'  => 0,
					'verwijderbaar' => 0,
					'wijzigbaar'    => $wijzigbaar ? 1 : 0,
					'wie'           => ( $wijzigbaar && ! $datum_verstreken ) ? '-beschikbaar-' : '',
					'gebruiker_id'  => $huidige_gebruiker_id,
					'gebruiker'     => $huidige_gebruiker->display_name,
				];

				foreach ( $reserveringen as $reservering ) {
					if ( $reservering->dag === $dag ) {
						if ( $reservering->gebruiker_id == $huidige_gebruiker_id ) {  // WPCS: loose comparison ok.
							$kleur         = ! $datum_verstreken ? 'lightgreen' : $kleur;
							$wijzigbaar    = ! $verwerkt || is_super_admin();
							$verwijderbaar = Kleistad_Roles::override() ? ! $verwerkt : ! $datum_verstreken;
						} else {
							$kleur = ! $datum_verstreken ? 'pink' : $kleur;
							// als de huidige gebruiker geen bevoegdheid heeft, dan geen actie.
							$wijzigbaar    = ( ! $verwerkt && Kleistad_Roles::override() ) || is_super_admin();
							$verwijderbaar = ! $verwerkt && Kleistad_Roles::override();
						}

						$gebruiker_info = get_userdata( $reservering->gebruiker_id );

						$selectie = [
							'oven_id'       => $reservering->oven_id,
							'dag'           => $reservering->dag,
							'maand'         => $reservering->maand,
							'jaar'          => $reservering->jaar,
							'soortstook'    => $reservering->soortstook,
							'temperatuur'   => $reservering->temperatuur,
							'programma'     => $reservering->programma,
							'verdeling'     => $reservering->verdeling,
							'gebruiker_id'  => $reservering->gebruiker_id,
							'gebruiker'     => $gebruiker_info->display_name,
							'wie'           => $gebruiker_info->display_name,
							'gereserveerd'  => 1,
							'verwijderbaar' => $verwijderbaar ? 1 : 0,
							'wijzigbaar'    => $wijzigbaar ? 1 : 0,
						];
						break; // exit de foreach loop.
					}
				}
				$row_html .= "<tr style=\"background-color: $kleur\">";
				if ( $wijzigbaar ) {
					$row_html .= "<td><a class=\"kleistad_box\" data-form='" . wp_json_encode( $selectie ) . "' >$dag $dagnamen[$weekdag]</a></td>";
				} else {
					$row_html .= "<td>$dag $dagnamen[$weekdag]</td>";
				}
				$row_html .= "<td>{$selectie['wie']}</td>
                    <td>{$selectie['soortstook']}</td>
                    <td>{$selectie['temperatuur']}</td>
                </tr>";
			}
			$rows[] = $row_html;
		}

		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/kleistad-public-show-reservering.php';
		$html = ob_get_contents();
		ob_clean();

		return new WP_REST_response(
			[
				'html'    => $html,
				'oven_id' => $oven_id,
			]
		);
	}

	/**
	 *
	 * Callback from Ajax request
	 *
	 * @param WP_REST_Request $request Ajax request params.
	 * @return WP_REST_Response Ajax response.
	 */
	public static function callback_muteer( WP_REST_Request $request ) {
		$gebruiker_id = intval( $request->get_param( 'gebruiker_id' ) );
		$oven_id      = absint( intval( $request->get_param( 'oven_id' ) ) );

		$reservering           = new Kleistad_Reservering( $oven_id );
		$bestaande_reservering = $reservering->find(
			intval( $request->get_param( 'jaar' ) ), intval( $request->get_param( 'maand' ) ), intval( $request->get_param( 'dag' ) )
		);

		if ( $request->get_param( 'oven_id' ) > 0 ) {
			// het betreft een toevoeging of wijziging, check of er al niet een bestaande reservering is.
			if ( ! $bestaande_reservering || ( $reservering->gebruiker_id == $gebruiker_id ) || Kleistad_Roles::override() ) { // WPCS: loose comparison ok.
				$reservering->gebruiker_id = $gebruiker_id;
				$reservering->dag          = intval( $request->get_param( 'dag' ) );
				$reservering->maand        = intval( $request->get_param( 'maand' ) );
				$reservering->jaar         = intval( $request->get_param( 'jaar' ) );
				$reservering->temperatuur  = intval( $request->get_param( 'temperatuur' ) );
				$reservering->soortstook   = sanitize_text_field( $request->get_param( 'soortstook' ) );
				$reservering->programma    = intval( $request->get_param( 'programma' ) );
				$reservering->verdeling    = $request->get_param( 'verdeling' );
				$reservering->save();
			}
		} else {
			// het betreft een annulering, mag alleen verwijderd worden door de gebruiker of een bevoegde.
			if ( $bestaande_reservering && ( ( $reservering->gebruiker_id == $gebruiker_id ) || Kleistad_Roles::override() ) ) { // WPCS: loose comparison ok.
				$reservering->delete();
			}
		}
		$request->set_param( 'oven_id', $oven_id ); // zorg dat het over_id correct is.
		return self::callback_show( $request );
	}

}
