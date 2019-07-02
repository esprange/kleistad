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
	 * Register endpoint.
	 *
	 * @since 5.6.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Kleistad_Public::url(),
			'/email_workshop',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_email' ],
				'permission_callback' => function() {
					return true;
				},
			]
		);
	}

	/**
	 * Helper, geeft de url voor het endpoint terug.
	 */
	public static function endpoint() {
		return Kleistad_Public::base_url() . '/email_workshop';
	}

	/**
	 * Callback vanuit mailgun.
	 *
	 * @param WP_REST_Request $request Callback request params.
	 */
	public static function callback_email( WP_REST_Request $request ) {
		$casus   = null;
		$emailer = new Kleistad_Email();
		$params  = $request->get_params();
		$iparams = array_change_key_case( $params, CASE_LOWER );
		$tekst   = wp_kses_post( 'text/html' === $iparams['content-type'] ? $iparams['stripped-html'] : $iparams['stripped-text'] );

		/**
		 * Zoek eerst op basis van het case nummer in subject.
		 */
		if ( 2 !== sscanf( $iparams['subject'], '%*[^[WA#][WA#%u]', $casus_id ) ) {
			$casus = get_post( $casus_id );
			if ( is_null( $casus ) || self::POST_TYPE !== $casus->post_type ) {
				$casus_id = 0;
			}
		}
		/**
		 * Als niet gevonden probeer dan te zoeken op het email adres van de afzender.
		 */
		if ( ! $casus_id ) {
			$casussen = get_posts(
				[
					'post_type'   => self::POST_TYPE,
					'post_name'   => $iparams['from'],
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
		if ( $casus_id ) {
			$emailer->send(
				[
					'to'      => 'Workshop mailbox <info@' . Kleistad_Email::domein() . '>',
					'subject' => 'aanvraag workshop/kinderfeest',
					'content' => "<p>Er is een reactie ontvangen van {$iparams['from']}</p>",
				]
			);
			wp_update_post(
				[
					'ID'           => $casus_id,
					'post_status'  => 'vraag',
					'post_content' => self::communicatie(
						[
							'type'    => 'vraag',
							'from'    => $iparams['from'],
							'subject' => $iparams['subject'],
							'tekst'   => $tekst,
						]
					) . $casus->post_content,
				]
			);
		} else {
			$emailer->send(
				[
					'to'      => 'Workshop mailbox <info@' . Kleistad_Email::domein() . '>',
					'subject' => 'onbekende email',
					'content' => "<p>Er is een onbekende reactie ontvangen van {$iparams['from']}</p><p>{$iparams['stripped-text']}</p>",
				]
			);
		}
		exit(); // phpcs:ignore
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
