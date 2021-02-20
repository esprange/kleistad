<?php
/**
 * Definitie van de publieke class van de plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_User;

/**
 * De kleistad class voor de publieke filters.
 */
class Public_Filters {

	/**
	 * Wordt aangeroepen door filter single_template, zorgt dat WP de juiste template file toont.
	 *
	 * @since 4.1.0
	 *
	 * @param string $single_template het template path.
	 * @return string
	 *
	 * @internal Filter for single_template.
	 */
	public function single_template( $single_template ) {
		global $post;

		if ( false !== strpos( $post->post_type, 'kleistad_' ) ) {
			$object          = substr( $post->post_type, strlen( 'kleistad_' ) );
			$single_template = dirname( __FILE__ ) . "/partials/public-single-$object.php";
		}
		return $single_template;
	}

	/**
	 * Wordt aangeroepen door filter comments_template, zorgt dat WP de juiste template file toont.
	 *
	 * @since 4.1.0
	 *
	 * @param string $comments_template het template path.
	 * @return string
	 *
	 * @internal Filter for comments_template.
	 */
	public function comments_template( $comments_template ) {
		global $post;

		if ( false !== strpos( $post->post_type, 'kleistad_' ) ) {
			$object            = substr( $post->post_type, strlen( 'kleistad_' ) );
			$comments_template = dirname( __FILE__ ) . "/partials/public-comments-$object.php";
		}
		return $comments_template;
	}

	/**
	 * Wordt aangeroepen door filter comment form default fields, om niet te vragen naar een website url.
	 *
	 * @since 4.1.0
	 *
	 * @param array $fields De commentaar velden.
	 * @return array
	 *
	 * @internal Filter for comment_form_default_fields.
	 */
	public function comment_fields( $fields ) {
		if ( isset( $fields['url'] ) ) {
			unset( $fields['url'] );
		}
		return $fields;
	}

	/**
	 * Wordt aangeroepen door filter email_change_email, als er een email adres gewijzigd wordt.
	 *
	 * @param array $email_change_email Basis voor WP_mail.
	 * @param array $user               De bestaande user info.
	 * @param array $userdata           De gewijzigd user info.
	 *
	 * @internal Filter for email_change_email.
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 * phpcs:disable
	 */
	public function email_change_email( /** @scrutinizer ignore-unused */ $email_change_email, $user, $userdata ) {
		// phpcs:enable
		$emailer = new Email();
		return $emailer->notify(
			[
				'slug'       => 'email_wijziging',
				'to'         => $userdata['user_email'],
				'cc'         => [ $user['user_email'] ],
				'subject'    => 'Wijziging email adres',
				'parameters' => [
					'voornaam'   => $userdata['first_name'],
					'achternaam' => $userdata['last_name'],
					'email'      => $userdata['user_email'],
				],
			]
		);
	}

	/**
	 * Wordt aangeroepen door filter password_change_email, als het wachtwoord gewijzigd wordt.
	 *
	 * @param array $email_change_email Basis voor WP_mail.
	 * @param array $user               De bestaande user info.
	 * @param array $userdata           De gewijzigd user info.
	 *
	 * @internal Filter for password_change_email.
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 * phpcs:disable
	 */
	public function password_change_email( /** @scrutinizer ignore-unused */ $email_change_email, /** @scrutinizer ignore-unused */ $user, $userdata ) {
		// phpcs:enable
		$emailer = new Email();
		return $emailer->notify(
			[
				'to'         => $userdata['user_email'],
				'subject'    => 'Wachtwoord gewijzigd',
				'slug'       => 'wachtwoord_wijziging',
				'parameters' => [
					'voornaam'   => $userdata['first_name'],
					'achternaam' => $userdata['last_name'],
				],
			]
		);
	}

	/**
	 * Wordt aangeroepen door filter retrieve_password_message, als er een wachtwoord reset gevraagd wordt.
	 *
	 * @param string  $message    Het bericht.
	 * @param string  $key        De reset sleutel.
	 * @param string  $user_login De gebruiker login naam.
	 * @param WP_User $user_data  Het user record van de gebruiker.
	 *
	 * @internal Filter for retrieve_password_message.
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 * phpcs:disable
	 */
	public function retrieve_password_message( /** @scrutinizer ignore-unused */ $message, $key, $user_login = '', $user_data = '' ) {
		// phpcs:enable
		$emailer = new Email();
		$result  = $emailer->notify(
			[
				'slug'       => 'wachtwoord_reset',
				'to'         => $user_data->user_email,
				'subject'    => 'Wachtwoord reset',
				'parameters' => [
					'voornaam'   => $user_data->first_name,
					'achternaam' => $user_data->last_name,
					'reset_link' => '<a href="' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . '" >wachtwoord reset pagina</a>',
				],
			]
		);
		return $result['message'];
	}

	/**
	 * Password hint
	 *
	 * @return string
	 *
	 * @internal Filter for password_hint.
	 */
	public function password_hint() {
		return "Hint: het wachtwoord moet minimaal 9 tekens lang zijn. Bij de invoer wordt gecontroleerd op te gemakkelijk te bedenken wachtwoorden (als 1234...).\nGebruik hoofd- en kleine letters, nummers en tekens zoals ! \" ? $ % ^ & ) om het wachtwoord sterker te maken.";
	}

	/**
	 * Uitbreiding WP_User object met adres gegevens
	 *
	 * @since 4.5.1
	 *
	 * @param array $user_contact_method De extra velden met adresgegevens.
	 * @return array de extra velden.
	 *
	 * @internal Filter for user_contactmethods.
	 */
	public function user_contact_methods( $user_contact_method ) {

		$user_contact_method['telnr']  = 'Telefoon nummer';
		$user_contact_method['straat'] = 'Straat';
		$user_contact_method['huisnr'] = 'Nummer';
		$user_contact_method['pcode']  = 'Postcode';
		$user_contact_method['plaats'] = 'Plaats';

		return $user_contact_method;
	}

	/**
	 * Pas de template aan ingeval van de pagina voor de ideal betaal link.
	 *
	 * @param string $template De locatie van de template file.
	 *
	 * @internal Filter for template_include.
	 */
	public function template_include( $template ) {
		global $pagename;
		if ( isset( $pagename ) && preg_match( '~(kleistad-betaling|kleistad-extra_cursisten)~', $pagename ) ) {
			return dirname( __FILE__ ) . '/partials/public-basispagina.php';
		}
		return $template;
	}

}
