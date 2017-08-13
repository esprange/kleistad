<?php

/**
 * The admin-specific functionality for management of stooksaldo of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */
if (!class_exists('WP_List_Table')) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Stooksaldo_List_Table extends WP_List_Table {

  /**
   * 
   */
  function __construct() {
    parent::__construct([
        'singular' => 'stooksaldo',
        'plural' => 'stooksaldi',
    ]);
  }

  /**
   *
   * @param $item - row (key, value array)
   * @param $column_name - string (key)
   * @return HTML
   */
  function column_default($item, $column_name) {
    return $item[$column_name];
  }

  /**
   *
   * @param $item - row (key, value array)
   * @return HTML
   */
  function column_naam($item) {
    $actions = [
        'edit' => sprintf('<a href="?page=stooksaldo_form&id=%s">%s</a>', $item['id'], 'Wijzigen'),
    ];

    return sprintf('%s %s', $item['naam'], $this->row_actions($actions)
    );
  }

  /**
   *
   * @param $item - row (key, value array)
   * @return HTML
   */
  function column_cb($item) {
    return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />', $item['id']
    );
  }

  /**
   *
   * @return array
   */
  function get_columns() {
    $columns = [
//        'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
        'naam' => 'Naam gebruiker',
        'saldo' => 'Saldo',
    ];
    return $columns;
  }

  /**
   *
   * @return array
   */
  function get_sortable_columns() {
    $sortable_columns = [
        'naam' => ['naam', true],
        'saldo' => ['saldo', false],
    ];
    return $sortable_columns;
  }

  /**
   *
   * It will get rows from database and prepare them to be showed in table
   */
  function prepare_items() {
    $per_page = 5; // constant, how much records will be shown per page

    $columns = $this->get_columns();
    $hidden = [];
    $sortable = $this->get_sortable_columns();

    // here we configure table headers, defined in our methods
    $this->_column_headers = [$columns, $hidden, $sortable];

    // prepare query params, as usual current page, order by and order direction
    $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
    $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'naam';
    $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], [ 'asc', 'desc' ])) ? $_REQUEST['order'] : 'asc';

    // will be used in pagination settings
    $gebruikers = get_users(
            ['fields' => ['id', 'display_name'], 'meta_key' => 'stooksaldo', 'orderby' => ['display_name'], 'order' => $order]);

    $stooksaldi = [];

    foreach ($gebruikers as $gebruiker) {
      $stooksaldi[] = [
          'id' => $gebruiker->id,
          'naam' => $gebruiker->display_name,
          'saldo' => get_user_meta($gebruiker->id, 'stooksaldo', true),
      ];
    }
    if ($orderby == 'naam') {
    } else { // oven_naam
      foreach ($stooksaldi as $key => $saldo) {
          $bedrag[$key] = $saldo['saldo'];
      }
      array_multisort($bedrag, constant('SORT_' . strtoupper($order)), $stooksaldi);
    }
    $total_items = count($stooksaldi);
    $this->items = array_slice($stooksaldi, $paged * $per_page, $per_page, true);
    $this->set_pagination_args([
        'total_items' => $total_items, // total items defined above
        'per_page' => $per_page, // per page constant defined at top of method
        'total_pages' => ceil($total_items / $per_page) // calculate pages count
    ]);
  }

}
