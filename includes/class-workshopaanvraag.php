<?php
/**
 * De definitie van de recept class
 *
 * @link       https://www.kleistad.nl
 * @since      5.6.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Html2Text;
use PhpImap;

/**
 * Kleistad WorkshopAanvraag class.
 *
 * @since 5.6.0
 */
class WorkshopAanvraag {

	/**
	 * We maken gebruik van een custom post object
	 */
	const POST_TYPE = 'kleistad_workshopreq';

	/**
	 * Initialiseer de aanvragen als custom post type.
	 */
	public static function create_type() {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'            => [
					'name'               => 'Workshop aanvragen',
					'singular_name'      => 'Workshop aanvraag',
					'add_new'            => 'Toevoegen',
					'add_new_item'       => 'Aanvraag toevoegen',
					'edit'               => 'Wijzigen',
					'edit_item'          => 'Aanvraag wijzigen',
					'view'               => 'Inzien',
					'view_item'          => 'Aanvraag inzien',
					'search_items'       => 'Aanvraag zoeken',
					'not_found'          => 'Niet gevonden',
					'not_found_in_trash' => 'Niet in prullenbak gevonden',
				],
				'public'            => true,
				'supports'          => [
					'title',
					'comments',
					'thumbnail',
				],
				'rewrite'           => [
					'slug' => 'workshopaanvragen',
				],
				'show_ui'           => false,
				'show_in_admin_bar' => false,
				'show_in_nav_menus' => false,
			]
		);
		register_post_status(
			'nieuw',
			[
				'label'     => 'nieuwe aanvraag',
				'post_type' => self::POST_TYPE,
				'public'    => true,
			]
		);
		register_post_status(
			'gereageerd',
			[
				'label'     => 'gereageerd naar aanvrager',
				'post_type' => self::POST_TYPE,
				'public'    => true,
			]
		);
		register_post_status(
			'vraag',
			[
				'label'     => 'aanvrager heeft nieuwe vraag gesteld',
				'post_type' => self::POST_TYPE,
				'public'    => true,
			]
		);
		register_post_status(
			'gepland',
			[
				'label'     => 'de workshop is ingepland',
				'post_type' => self::POST_TYPE,
				'public'    => true,
			]
		);
	}

	/**
	 * Verwerk een ontvangen email.
	 *
	 * @param array $email De ontvangen email.
	 * @return bool True als verwerkt.
	 */
	private static function verwerk( $email ) {
		$casus   = null;
		$emailer = new Email();
		/**
		* Zoek eerst op basis van het case nummer in subject.
		*/
		if ( 2 === sscanf( $email['subject'], '%*[^[WA#][WA#%u]', $casus_id ) ) {
			$casus = get_post( $casus_id );
		} else {
			/**
			* Als niet gevonden probeer dan te zoeken op het email adres van de afzender.
			*/
			$casussen = get_posts(
				[
					'post_type'   => self::POST_TYPE,
					'post_name'   => $email['from-email'],
					'numberposts' => '1',
					'orderby'     => 'date',
					'order'       => 'DESC',
				]
			);
			if ( count( $casussen ) ) {
				$casus    = $casussen[0];
				$casus_id = $casus->ID;
			}
		}
		if ( is_object( $casus ) && self::POST_TYPE === $casus->post_type ) {
			$emailer->send(
				[
					'to'      => "Workshop mailbox <{$emailer->info}{$emailer->domein}>",
					'subject' => 'aanvraag workshop/kinderfeest',
					'content' => '<p>Er is een reactie ontvangen van ' . $email['from-name'] . '</p>',
					'sign'    => 'Workshop mailbox',
				]
			);
			wp_update_post(
				[
					'ID'           => $casus_id,
					'post_status'  => 'vraag',
					'post_content' => self::communicatie(
						$casus->post_content,
						[
							'type'    => 'vraag',
							'from'    => $email['from-name'],
							'subject' => $email['subject'],
							'tekst'   => $email['body'],
						]
					),
				]
			);
			return true;
		}
		return false;
	}

	/**
	 * Ontvang en verwerk emails.
	 */
	public static function ontvang_en_verwerk() {
		// phpcs:disable WordPress.NamingConventions
		$setup = setup();
		if ( empty( $setup['imap_server'] ) || empty( $setup['imap_pwd'] ) ) {
			die();
		}
		$answered = [];
		$emailer  = new Email();
		$mailbox  = new PhpImap\Mailbox(
			'{' . $setup['imap_server'] . '}INBOX',
			self::mbx() . $emailer->domein,
			$setup['imap_pwd']
		);
		try {
			$email_ids = $mailbox->searchMailbox( 'UNANSWERED' );
			foreach ( $email_ids as $email_id ) {
				$email = $mailbox->getMail( $email_id );
				if ( $email->textHtml ) {
					$html = new Html2Text\Html2Text( preg_replace( '/<!--\[if gte mso 9\]>.*<!\[endif\]-->/s', '', $email->textHtml ) );
					$body = $html->getText();
					if ( '' === $body ) {
						$body = $email->textPlain;
					}
				} elseif ( $email->textPlain ) {
					$body = $email->textPlain;
				} else {
					$body = '<p>bericht tekst kan niet worden weergegeven</p>';
				}
				if ( ! self::verwerk(
					[
						'from-name'  => isset( $email->fromName ) ? sanitize_text_field( $email->fromName ) : sanitize_email( $email->fromAddress ),
						'from-email' => sanitize_email( $email->fromAddress ),
						'subject'    => sanitize_text_field( $email->subject ),
						'body'       => sanitize_textarea_field( $body ),
					]
				) && 'production' === wp_get_environment_type() ) {
					$emailer->send(
						[
							'to'        => "Kleistad <{$emailer->info}{$emailer->domein}>",
							'from-name' => isset( $email->fromName ) ? sanitize_text_field( $email->fromName ) : sanitize_email( $email->fromAddress ),
							'from'      => sanitize_email( $email->fromAddress ),
							'subject'   => 'FW:' . sanitize_text_field( $email->subject ),
							'content'   => sanitize_textarea_field( $body ),
						]
					);
				}
				$answered[] = $email_id;
			}
			if ( ! empty( $answered ) ) {
				$mailbox->setFlag( $answered, '\\Answered' );
			}
			$mailbox->disconnect();
		} catch ( PhpImap\Exceptions\ConnectionException $ex ) {
			error_log( 'IMAP fail: ' . $ex->getMessage() ); // phpcs:ignore
			die();
		}
	// phpcs:enable
	}

	/**
	 * Voeg de communicatie toe aan de ticket.
	 *
	 * @param string $content    Huidige content van de ticket.
	 * @param array  $parameters De parameters van de communicatie.
	 */
	private static function communicatie( $content, $parameters ) {
		$correspondentie = empty( $content ) ? [] : unserialize( base64_decode( $content ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		array_unshift(
			$correspondentie,
			array_merge(
				str_replace(
					[ '{', '}' ],
					[ '&#123', '&#125' ],
					$parameters
				),
				[ 'tijd' => current_time( 'd-m-Y H:i' ) ]
			)
		);
		return base64_encode( serialize( $correspondentie ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Start een nieuwe casus en email de aanvrager
	 *
	 * @param array $casus_data De gegevens behorende bij de casus.
	 */
	public static function start( $casus_data ) {
		$emailer = new Email();
		$result  = wp_insert_post(
			[
				'post_type'      => self::POST_TYPE,
				'post_title'     => $casus_data['contact'] . ' met vraag over ' . $casus_data['naam'],
				'post_name'      => $casus_data['email'],
				'post_excerpt'   => maybe_serialize(
					[
						'email'       => $casus_data['email'],
						'naam'        => $casus_data['naam'],
						'contact'     => $casus_data['contact'],
						'omvang'      => $casus_data['omvang'],
						'periode'     => $casus_data['periode'],
						'telnr'       => $casus_data['telnr'],
						'workshop_id' => 0,
					]
				),
				'post_status'    => 'nieuw',
				'comment_status' => 'closed',
				'post_content'   => self::communicatie(
					'',
					[
						'tekst'   => $casus_data['vraag'],
						'type'    => 'aanvraag',
						'from'    => $casus_data['naam'],
						'subject' => '',
					]
				),
			]
		);
		if ( is_int( $result ) ) {
			$emailer->send(
				[
					'to'         => "{$casus_data['contact']} <{$casus_data['email']}>",
					'subject'    => sprintf( "[WA#%08d] Bevestiging {$casus_data['naam']} vraag", $result ),
					'from'       => self::mbx() . $emailer->verzend_domein,
					'reply-to'   => self::mbx() . $emailer->domein,
					'slug'       => 'workshop_aanvraag_bevestiging',
					'parameters' => $casus_data,
					'sign_email' => false,
					'auto'       => 'reply',
				]
			);
			return true;
		}
		return false;
	}

	/**
	 * Verander de status van de casus naar gepland.
	 *
	 * @param int $casus_id    De id van de casus.
	 * @param int $workshop_id De id van de workshop.
	 */
	public static function gepland( $casus_id, $workshop_id ) {
		if ( $casus_id ) {
			$casus                        = get_post( $casus_id );
			$casus_details                = unserialize( $casus->post_excerpt ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			$casus_details['workshop_id'] = $workshop_id;
			wp_update_post(
				[
					'ID'           => $casus_id,
					'post_status'  => $workshop_id ? 'gepland' : 'gereageerd',
					'post_excerpt' => maybe_serialize( $casus_details ),
				]
			);
		}
	}

	/**
	 * Voeg een reactie toe en email de aanvrager.
	 *
	 * @param int    $id Id van de aanvraag.
	 * @param string $reactie De reactie op de vraag.
	 */
	public static function reactie( $id, $reactie ) {
		$emailer       = new Email();
		$casus         = get_post( $id );
		$casus_details = maybe_unserialize( $casus->post_excerpt );
		$subject       = sprintf( "[WA#%08d] Reactie op {$casus_details['naam']} vraag", $id );
		wp_update_post(
			[
				'ID'           => $id,
				'post_status'  => 'gereageerd',
				'post_content' => self::communicatie(
					$casus->post_content,
					[
						'type'    => 'reactie',
						'from'    => wp_get_current_user()->display_name,
						'tekst'   => $reactie,
						'subject' => $subject,
					]
				),
			]
		);
		$emailer->send(
			[
				'to'         => "{$casus_details['contact']}  <{$casus_details['email']}>",
				'from'       => self::mbx() . $emailer->verzend_domein,
				'sign'       => wp_get_current_user()->display_name . ',<br/>Kleistad',
				'reply-to'   => self::mbx() . $emailer->domein,
				'subject'    => $subject,
				'slug'       => 'workshop_aanvraag_reactie',
				'auto'       => false,
				'parameters' => [
					'reactie' => nl2br( $reactie ),
					'contact' => $casus_details['contact'],
					'naam'    => $casus_details['naam'],
				],
				'sign_email' => false,
			]
		);

	}

	/**
	 * Geef het begin van de email aan.
	 *
	 * @return string
	 */
	private static function mbx() {
		return ( 'production' === wp_get_environment_type() ) ? 'workshops@' : ( strtok( get_bloginfo( 'admin_email' ), '@' ) . '@' );
	}
}
