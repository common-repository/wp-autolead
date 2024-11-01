<?php
/*
  Plugin Name: Wordpress AutoLead
  Plugin URI: https://www.usefulblogging.com
  Description:  WordPress AutoLead plugin for survey and lead capture.
  Version: 1.3
  Author: Ataul Ghani
  Author URI: https://www.usefulblogging.com
 */

class ClsAutoLead
{
    /**
     * plugins custom post type name
     */

    const PLUGIN_CPT = 'cptautolead';

    /**
     * Plugin Name
     */
    const PLUGIN_NAME = 'autolead';

    /**
     * Submits table version
     */
    const SUBMITS_TABLE_VERSION = 1;

    /**
     * Submits Meta Table version
     */
    const SUBMITS_META_TABLE_VERSION = 1;

    /**
     * Settings page slug
     */
    public $settings_page;

    /**
     * sets to true if shortcode exists
     */
    public $shortcode_exists;

    /**
     * autolead ids on the page
     * @access public
     */
    public $autolead_ids;

    /**
     * subscribers page
     */
    public $subsribers_page;

    /**
     * Default constructor
     * @access public
     * @param $var datatype|datatype description
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function __construct()
    {
        add_action( 'init', array( $this, 'initialize' ) );
        add_action( 'init', array( $this, 'create_autolead_tables' ) );
        add_action( 'add_meta_boxes', array( $this, 'init_metaboxes' ) );
        add_action( 'admin_print_styles-post.php', array( $this, 'metabox_styles' ) );
        add_action( 'admin_print_styles-post-new.php', array( $this, 'metabox_styles' ) );
        add_action( 'admin_print_scripts-post.php', array( $this, 'metabox_scripts' ) );
        add_action( 'admin_print_scripts-post-new.php', array( $this, 'metabox_scripts' ) );
        //add_action( 'admin_menu', array( $this, 'add_settings_menu' ) );

        /**
         * Save the builder metabox data
         */
        add_action( 'wp_ajax_autolead_save_builder_meta', array( $this, 'save_builder_data' ) );


        /**
         * Shortcode processor
         */
        add_shortcode( 'autolead', array( $this, 'render_shortcode' ) );


        /**
         * add autoload default styles
         */
        add_action( 'wp_footer', array( $this, 'add_default_frontend_assets' ), 10 );
        add_action( 'wp_footer', array( $this, 'add_user_frontend_assets' ), 1000 );


        /**
         * save meta data when post updates
         */
        add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );


        /**
         * Pull the arcode from the autolead post
         */
        add_action( 'wp_ajax_autolead_pull_arcode', array( $this, 'pull_arcode' ) );
        add_action( 'wp_ajax_nopriv_autolead_pull_arcode', array( $this, 'pull_arcode' ) );

        /**
         * Process autolead form
         */
        add_action( 'wp_ajax_autolead_process_form', array( $this, 'process_form' ) );
        add_action( 'wp_ajax_nopriv_autolead_process_form', array( $this, 'process_form' ) );

        /**
         * display the plugin shortcode on columns
         */
        add_filter( 'manage_edit-' . self::PLUGIN_CPT . '_columns', array( $this, 'add_shortcode_columns' ) );
        add_action( 'manage_' . self::PLUGIN_CPT . '_posts_custom_column', array( $this, 'process_shortcode_columns' ) );

        /**
         * Add manage menu
         */
        add_action( 'admin_menu', array( $this, 'add_subscribers_menu' ) );
    }



    /**
     * Initialize the plugin
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function initialize()
    {
        $labels = array(
            'name' => __( 'AutoLead', self::PLUGIN_NAME ),
            'singular_name' => __( 'AutoLead', self::PLUGIN_NAME ),
            'add_new' => __( 'Add New AutoLead', self::PLUGIN_NAME ),
            'add_new_item' => __( 'Add New AutoLead', self::PLUGIN_NAME ),
            'edit' => __( 'Edit AutoLead', self::PLUGIN_NAME ),
            'edit_item' => __( 'Edit AutoLead', self::PLUGIN_NAME ),
            'new-item' => __( 'New AutoLead', self::PLUGIN_NAME ),
            'view' => __( 'View AutoLead', self::PLUGIN_NAME ),
            'view_item' => __( 'View AutoLead', self::PLUGIN_NAME ),
            'search_items' => __( 'Search AutoLead', self::PLUGIN_NAME ),
            'not_found' => __( 'AutoLead Not Found', self::PLUGIN_NAME ),
            'not_found_in_trash' => __( 'AutoLead Not Found in Trash', self::PLUGIN_NAME ),
            'parent' => __( 'Parent AutoLead', self::PLUGIN_NAME )
        );

        $args = array(
            'description' => __( 'AutoLead Custom Post Type', self::PLUGIN_NAME ),
            'labels' => $labels,
            'show_ui' => TRUE,
            'exclude_from_search' => TRUE,
            'public' => FALSE,
            'publicly_queryable' => FALSE,
            'capability_type' => 'post',
            'hierarchical' => TRUE,
            'has_archive' => TRUE,
            'map_meta_cap' => TRUE,
            'rewrite' => array( 'slug' => 'autolead' ),
            'supports' => array( 'title' ),
            'query_var' => TRUE,
            'can_export' => TRUE
        );

        register_post_type( self::PLUGIN_CPT, $args );
    }



    /**
     * Create autolead tables
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function create_autolead_tables()
    {
        global $wpdb;
        $submits_table = $wpdb->prefix . 'autolead_submits';
        $meta_table = $wpdb->prefix . 'autolead_submits_meta';

        if ( !empty( $wpdb->charset ) )
        {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }

        if ( !empty( $wpdb->collate ) )
        {
            $charset_collate .= " COLLATE $wpdb->collate";
        }

        $submits_table_version = get_option( 'autolead_submits_table_version' );

        if ( $submits_table_version < self::SUBMITS_TABLE_VERSION || $wpdb->get_var( "SHOW TABLES LIKE '$submits_table'" ) != $submits_table )
        {
            $sql = "CREATE TABLE $submits_table (
                        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                        autolead_post_id BIGINT(20) UNSIGNED NOT NULL,
                        name VARCHAR(255) NOT NULL DEFAULT '',
                        email VARCHAR(255) DEFAULT NULL,
                        submit_date TIMESTAMP DEFAULT NOW(),
                        ip VARCHAR(255) DEFAULT NULL,
                        UNIQUE KEY survey (ip,autolead_post_id),
                        PRIMARY KEY (id)) $charset_collate;";
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            update_option( 'autolead_submits_table_version', self::SUBMITS_TABLE_VERSION );
        }


        $submits_meta_table_version = get_option( 'autolead_submits_meta_table_version' );
        if ( $submits_meta_table_version < self::SUBMITS_META_TABLE_VERSION || $wpdb->get_var( "SHOW TABLES LIKE '$meta_table'" ) != $meta_table )
        {
            $sql = "CREATE TABLE $meta_table (
                        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                        autolead_post_id BIGINT(20) UNSIGNED NOT NULL,
                        meta_key LONGTEXT NOT NULL,
                        meta_value LONGTEXT NULL DEFAULT NULL,
                        PRIMARY KEY (id)) $charset_collate;";
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            update_option( 'autolead_submits_meta_table_version', self::SUBMITS_META_TABLE_VERSION );
        }
    }



    /**
     * Loads all the necessary metaboxes
     * @access public
     */
    public function init_metaboxes()
    {
        $post_type = get_post_type();
        if ( self::PLUGIN_CPT == $post_type )
        {
            add_meta_box( 'autolead-builder', __( 'AutoLead Builder', self ::PLUGIN_NAME ), array( $this, 'display_metabox' ), $post_type->name, 'advanced', 'high', array( 'view' => 'autolead-builder.php' ) );
            add_meta_box( 'autolead-arcode', 'Autolead Autoresponder', array( $this, 'display_metabox' ), $post_type->name, 'normal', 'high', array( 'view' => 'autolead-arcode.php' ) );
            add_meta_box( 'autolead-after-submit', 'Autolead After Survey Options', array( $this, 'display_metabox' ), $post_type->name, 'normal', 'high', array( 'view' => 'autolead-after-survey.php' ) );
            add_meta_box( 'autolead-title-options', 'Autolead Title Options', array( $this, 'display_metabox' ), $post_type->name, 'normal', 'high', array( 'view' => 'autolead-title-options.php' ) );
            add_meta_box( 'autolead-content-options', 'Autolead Content Options', array( $this, 'display_metabox' ), $post_type->name, 'normal', 'high', array( 'view' => 'autolead-content-options.php' ) );
            add_meta_box( 'autolead-button-options', 'Autolead Action Button Options', array( $this, 'display_metabox' ), $post_type->name, 'normal', 'high', array( 'view' => 'autolead-button-options.php' ) );
            add_meta_box( 'autolead-choice-options', 'Autolead Choice Button Options', array( $this, 'display_metabox' ), $post_type->name, 'normal', 'high', array( 'view' => 'autolead-choice-options.php' ) );
            add_meta_box( 'autolead-list-options', 'Autolead List Options', array( $this, 'display_metabox' ), $post_type->name, 'normal', 'high', array( 'view' => 'autolead-list-options.php' ) );
        }
    }



    /**
     * Display the post metabox
     * @access public
     */
    public function display_metabox( $post, $box )
    {
        require_once 'views/meta/' . $box['args']['view'];
    }



    /**
     * Add metabox stylesheets
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function metabox_scripts()
    {
        $post_type = get_post_type();

        if ( self::PLUGIN_CPT == $post_type )
        {
            global $post;
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-draggable' );
            wp_enqueue_script( 'jquery-ui-droppable' );

            wp_enqueue_script( 'bootstrap', plugins_url( 'js/bootstrap.min.js', __FILE__ ) );
            wp_enqueue_script( 'autolead-admin-js', plugins_url( 'js/autolead-admin.js', __FILE__ ), array( 'jquery', 'bootstrap' ) );
            wp_enqueue_script( 'jquery-stickyfloat', plugins_url( 'js/jquery.stickyfloat.js', __FILE__ ), array( 'jquery' ) );
            wp_enqueue_script( 'alertify', plugins_url( 'js/alertify.min.js', __FILE__ ) );
            $vars = array(
                'nonce' => wp_create_nonce( 'autolead-actions' ),
                'ajax_loader' => plugins_url( 'images/ajax-loader.gif', __FILE__ ),
                'post_id' => $post->ID
            );
            wp_localize_script( 'autolead-admin-js', 'autolead_vars', $vars );
        }
    }



    /**
     * Adds necessary metabox styles
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function metabox_styles()
    {
        $post_type = get_post_type();

        if ( self::PLUGIN_CPT == $post_type )
        {
            wp_enqueue_style( 'autolead-mb-styles', plugins_url( 'css/admin.css', __FILE__ ) );
            wp_enqueue_style( 'alertify-core', plugins_url( 'css/alertify.core.css', __FILE__ ) );
            wp_enqueue_style( 'alertify-theme', plugins_url( 'css/alertify.default.css', __FILE__ ) );
        }
    }



    /**
     * Add settings menu
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function add_settings_menu()
    {
        $this->settings_page = add_submenu_page( 'edit.php?post_type=cptautolead', 'AutoLead Options', 'AutoLead Options', 'manage_options', 'autolead-options', array( $this, 'display_settings_page' ) );
        add_action( 'load-' . $this->settings_page, array( $this, 'settings_metabox_assets' ) );
        /**
         * Metabox loading during the load of the page enables the option to
         * hide this page using the screen options top panel
         */
        add_action( 'load-' . $this->settings_page, array( $this, 'add_settings_metaboxes' ) );
    }



    /**
     * Display the settings page
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function display_settings_page()
    {
        /**
         * Adding the metaboxes here will disable the option to hide an mb
         * using the checkboxes inside the top screenoptions panel
         */
        //$this->add_settings_metaboxes();
        require_once 'views/settings/settings.php';
    }



    /**
     * Adds all the settings metabox scripts
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function settings_metabox_assets()
    {
        wp_enqueue_script( 'common' );
        wp_enqueue_script( 'wp-lists' );
        wp_enqueue_script( 'postbox' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'settings-styles', plugins_url( 'css/settings-page.css', __FILE__ ) );
    }



    /**
     * Add the settings page metaboxes
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function add_settings_metaboxes()
    {
        add_meta_box( 'autolead-title-box', __( 'AutoLead Title Options', 'autolead' ), array( $this, 'display_settings_metabox' ), $this->settings_page, 'normal', 'high', array( 'view' => 'autolead-title-options' ) );
        add_meta_box( 'autolead-content-box', __( 'AutoLead Content Box Options', 'autolead' ), array( $this, 'display_settings_metabox' ), $this->settings_page, 'normal', 'high', array( 'view' => 'autolead-content-options' ) );
        add_meta_box( 'autolead-button-box', __( 'AutoLead Action Button Options', 'autolead' ), array( $this, 'display_settings_metabox' ), $this->settings_page, 'normal', 'high', array( 'view' => 'autolead-button-options' ) );
        add_meta_box( 'autolead-choice-box', __( 'AutoLead Choice Button Options', 'autolead' ), array( $this, 'display_settings_metabox' ), $this->settings_page, 'normal', 'high', array( 'view' => 'autolead-choice-options' ) );
        add_meta_box( 'autolead-list-box', __( 'AutoLead List Options', 'autolead' ), array( $this, 'display_settings_metabox' ), $this->settings_page, 'normal', 'high', array( 'view' => 'autolead-list-options' ) );
    }



    /**
     * Display settings page metaboxes
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function display_settings_metabox( $post, $box )
    {
        require_once 'views/settings/' . $box['args'] ['view'] . '.php';
    }



    /**
     * Saves the builder meta data
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function save_builder_data()
    {
        try
        {
            check_ajax_referer( 'autolead-actions', 'nonce' );
            $post_id = filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );

            if ( !$post_id || self::PLUGIN_CPT !== get_post_type( $post_id ) )
            {
                throw new Exception( 'This is not an autolead admin page' );
            }

            /**
             * Capture the buider data
             */
            $new_meta_value = trim( htmlspecialchars( $_POST['builder_data'], ENT_QUOTES ) );

            $meta_key = '_autolead_builder_data';

            /* Get the meta value of the custom field key. */
            $old_meta_value = get_post_meta( $post_id, $meta_key, TRUE );

            /* If a new meta value was added and there was no previous value, add it. */
            if ( $new_meta_value && '' == $old_meta_value )
            {
                add_post_meta( $post_id, $meta_key, $new_meta_value, TRUE );
            }
            /* If the new meta value does not match the old value, update it. */
            elseif ( $new_meta_value && $new_meta_value != $old_meta_value )
            {
                update_post_meta( $post_id, $meta_key, $new_meta_value );
            }
            /* If there is no new meta value but an old value exists, delete it. */
            elseif ( '' == $new_meta_value && $old_meta_value )
            {
                delete_post_meta( $post_id, $meta_key );
            }

            $output['success'] = 'Builder data updated successfully';
        }
        catch ( Exception $e )
        {
            $output['error'] = $e->getMessage();
        }

        echo json_encode( $output );
        exit;
    }



    /**
     * Shortcode renderer
     * @access public
     * @param mixed $atts The shortcode Attributes
     * @param string $content The content string
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function render_shortcode( $atts, $content )
    {
        try
        {
            $defaults = array( 'id' => '' );
            extract( shortcode_atts( $defaults, $atts ) );

            if ( !$id || self::PLUGIN_CPT != get_post_type( $id ) )
            {
                throw new Exception( 'Invalid autolead shortcode found' );
            }

            /**
             * check if user already submitted the survey
             */
            if ( $this->user_submitted_survey( $id ) )
            {
                $after_action = get_post_meta( $id, '_autolead_after_survey', TRUE );
                switch ( $after_action )
                {
                    case 'redirect':
                        $redirect_url = get_post_meta( $id, '_autolead_redirect', TRUE );
                        echo '<script>location.href="' . $redirect_url . '";</script>';
                        exit;
                        break;

                    case 'content':
                        $after_content = html_entity_decode( get_post_meta( $id, '_autolead_after_content', TRUE ) );
                        return do_shortcode( html_entity_decode( $after_content ) );
                        break;

                    default:
                        return;
                        break;
                }
            }

            /**
             * User reaches here if he really did not submit the survey
             */
            $builder_data = get_post_meta( $id, '_autolead_builder_data', TRUE );

            /**
             * If no data is found then this is an empty post
             */
            if ( !$builder_data )
            {
                throw new Exception( 'Autolead Survey Found Empty' );
            }

            /**
             * If this point is reached then that means the shortcode exists
             * and sets to true to add styles to the footer
             */
            $this->shortcode_exists = TRUE;
            $this->autolead_ids[] = $id;
            $builder_data = html_entity_decode( $builder_data );
            return '<div class="autolead-widget-wrapper" id="autolead-parent-wrapper-' . $id . '">' . $builder_data . '<input type="hidden" name="autolead_post_id" value="' . $id . '" /></div>';
        }
        catch ( Exception $e )
        {
            return '<p class="autolead-error">' . $e->getMessage() . '</p>';
        }
    }



    /**
     * Checks if the user already submitted the survey or not
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function user_submitted_survey( $autolead_post_id )
    {
        $cookie_data = filter_input( INPUT_COOKIE, 'autolead_' . $autolead_post_id );

        if ( !is_null( $cookie_data ) )
        {
            return TRUE;
        }

        /**
         * If the cookie is not found then check for valid ip and list id
         */
        global $wpdb;
        $submits_table = $wpdb->prefix . 'autolead_submits';
        $ip = filter_var( $_SERVER ['REMOTE_ADDR'], FILTER_VALIDATE_IP );
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $submits_table  WHERE autolead_post_id = %d AND ip = %s", $autolead_post_id, $ip ) );

        if ( $result )
        {
            $this->set_cookie( $autolead_post_id );
            return TRUE;
        }

        return FALSE;
    }



    /**
     * Set cookie using autolead post_id
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function set_cookie( $autolead_post_id )
    {
        $cookie_validity = strtotime( '+3 months' );
        $cookie_hash = 'autolead_' . $autolead_post_id;
        @setcookie( $cookie_hash, 'submitted', $cookie_validity, COOKIEPATH, COOKIE_DOMAIN );
    }



    /**
     * Adds the user defined frontend styles
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function add_user_frontend_assets()
    {
        if ( $this->shortcode_exists )
        {
            foreach ( $this->autolead_ids as $id )
            {
                $title_css_rules = get_post_meta( $id, '_autolead_title_css_rules', TRUE );
                $content_css_rules = get_post_meta( $id, '_autolead_content_css_rules', TRUE );
                $button_css_rules = get_post_meta( $id, '_autolead_action_button_css_rules', TRUE );
                $choice_active_button_css_rules = get_post_meta( $id, '_autolead_choice_active_button_css_rules', TRUE );
                $choice_inactive_button_css_rules = get_post_meta( $id, '_autolead_choice_inactive_button_css_rules', TRUE );
                $list_css_rules = get_post_meta( $id, '_autolead_list_css_rules', TRUE );

                $parent_wrapper = '#autolead-parent-wrapper-' . $id . ' .autolead-widget ';
                $css_output = $parent_wrapper . '.autolead-title {' . $title_css_rules . '}';
                $css_output .= $parent_wrapper . '.autolead-content {' . $content_css_rules . '}';
                $css_output .= $parent_wrapper . '.autolead-content .autolead-action {' . $button_css_rules . ' }';
                $css_output .= $parent_wrapper . '.autolead-content .autolead-answer .choice-selector .choice-active {' . $choice_active_button_css_rules . ' }';
                $css_output .= $parent_wrapper . '.autolead-content .autolead-answer .choice-selector .choice-inactive {' . $choice_inactive_button_css_rules . ' }';
                $css_output .= $parent_wrapper . '.autolead-content .autolead-answer ul li {' . $list_css_rules . ' }';
                echo '<style>' . "\n" . $css_output . "\n" . '</style>';
            }
        }
    }



    /**
     * Adds the default styles and scripts to the page
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function add_default_frontend_assets()
    {
        if ( $this->shortcode_exists )
        {
            wp_enqueue_style( 'autolead-user-styles', plugins_url( 'css/autolead-styles.css', __FILE__ ) );
            wp_enqueue_script( 'autolead-user-script', plugins_url( 'js/autolead-user-script.js', __FILE__ ), array( 'jquery' ) );
            $vars = array(
                'nonce' => wp_create_nonce( 'autolead-actions' ),
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'ajax-loader' => plugins_url( 'images/ajax-loader.gif', __FILE__ )
            );
            wp_localize_script( 'autolead-user-script', 'autolead_vars', $vars );
        }
    }



    /**
     * Save Metabox Meta Data
     * @access public
     * @param int $post_id post id
     * @param obj $post global post object
     */
    public function save_meta( $post_id, $post )
    {
        if ( $post->post_type != self::PLUGIN_CPT )
        {
            return $post_id;
        }

        if ( !current_user_can( 'edit_posts', $post->ID ) || $post->post_type === 'revision' )
        {
            return $post_id;
        }

        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) )
        {
            return $post_id;
        }

        /**
         * Now check the nonce here
         */
        if ( !wp_verify_nonce( filter_input( INPUT_POST, 'autolead_nonce', FILTER_SANITIZE_STRING ), 'autolead-actions' ) )
        {
            return $post_id;
        }


        $metas['_autolead_arcode'] = htmlspecialchars( $_POST['autolead_arcode'] );
        $metas['_autolead_after_survey'] = filter_input( INPUT_POST, 'autolead_after_survey', FILTER_SANITIZE_STRING );
        $metas['_autolead_after_content'] = htmlspecialchars( $_POST['autolead_after_content'] );
        $metas['_autolead_redirect'] = filter_input( INPUT_POST, 'autolead_redirect', FILTER_SANITIZE_URL );
        $metas['_autolead_title_css_rules'] = filter_input( INPUT_POST, 'autolead_title_css_rules', FILTER_SANITIZE_STRING );
        $metas['_autolead_content_css_rules'] = filter_input( INPUT_POST, 'autolead_content_css_rules', FILTER_SANITIZE_STRING );
        $metas['_autolead_action_button_css_rules'] = filter_input( INPUT_POST, 'autolead_action_button_css_rules', FILTER_SANITIZE_STRING );
        $metas['_autolead_choice_active_button_css_rules'] = filter_input( INPUT_POST, 'autolead_choice_active_button_css_rules', FILTER_SANITIZE_STRING );
        $metas['_autolead_choice_inactive_button_css_rules'] = filter_input( INPUT_POST, 'autolead_choice_inactive_button_css_rules', FILTER_SANITIZE_STRING );
        $metas['_autolead_list_css_rules'] = filter_input( INPUT_POST, 'autolead_list_css_rules', FILTER_SANITIZE_STRING );

        foreach ( $metas as $meta_key => $new_meta_value )
        {
            /* Get the meta value of the custom field key. */
            $old_meta_value = get_post_meta( $post_id, $meta_key, TRUE );

            /* If a new meta value was added and there was no previous value, add it. */
            if ( $new_meta_value && '' == $old_meta_value )
            {
                add_post_meta( $post_id, $meta_key, $new_meta_value, TRUE );
            }
            /* If the new meta value does not match the old value, update it. */
            elseif ( $new_meta_value && $new_meta_value != $old_meta_value )
            {
                update_post_meta( $post_id, $meta_key, $new_meta_value );
            }
            /* If there is no new meta value but an old value exists, delete it. */
            elseif ( '' == $new_meta_value && $old_meta_value )
            {
                delete_post_meta( $post_id, $meta_key );
            }
        }
    }



    /**
     * Pulls all the arcode
     * @access public
     */
    public function pull_arcode()
    {
        try
        {
            check_ajax_referer( 'autolead-actions', 'nonce' );

            /**
             * Validate the autolead post id
             */
            $autolead_post_id = filter_input( INPUT_POST, 'autolead_id', FILTER_VALIDATE_INT );

            if ( self::PLUGIN_CPT != get_post_type( $autolead_post_id ) )
            {
                throw new Exception( 'Invalid autolead id found, try again later' );
            }

            /**
             * check if the arcode is available
             */
            $arcode = html_entity_decode( get_post_meta( $autolead_post_id, '_autolead_arcode', TRUE ) );
            $arcode = trim( $arcode );

            /**
             * ARcode is present now process that
             */
            if ( $arcode )
            {
                if ( !class_exists( 'PressWordFormProcessor' ) )
                {
                    require_once 'lib/dm-form-processor.php';
                    $autolead_form_processor = new ClsAutoLeadFormProcessor();

                    /**
                     * Check if the form is valid
                     */
                    if ( !$autolead_form_processor->is_form_valid( $arcode ) )
                    {
                        throw new Exception( 'Found Invalid Autoresponder Code' );
                    }

                    /**
                     * Process the form and set the properties
                     */
                    $autolead_form_processor->process_form( $arcode );
                    $output['form_code'] = $autolead_form_processor->get_vanilla_form();
                }
            }
            else
            {
                $output['get_name'] = '<input type="text" class="autolead-input" name="autolead_getname" placeholder="Enter your name" />';
            }
        }
        catch ( Exception $e )
        {
            $output['error'] = $e->getMessage();
        }
        echo json_encode( $output );
        exit;
    }



    /**
     * Process autolead form
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function process_form()
    {
        try
        {
            check_ajax_referer( 'autolead-actions', 'nonce' );

            /**
             * check autolead post id and validate it
             */
            $autolead_post_id = filter_input( INPUT_POST, 'autolead_id', FILTER_VALIDATE_INT );

            if ( !$autolead_post_id || self::PLUGIN_CPT != get_post_type( $autolead_post_id ) )
            {
                throw new Exception( 'Found invalid Autolead Post ID' );
            }

            /**
             * Get the processing type
             */
            $processing_type = filter_input( INPUT_POST, 'process_type', FILTER_SANITIZE_STRING );
            $check_all = filter_input( INPUT_POST, 'check_all', FILTER_VALIDATE_BOOLEAN );

            /**
             * Process survey data
             */
            $survey_data = filter_input_array( INPUT_POST, array(
                'survey_data' => array(
                    'flags' => FILTER_REQUIRE_ARRAY,
                    'filter' => FILTER_SANITIZE_STRING
                ) ) );

            $survey_data = $survey_data['survey_data'];

            if ( !$survey_data )
            {
                throw new Exception( 'Survey data found emtpy' );
            }

            switch ( $processing_type )
            {
                case 'arform':
                    $submit_arform = TRUE;
                    switch ( $check_all )
                    {
                        case TRUE:

                            if ( !$name = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING ) )
                            {
                                throw new Exception( 'Name Found Empty' );
                            }

                            if ( !$email = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL ) )
                            {
                                throw new Exception( 'Email found invalid' );
                            }
                            $email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );
                            break;

                        case FALSE:
                            $valid_field = filter_input( INPUT_POST, 'valid_field', FILTER_SANITIZE_STRING );
                            switch ( $valid_field )
                            {
                                case 'name':
                                    if ( !$name = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING ) )
                                    {
                                        throw new Exception( 'Name Found Empty' );
                                    }
                                    break;

                                case 'email':
                                    if ( !$email = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL ) )
                                    {
                                        throw new Exception( 'Email found invalid' );
                                    }
                                    $email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );
                                    break;
                            }
                            break;
                    }
                    $data['name'] = $name;
                    $data['email'] = $email;
                    $data['meta'] = $survey_data;
                    break;

                case 'getname':
                    if ( !$name = filter_input( INPUT_POST, 'getname', FILTER_SANITIZE_STRING ) )
                    {
                        throw new Exception( 'Name Found Empty' );
                    }
                    $data['name'] = $name;
                    $data['email'] = 'NA';
                    break;

                default:
                    throw new Exception( 'Something went wrong, try again later' );
                    break;
            }

            $date = new DateTime( 'now', new DateTimeZone( 'GMT' ) );
            $data['autolead_post_id'] = $autolead_post_id;
            $data['submit_date'] = $date->format( 'Y-m-d H:i:s' );
            $data['ip'] = filter_var( $_SERVER ['REMOTE_ADDR'], FILTER_VALIDATE_IP );
            $data['meta'] = $survey_data;
            $this->insert_survey( $data );
            $output['success'] = 'Survey Submitted Successfully';
            $output['submit_arform'] = $submit_arform ? TRUE : FALSE;
            $this->set_cookie( $autolead_post_id );
        }
        catch ( Exception $e )
        {
            $output['error'] = $e->getMessage();
        }

        echo json_encode( $output );
        exit;
    }



    /**
     * Adds the new autolead
     * @access public
     * @param datatype $var description
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function insert_survey( $data )
    {
        global $wpdb;
        $submits_table = $wpdb->prefix . 'autolead_submits';
        $meta_table = $wpdb->prefix . 'autolead_submits_meta';
        $submit_data = array(
            'autolead_post_id' => $data['autolead_post_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'submit_date' => $data['submit_date'],
            'ip' => $data['ip']
        );

        $wpdb->insert( $submits_table, $submit_data );
        $last_insert_id = $wpdb->insert_id;

        /**
         * If valid meta and insert id exists continue
         */
        if ( !empty( $data['meta'] ) && $last_insert_id )
        {
            foreach ( $data['meta'] as $meta_key => $meta_value )
            {
                $meta_data['autolead_post_id'] = $last_insert_id;
                $meta_data['meta_key'] = $meta_key;
                $meta_data['meta_value'] = $meta_value;
                $wpdb->insert( $meta_table, $meta_data );
            }
        }
    }



    /**
     * Adds the shortcode columns
     * @access public
     * @param mixed $columns The columns array
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function add_shortcode_columns( $columns )
    {
        $columns['autolead_shortcode'] = __( 'AutoLead Shortcode', self::PLUGIN_NAME );
        return $columns;
    }



    /**
     * Display the shortcode column
     * @access public
     * @param mixed $column The columns array
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function process_shortcode_columns( $column )
    {
        switch ( $column )
        {
            case 'autolead_shortcode':
                global $post;
                echo '[autolead id="' . $post->ID . '"]';
                break;
        }
    }



    /**
     * Add manage menu
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function add_subscribers_menu()
    {
        $this->subsribers_page = add_submenu_page( 'edit.php?post_type=cptautolead', __( 'AutoLead Subscribers', self ::PLUGIN_NAME ), __( 'AutoLead Subscribers', 'autolead' ), 'manage_options', 'autolead-subscribers', array( $this, 'display_subscribers_page' ) );
        add_action( "load-" . $this->subsribers_page, array( $this, 'load_screen_options' ) );
        add_action( "admin_print_scripts-" . $this->subsribers_page, array( $this, 'subscribers_page_scripts' ) );
        add_action( "admin_print_styles-" . $this->subsribers_page, array( $this, 'subscribers_page_styles' ) );
    }



    /**
     * Subscribers admin page scripts
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function subscribers_page_scripts()
    {
        //wp_enqueue_script( 'jquery.colorbox', plugins_url( 'js/jquery-colorbox/jquery.colorbox-min.js', __FILE__ ), array( 'jquery' ), '1.4.27' );
        wp_enqueue_script( 'bootstrap-transition', plugins_url( 'js/transition.js', __FILE__ ), array( 'jquery' ) );
        wp_enqueue_script( 'bootstrap-modal', plugins_url( 'js/modal.js', __FILE__ ), array( 'jquery', 'bootstrap-transition' ) );
        wp_enqueue_script( 'admin-autolead-subscribers', plugins_url( 'js/admin-subscribers.js', __FILE__ ), array( 'jquery' ) );
    }



    /**
     * Load necessary styles
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function subscribers_page_styles()
    {
        //wp_enqueue_style( 'jquery.colorbox', plugins_url( 'js/jquery-colorbox/colorbox.css', __FILE__ ), '', '1.4.27' );
        wp_enqueue_style( 'admin-autolead-subscribers', plugins_url( 'css/admin-subscribers.css', __FILE__ ) );
    }



    /**
     * Loads the affiliate user programs screen options
     * @access public
     */
    public function load_screen_options()
    {
        $screen = get_current_screen();

        if ( !is_object( $screen ) || $screen->id != $this->subsribers_page )
        {
            return;
        }

        $args = array(
            'label' => __( 'Subscibers per page', self::PLUGIN_NAME ),
            'default' => 100,
            'option' => 'edit_per_page'
        );

        add_screen_option( 'per_page', $args );
        include 'views/list-tables/list-subscribers.php';
        global $autolead_subscribers;
        $autolead_subscribers = new ClsAutoLeadList();
    }



    /**
     * Display subscribers page
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function display_subscribers_page()
    {
        global $autolead_subscribers;
        $list_id = filter_input( INPUT_GET, 'list_filter' );
        $autolead_subscribers->prepare_items();
        ?>
        <div class="wrap">
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php _e( 'AutoLead Subscribers', self::PLUGIN_NAME ) ?></h2>
            <?php
            $admin_messages = get_option( 'autolead_admin_messages' );
            if ( $admin_messages )
            {
                $admin_action = $admin_messages['action'];
                switch ( $admin_action )
                {
                    case 'users_deleted':
                        $msg = __( 'Users deleted successfully', self::PLUGIN_NAME );
                        delete_option( 'autolead_admin_messages' );
                        break;

                    default:
                        break;
                }
                echo '<div class="updated" id="message"><p>' . $msg . '</p></div>';
            }
            ?>

            <!--<form method="get" class="search-form">
            <?php $autolead_subscribers->search_box( __( 'Search Users', 'autolead' ), 'user' ); ?>
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
                <input type="hidden" name="post_type" value="<?php echo $_REQUEST['post_type']; ?>" />
            </form>-->


            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="automail-list-form" method="post">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <!-- Now we can render the completed list table -->
                <?php $autolead_subscribers->display(); ?>
            </form>
        </div>
        <?php
    }



}

if ( class_exists( 'ClsAutoLead' ) )
{
    $wpautolead = new ClsAutoLead();
}
