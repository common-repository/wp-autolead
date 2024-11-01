<?php

class ClsAutoLeadList extends WP_List_Table
{

    protected $list_id;

    public function __construct()
    {
        parent::__construct( array(
            'singular' => 'autolead_subscriber',
            'plural' => 'autolead_subscribers',
            'ajax' => false
        ) );
    }



    public function get_columns()
    {
        return $columns = array(
            'cb' => '<input type="checkbox" />',
            'autolead_post_id' => __( 'Survey Name', 'autolead' ),
            'name' => __( 'Name', 'autolead' ),
            'email' => __( 'Email', 'autolead' ),
            'submit_date' => __( 'Submit Date', 'autolead' ),
            'ip' => __( 'IP Address', 'autolead' ),
            'details' => __( 'Survey Details' )
        );
    }



    public function get_sortable_columns()
    {
        return $sortable = array(
            'autolead_post_id' => array( 'autolead_post_id', TRUE ),
            'name' => array( 'name', FALSE ),
            'email' => array( 'email', FALSE ),
            'submit_date' => array( 'submit_date', false )
        );
    }



    public function column_cb( $item )
    {
        return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->id );
    }



    public function column_default( $item, $column_name )
    {
        switch ( $column_name )
        {
            case 'autolead_post_id':
                return get_the_title( $item->autolead_post_id );
                break;

            case 'name':
                return $item->name;
                break;

            case 'email':
                return $item->email;
                break;

            case 'ip':
                return $item->ip;
                break;

            case 'submit_date':
                $submitted_date = new DateTime( $item->submit_date, new DateTimeZone( 'GMT' ) );
                return date_i18n( get_option( 'date_format' ), $submitted_date->getTimestamp() );
                break;

            case 'details':
                global $wpdb;
                $meta_table = $wpdb->prefix . 'autolead_submits_meta';
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $meta_table WHERE autolead_post_id = %d", $item->id ) );
                foreach ( $results as $result )
                {
                    $output .= '<tr><td>' . $result->meta_key . '</td><td>' . $result->meta_value . '</td></tr>';
                }

                $output = '<table class="table table-bordered table-responsive table-striped"><tbody>' . $output . '</tbody></table>';
                $modal .= '<div class="modal fade" id="autolead-modal-' . $item->id . '" tabindex="-1" role="dialog" aria-hidden="true">';
                $modal .= '<div class="modal-dialog">';
                $modal .= '<div class="modal-content">';
                $modal .= '<div class="modal-header">';
                $modal .= '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
                $modal .= '<h4 class="modal-title">Survey Result</h4>';
                $modal .= '</div>';
                $modal .= '<div class="modal-body">';
                $modal .= $output;
                $modal .= '</div>';
                $modal .= '</div><!-- /.modal-content -->';
                $modal .= '</div><!-- /.modal-dialog -->';
                $modal .= '</div><!-- /.modal -->';
                return '<a data-toggle="modal" href="#autolead-modal-' . $item->id . '" class="view-survey-results">View Results</a>' . $modal;
                break;

            default:
                return print_r( $item, true );
                break;
        }
    }



    public function get_bulk_actions()
    {
        $actions = array( 'delete_contacts' => 'Remove from list' );
        return $actions;
    }



    public function process_bulk_action()
    {
        $current_action = $this->current_action();

        // get all post variables inside an array by filtering
        $data = filter_input_array( INPUT_POST, array(
            $this->_args['singular'] => array( 'filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY )
                ) );

        $subscribers = $data[$this->_args['singular']];

        if ( !$current_action || !$subscribers )
        {
            return;
        }

        check_admin_referer( 'bulk-' . $this->_args['plural'] );
        global $wpdb;
        $list_table = $wpdb->prefix . 'automail_list';

        switch ( $current_action )
        {
            case 'delete_contacts':
                foreach ( $subscribers as $subscriber )
                {
                    $wpdb->delete( $list_table, array( 'id' => $subscriber ), array( '%d' ) );
                }
                $msg_data = array( 'action' => 'contacts_deleted' );
                update_option( 'automail_admin_messages', $msg_data );
                break;

            default:
                break;
        }
    }



    public function prepare_items()
    {
        global $wpdb;
        $screen = get_current_screen();
        $lists_per_page = $this->get_items_per_page( 'edit_per_page' );
        $columns = $this->get_columns();
        $hidden_columns = array( );
        $sortable_columns = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden_columns, $sortable_columns );
        $paged = $this->get_pagenum();
        $search = isset( $_REQUEST['s'] ) ? trim( filter_var( $_REQUEST['s'], FILTER_SANITIZE_STRING ) ) : '';
        $orderby = filter_input( INPUT_GET, 'orderby' );
        $orderby = $orderby ? $orderby : 'id';

        // Handle all the bulk actions inside this class itself
        $this->process_bulk_action();

        $args = array(
            'posts_per_page' => $lists_per_page,
            'offset' => ( $paged - 1 ) * $lists_per_page,
            'orderby' => $orderby,
            'order' => ( $order = filter_input( INPUT_GET, 'order' ) ) ? $order : 'DESC',
            'search' => $search
        );

        $list_object = new ClsAutoLeadSubscribersQuery( $args );
        $list_table = $wpdb->prefix . 'autolead_submits';
        $list_object->set_fields( "*" );
        $list_object->set_from( "$list_table" );
        $this->list_id ? $list_object->set_where( "id = {$this->id}" ) : '';
        $list_object->set_orderby( $orderby );
        $list_object->start_process();
        $this->items = $list_object->get_results();
        $total_items = $list_object->get_total();

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page' => $lists_per_page
        ) );
    }



    /**
     * extra control between the bulk actions and pagination
     * @access public
     * @param $which string whether the controls are at the bottom or top
     * check the parent::display_tablenav function which calls the $which
     * variable
     */
    public function extra_tablenav_bak( $which )
    {
        $two = $which == 'top' ? '' : '2';

        if ( 'top' == $which )
        {
            $list_id = filter_var( $_REQUEST['list_filter'], FILTER_VALIDATE_INT );
            $args = array(
                'orderby' => 'post_date',
                'order' => 'DESC',
                'post_status' => 'publish',
                'post_type' => ClsAutoMail::PLUGIN_CPT,
                'numberposts' => 0
            );

            $cptposts = get_posts( $args );
            echo '<div class="alignleft actions" style="padding: 3px 8px 0 0;">';
            echo '<select name="list_filter">';
            echo '<option value="0">' . __( 'Please Select', 'autolead' ) . '</option>';
            foreach ( $cptposts as $cptpost )
            {
                echo '<option value="' . $cptpost->ID . '"' . selected( $cptpost->ID, $list_id ) . '>' . get_the_title( $cptpost->ID ) . '</option>';
            }
            echo "</select>";
            echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
            submit_button( __( 'Filter', 'autolead' ), 'secondary', 'automail_filter', FALSE );
            echo '</div>';
        }
    }



}

class ClsAutoLeadSubscribersQuery
{

    /**
     * List of found records
     */
    public $results;

    /**
     * Total number of found users for the current query
     * @access private
     */
    public $total_rows = 0;

    /**
     * The query fields variables which sets the table name and the fields
     * to query
     * @access public
     */
    public $query_fields;

    /**
     * Sets the from query
     * @access public
     */
    public $query_from;

    /**
     * Sets the WHERE part of the query
     * @access public
     */
    public $query_where;

    /**
     * Sets the ORDER BY part of the query
     * @access public
     */
    public $query_orderby;

    /**
     * Handles the limit potion of the main query
     * @access public
     */
    public $query_limit;

    /**
     * PHP5 constructor
     * @param string|array $args The query variables
     * @return clspcfquery
     */
    public function __construct( $args = null )
    {
        if ( !empty( $args ) )
        {
            $this->query_vars = wp_parse_args( $args, array(
                'search' => '',
                'order' => 'ASC',
                'offset' => '',
                'posts_per_page' => '',
                'count_total' => true,
                'search_columns' => ''
                    ) );
        }
    }



    /**
     * Start the process after all the settings are done
     * @access public
     */
    public function start_process()
    {
        $this->prepare_query();
        $this->query();
    }



    /**
     * Sets the table name for the class
     * @access public
     * @param string $name The string
     */
    public function set_fields( $fields )
    {
        $this->query_fields = $fields;
    }



    /**
     * Get the query_fields variable
     * @access public
     */
    public function get_fields()
    {
        return $this->query_fields;
    }



    /**
     * Set the FROM clause of the main query
     * @access public
     * @param string $from The FROM clause of the main query
     */
    public function set_from( $from )
    {
        $this->query_from = $from;
    }



    /**
     * Get the FROM clause from the object propery query_from
     * @access public
     */
    public function get_from()
    {
        return $this->query_from;
    }



    /**
     * Sets the WHERE clause of the query
     * @access public
     * @param string $where The WHERE clause of the query
     */
    public function set_where( $where )
    {
        $this->query_where = $where;
    }



    /**
     * Gets the FROM clause of the query from object property query_where
     * @access public
     */
    public function get_where()
    {
        return $this->query_where;
    }



    /**
     * Sets the ORDER BY clause
     * @access public
     * @param string $orderby The ORDER BY clause of the query
     */
    public function set_orderby( $orderby )
    {
        $this->query_orderby = $orderby;
    }



    /**
     * Gets the ORDER BY part of the query
     * @access public
     */
    public function get_orderby()
    {
        return $this->query_orderby;
    }



    /**
     * Prepare the query variables
     * @access public
     */
    public function prepare_query()
    {
        global $wpdb;

        $qv = &$this->query_vars;

        $this->query_fields = $this->get_fields();

        if ( $this->query_vars['count_total'] )
        {
            $this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
        }

        $this->query_from = 'FROM ' . $this->get_from();
        $this->query_where = $this->get_where();
        $orderby = $this->get_orderby();
        $qv['order'] = strtoupper( $qv['order'] );
        $order = 'ASC' == $qv['order'] ? 'ASC' : 'DESC';
        $this->query_orderby = "ORDER BY $orderby $order";

        // limit
        if ( $qv['posts_per_page'] )
        {
            if ( $qv['offset'] )
            {
                $this->query_limit = $wpdb->prepare( "LIMIT %d, %d", $qv['offset'], $qv['posts_per_page'] );
            }
            else
            {
                $this->query_limit = $wpdb->prepare( "LIMIT %d", $qv['posts_per_page'] );
            }
        }

        $search = trim( $qv['search'] );

        if ( $search )
        {
            $leading_wild = ltrim( $search, '*' ) != $search;
            $trailing_wild = rtrim( $search, '*' ) != $search;
            if ( $leading_wild && $trailing_wild )
            {
                $wild = 'both';
            }
            elseif ( $leading_wild )
            {
                $wild = 'leading';
            }
            elseif ( $trailing_wild )
            {
                $wild = 'trailing';
            }
            else
            {
                $wild = false;
            }

            if ( $wild )
            {
                $search = trim( $search, '*' );
            }

            $this->query_where .= $this->get_search_sql( $search, $qv['search_columns'], $wild );
        }
    }



    /*
     * Used internally to generate an SQL string for searching across multiple columns
     * @param bool $wild Whether to allow wildcard searches. Default is false for Network Admin, true for
     * @return string
     */

    public function get_search_sql( $string, $cols, $wild = false )
    {
        $string = esc_sql( $string );
        $searches = array( );
        $leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
        $trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
        foreach ( $cols as $col )
        {
            $searches[] = "$col LIKE '$leading_wild" . like_escape( $string ) . "$trailing_wild'";
        }
        return ' AND (' . implode( ' OR ', $searches ) . ')';
    }



    /**
     * Execute the query, with the current variables
     * @access public
     */
    public function query()
    {
        global $wpdb;
        $query = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";
        $this->results = $wpdb->get_results( $query );

        if ( $this->query_vars['count_total'] )
        {
            $this->total_rows = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
        }

        if ( !$this->results )
        {
            return;
        }
    }



    /**
     * Return the list of records
     * @return array
     */
    public function get_results()
    {
        return $this->results;
    }



    /**
     * Return the total number of rows
     * @return array
     */
    public function get_total()
    {
        return $this->total_rows;
    }



}
