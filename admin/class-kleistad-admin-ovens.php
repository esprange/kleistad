<?php

/**
 * The admin-specific functionality for management of ovens of the plugin.
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

class Ovens_List_Table extends WP_List_Table
{
    /**
     * 
     */
    function __construct()
    {
        parent::__construct([
            'singular' => 'oven',
            'plural' => 'ovens',
        ]);
    }

    /**
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    /**
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_naam($item)
    {
        $actions = [
            'edit' => sprintf('<a href="?page=ovens_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
        ];

        return sprintf('%s %s',
            $item['naam'],
            $this->row_actions($actions)
        );
    }
    
    function column_beschikbaarheid($item)
    {
      $beschikbaarheid = json_decode($item['beschikbaarheid'], true);
      return implode(', ', $beschikbaarheid);
    }

    /**
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    /**
     *
     * @return array
     */
    function get_columns()
    {
        $columns = [
//            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'naam' => 'Naam',
            'kosten' => 'Tarief',
            'beschikbaarheid' => 'Beschikbaarheid',
            'id' => 'Id',
        ];
        return $columns;
    }

    /**
     *
     * @return array
     */
    function get_sortable_columns()
    {
        $sortable_columns = [
            'naam' => ['naam', true],
        ];
        return $sortable_columns;
    }


    /**
     *
     * It will get rows from database and prepare them to be showed in table
     */
    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kleistad_ovens'; // do not forget about tables prefix

        $per_page = 5; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = [$columns, $hidden, $sortable];

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'naam';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], [ 'asc', 'desc' ])) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
        $this->set_pagination_args([
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ]);
    }
}
