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
	const MBX = 'workshop';

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
	 */
	public function verwerk( $email ) {
		$casus   = null;
		$emailer = new Kleistad_Email();

		/**
		* Zoek eerst op basis van het case nummer in subject.
		*/
		if ( 2 !== sscanf( $email['subject'], '%*[^[WA#][WA#%u]', $casus_id ) ) {
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
					'content' => "<p>Er is een reactie ontvangen van {$email['from-name']}</p>",
				]
			);
			wp_update_post(
				[
					'ID'           => $casus_id,
					'post_status'  => 'vraag',
					'post_content' => self::communicatie(
						[
							'type'    => 'vraag',
							'from'    => $email['from-name'],
							'subject' => $email['subject'],
							'tekst'   => $email['body'],
						]
					) . $casus->post_content,
				]
			);
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
			'workshops@' . Kleistad_Email::domein(),
			$options['imap_pwd']
		);
		$emailer = new Kleistad_Email();
		try {
			$email_ids = $mailbox->searchMailbox( 'UNSEEN' );
		} catch ( PhpImap\Exceptions\ConnectionException $ex ) {
			error_log( "IMAP connection failed: $ex" ); // phpcs:ignore
			return;
		}
		if ( ! $email_ids ) {
			error_log( 'geen berichten' );
			return; // Geen berichten.
		}

		foreach ( $email_ids as $email_id ) {
			$email = $mailbox->getMail( $email_id );
			if ( ! $this->verwerk(
				// phpcs:disable
				[
					'from-name'  => isset( $email->fromName ) ? $email->fromName : $email->fromAddress,
					'from-email' => $email->fromAddress,
					'subject'    => $email->subject,
					'body'       => $email->textHtml ? $email->textHtml : $email->textPlain,
				]
				// phpcs:enable
			)
				) {
				$emailer->send(
					[
						'to'      => 'Workshop mailbox <info@' . Kleistad_Email::domein() . '>',
						'subject' => "niet te verwerken email over: {$email->subject}",
						'content' => '<p>Er is een onbekende reactie ontvangen op workshops@' . Kleistad_Email::domein() . ' van ' . $email->fromAddress, // phpcs:ignore
					]
				);
			};
		}
	}

	/**
	 * Voeg de communicatie toe aan de ticket.
	 *
	 * @param array $parameters De parameters van de communicatie.
	 */
	public static function communicatie( $parameters ) {
		$nu = date( 'd-m-Y H:i' );
		switch ( $parameters['type'] ) {
			case 'aanvraag':
				return "<div class=\"kleistad_workshop_aanvraag\"><p>ontvangen op : $nu </p><p>{$parameters['tekst']}</p></div>";
			case 'vraag':
				return "<div class=\"kleistad_workshop_vraag\"><p>ontvangen van: {$parameters['from']} op: $nu met onderwerp:{$parameters['subject']}</p><p>{$parameters['tekst']}</p><hr></div>";
			case 'reactie':
				return "<div class=\"kleistad_workshop_reactie\"><p>verzonden door: {$parameters['from']} op: $nu</p><p>{$parameters['tekst']}</p><hr></div>";
		}
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
					[
						'tekst' => $casus_data['vraag'],
						'type'  => 'aanvraag',
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
					'reply-to'   => self::MBX . '@' . Kleistad_Email::verzend_domein(),
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
		$casus_content = self::communicatie(
			[
				'type'  => 'reactie',
				'from'  => wp_get_current_user()->display_name,
				'tekst' => $reactie,
			]
		) . $casus->post_content;
		wp_update_post(
			[
				'ID'           => $id,
				'post_status'  => 'gereageerd',
				'post_content' => $casus_content,
			]
		);
		$emailer->send(
			[
				'to'         => "{$casus_details['contact']}  <{$casus_details['email']}>",
				'from'       => self::MBX . '@',
				Kleistad_Email::verzend_domein(),
				'reply-to'   => self::MBX . '@',
				Kleistad_Email::verzend_domein(),
				'subject'    => "[WA#$id] Reactie op {$casus_details['naam']} aanvraag",
				'slug'       => 'kleistad_email_reactie_workshop_aanvraag',
				'parameters' => [
					'reactie' => $casus_content,
				],
			]
		);

	}
}
