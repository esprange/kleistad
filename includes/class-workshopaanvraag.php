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
	 * Het email object
	 *
	 * @var Email $emailer Het emailer object.
	 */
	private Email $emailer;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->emailer = new Email();
	}

	/**
	 * Initialiseer de aanvragen als custom post type.
	 */
	public static function create_type() {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
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
				'public'              => true,
				'supports'            => [
					'title',
					'comments',
					'thumbnail',
				],
				'rewrite'             => [
					'slug' => 'workshopaanvragen',
				],
				'show_ui'             => false,
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'exclude_from_search' => true,
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
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public function verwerk( array $email ) {
		$casus = null;
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
					'post_name'   => $email['from'],
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
			$this->emailer->send(
				[
					'to'      => "Workshop mailbox <{$this->emailer->info}{$this->emailer->domein}>",
					'subject' => 'aanvraag workshop/kinderfeest',
					'content' => "<p>Er is een reactie ontvangen van {$email['from-name']}</p>",
					'sign'    => 'Workshop mailbox',
				]
			);
			wp_update_post(
				[
					'ID'           => $casus_id,
					'post_status'  => 'vraag',
					'post_content' => $this->communicatie(
						$casus->post_content,
						[
							'type'    => 'vraag',
							'from'    => $email['from-name'],
							'subject' => $email['subject'],
							'tekst'   => $email['content'],
						]
					),
				]
			);
			return;
		}
		$email['to'] = "Kleistad <{$this->emailer->info}{$this->emailer->domein}>";
		$this->emailer->send( $email );
	}

	/**
	 * Voeg de communicatie toe aan de ticket.
	 *
	 * @param string $content    Huidige content van de ticket.
	 * @param array  $parameters De parameters van de communicatie.
	 * @return string
	 */
	private function communicatie( string $content, array $parameters ) : string {
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
	 * @return bool
	 */
	public function start( array $casus_data ) : bool {
		$result = wp_insert_post(
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
				'post_content'   => $this->communicatie(
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
			$this->emailer->send(
				[
					'to'         => "{$casus_data['contact']} <{$casus_data['email']}>",
					'subject'    => sprintf( "[WA#%08d] Bevestiging {$casus_data['naam']} vraag", $result ),
					'from'       => $this->mbx( true ),
					'reply-to'   => $this->mbx(),
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
	public function gepland( int $casus_id, int $workshop_id ) {
		if ( $casus_id ) {
			$casus                        = get_post( $casus_id );
			$casus_details                = maybe_unserialize( $casus->post_excerpt );
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
	 * @param int    $aanvraag_id Id van de aanvraag.
	 * @param string $reactie     De reactie op de vraag.
	 */
	public function reactie( int $aanvraag_id, string $reactie ) {
		$casus         = get_post( $aanvraag_id );
		$casus_details = maybe_unserialize( $casus->post_excerpt );
		$subject       = sprintf( "[WA#%08d] Reactie op {$casus_details['naam']} vraag", $aanvraag_id );
		wp_update_post(
			[
				'ID'           => $aanvraag_id,
				'post_status'  => 'gereageerd',
				'post_content' => $this->communicatie(
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
		$this->emailer->send(
			[
				'to'         => "{$casus_details['contact']}  <{$casus_details['email']}>",
				'from'       => $this->mbx( true ),
				'sign'       => wp_get_current_user()->display_name . ',<br/>Kleistad',
				'reply-to'   => $this->mbx(),
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
	 * @param  bool $verzenden Of het de verzend mailbox is.
	 * @return string
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function mbx( bool $verzenden = false ) : string {
		$prefix = ( 'production' === wp_get_environment_type() ) ? 'workshops@' : ( strtok( get_bloginfo( 'admin_email' ), '@' ) . '@' );
		if ( $verzenden ) {
			return $prefix . $this->emailer->verzend_domein;
		}
		return $prefix . $this->emailer->domein;
	}
}
