<?php
/** De admin filters van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

use WP_Post;
use WP_Query;

/**
 * De admin-specifieke filters van de plugin.
 */
class Admin_Filters {

	/**
	 * Filter de acties voor een email post.
	 *
	 * @param array   $acties De acties.
	 * @param WP_Post $post   De post.
	 *
	 * @internal Filter for post_row_actions.
	 */
	public function post_row_actions( array $acties, WP_Post $post ) : array {
		if ( Email::POST_TYPE === $post->post_type ) {
			unset( $acties['view'] );
			unset( $acties['inline hide-if-no-js'] );
		}
		return $acties;
	}

	/**
	 * Voeg een header label toe voor de email templates.
	 *
	 * @param array $columns De bestaande labels.
	 * @return array
	 *
	 * @internal Filter for manage_kleistad_email_posts_columns.
	 */
	public function email_posts_columns( array $columns ) :array {
		unset( $columns['date'] );
		return array_merge( $columns, [ 'wijziging' => 'Datum' ] );
	}

	/**
	 * Geef aan dat de wijziging column ook sorteerbaar is.
	 *
	 * @param array $columns De labels.
	 * @return array
	 *
	 * @internal Filter for manage_edit-kleistad_email_sortable_columns.
	 */
	public function email_sortable_columns( array $columns ) : array {
		return array_merge( $columns, [ 'wijziging' => 'wijziging' ] );
	}

	/**
	 * Zorg dat er gesorteerd wordt op wijzig datum.
	 *
	 * @param WP_Query $wp_query De query.
	 *
	 * @internal Filter for pre_get_posts.
	 */
	public function email_get_posts_order( WP_Query $wp_query ) {
		if ( isset( $wp_query->query['post_type'] ) && Email::POST_TYPE === $wp_query->query['post_type'] ) {
			$wp_query->set( 'orderby', 'modified' );
		}
	}

	/**
	 * Registreer de exporter van privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param array $exporters De exporters die WP aanroept bij het genereren van de zip file.
	 *
	 * @internal Filter for wp_privacy_personal_data_exporters.
	 */
	public function register_exporter( array $exporters ) : array {
		$gdpr                  = new Admin_GDPR();
		$exporters['kleistad'] = [
			'exporter_friendly_name' => 'plugin folder Kleistad',
			'callback'               => [ $gdpr, 'exporter' ],
		];
		return $exporters;
	}

	/**
	 * Registreer de eraser van privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param array $erasers De erasers die WP aanroept bij het verwijderen persoonlijke data.
	 *
	 * @internal Filter for wp_privacy_personal_data_erasers.
	 */
	public function register_eraser( array $erasers ) : array {
		$gdpr                = new Admin_GDPR();
		$erasers['kleistad'] = [
			'eraser_friendly_name' => 'Kleistad',
			'callback'             => [ $gdpr, 'eraser' ],
		];
		return $erasers;
	}

	/**
	 * Auto update van de plugin via het administrator board.
	 *
	 * @since 4.3.8
	 *
	 * @param  object $transient Het object waarin WP de updates deelt.
	 * @return object De transient.
	 *
	 * @internal Filter for pre_set_site_transient_update_plugins.
	 */
	public function check_update( object $transient ) : object {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		$update = new Admin_Update();
		$obj    = $update->get_remote( 'version' );
		if ( false === $obj ) {
			return $transient;
		}
		if ( version_compare( versie(), $obj->new_version, '<' ) ) {
			$transient->response[ $obj->plugin ] = $obj;
			return $transient;
		}
		$transient->no_update[ $obj->plugin ] = $obj;
		return $transient;
	}

	/**
	 * Haal informatie op, aangeroepen vanuit API plugin hook.
	 *
	 * @since 4.3.8
	 *
	 * @param  object|bool $obj    Wordt niet gebruikt.
	 * @param  string      $action De gevraagde actie.
	 * @param  object|null $arg    Argument door WP ingevuld.
	 * @return bool|object
	 *
	 * @internal Filter for plugins_api.
	 */
	public function check_info( $obj, string $action = '', ?object $arg = null ) {
		if ( ( 'query_plugins' === $action || 'plugin_information' === $action ) && isset( $arg->slug ) && 'kleistad' === $arg->slug ) {
			$plugin_info  = get_site_transient( 'update_plugins' );
			$arg->version = $plugin_info->checked['kleistad/kleistad.php'];
			$update       = new Admin_Update();
			$info         = $update->get_remote( 'info' );
			if ( false !== $info ) {
				return $info;
			}
		}
		return $obj;
	}

}
