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

/**
 * Kleistad WorkshopAanvraag class.
 *
 * @since 5.6.0
 */
class Kleistad_WorkshopAanvraag {

	/**
	 * We maken gebruik van een custom post object
	 */
	const POST_TYPE = 'kleistad_workshopreq';

	/**
	 * Dit is de prefix van de verzender van emails
	 */
	const MBX = 'workshops';

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
	public function verwerk( $email ) {
		$casus   = null;
		$emailer = new Kleistad_Email();
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
					'to'      => 'Workshop mailbox <info@' . Kleistad_Email::domein() . '>',
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
		} else {
			return false;
		}
	}

	/**
	 * Ontvang en verwerk emails.
	 */
	public function ontvang_en_verwerk() {
		$options = Kleistad::get_options();
		$mailbox = new PhpImap\Mailbox(
			'{' . $options['imap_server'] . '}INBOX',
			self::MBX . '@' . Kleistad_Email::domein(),
			$options['imap_pwd']
		);
		$emailer = new Kleistad_Email();
		try {
			$email_ids = $mailbox->searchMailbox( 'UNANSWERED' );
		} catch ( PhpImap\Exceptions\ConnectionException $ex ) {
			error_log( "IMAP connection failed: $ex" ); // phpcs:ignore
			return;
		}
		if ( empty( $email_ids ) ) {
			return; // Geen berichten.
		}
		foreach ( $email_ids as $email_id ) {
			$email = $mailbox->getMail( $email_id );
			// phpcs:disable
			if ( $email->textHtml ) {
				$html = new \Html2Text\Html2Text( preg_replace( '/<!--\[if gte mso 9\]>.*<!\[endif\]-->/s', '', $email->textHtml ) );
				$body = nl2br( $html->getText() );
			} elseif ( $email->textPlain ) {
				$body = nl2br( $email->textPlain );
			} else {
				$body = '<p>bericht tekst kan niet worden weergegeven</p>';
			}
			// phpcs:enable
			if ( ! $this->verwerk(
				// phpcs:disable
				[
					'from-name'  => isset( $email->fromName ) ? sanitize_text_field( $email->fromName ) : sanitize_email( $email->fromAddress ),
					'from-email' => sanitize_email( $email->fromAddress ),
					'subject'    => sanitize_text_field( $email->subject ),
					'body'       => $body,
				]
				// phpcs:enable
			)
				) {
				$emailer->send(
					[
						'to'      => 'Workshop mailbox <info@' . Kleistad_Email::domein() . '>',
						'subject' => "niet te verwerken email over: {$email->subject}",
						'content' => '<p>Er is een onbekende reactie ontvangen op ' . self::MBX . '@' . Kleistad_Email::domein() . ' van ' . $email->fromAddress, // phpcs:ignore
					]
				);
			} else {
				$mailbox->setFlag( [ $email_id ], '\\Answered' );
			};
		}
	}

	/**
	 * Voeg de communicatie toe aan de ticket.
	 *
	 * @param string $content    Huidige content van de ticket.
	 * @param array  $parameters De parameters van de communicatie.
	 */
	private static function communicatie( $content, $parameters ) {
		if ( empty( $content ) ) {
			$correspondentie = [];
		} else {
			$correspondentie = unserialize( base64_decode( $content ) ); // phpcs:ignore
		}

		array_unshift(
			$correspondentie,
			array_merge(
				str_replace(
					[ '{', '}' ],
					[ '&#123', '&#125' ],
					$parameters
				),
				[ 'tijd' => date( 'd-m-Y H:i' ) ]
			)
		);
		return base64_encode( serialize( $correspondentie ) ); // phpcs:ignore
	}

	/**
	 * Start een nieuwe casus en email de aanvrager
	 *
	 * @param array $casus_data De gegevens behorende bij de casus.
	 */
	public static function start( $casus_data ) {
		$emailer = new Kleistad_Email();
		$result  = wp_insert_post(
			[
				'post_type'      => self::POST_TYPE,
				'post_title'     => $casus_data['contact'] . ' met vraag over ' . $casus_data['naam'],
				'post_name'      => $casus_data['email'],
				'post_excerpt'   => maybe_serialize(
					[
						'email'   => $casus_data['email'],
						'naam'    => $casus_data['naam'],
						'contact' => $casus_data['contact'],
						'omvang'  => $casus_data['omvang'],
						'periode' => $casus_data['periode'],
						'telnr'   => $casus_data['telnr'],
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
					'subject'    => sprintf( "[WA#%08d] Bevestiging {$casus_data['naam']} aanvraag", $result ),
					'from'       => self::MBX . '@' . Kleistad_Email::verzend_domein(),
					'reply-to'   => self::MBX . '@' . Kleistad_Email::domein(),
					'slug'       => 'kleistad_email_bevestiging_workshop_aanvraag',
					'parameters' => $casus_data,
				]
			);
			return true;
		}
		return false;
	}

	/**
	 * Voeg een reactie toe en email de aanvrager.
	 *
	 * @param int    $id Id van de aanvraag.
	 * @param string $reactie De reactie op de vraag.
	 */
	public static function reactie( $id, $reactie ) {
		$emailer       = new Kleistad_Email();
		$casus         = get_post( $id );
		$casus_details = maybe_unserialize( $casus->post_excerpt );
		$subject       = "[WA#$id] Reactie op {$casus_details['naam']} aanvraag";
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
				'from'       => self::MBX . '@' . Kleistad_Email::verzend_domein(),
				'reply-to'   => self::MBX . '@' . Kleistad_Email::domein(),
				'subject'    => $subject,
				'slug'       => 'kleistad_email_reactie_workshop_aanvraag',
				'auto'       => false,
				'parameters' => [
					'reactie' => $reactie,
					'contact' => $casus_details['contact'],
					'naam'    => $casus_details['naam'],
				],
			]
		);

	}
}
