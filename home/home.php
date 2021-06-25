<?php
class Pray4Movement_Site_Porch_Home
{
    public $root = "p4m_app";
    public $type = 'home';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_filter( 'dt_non_standard_front_page', [ $this, 'dt_non_standard_front_page' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

        $url = dt_get_url_path();
        if ( empty($url) && ! dt_is_rest() ) {
            add_action( "template_redirect", [ $this, 'theme_redirect' ] );

            add_filter( 'dt_blank_access', function(){ return true;
            } );
            add_filter( 'dt_allow_non_login_access', function(){ return true;
            }, 100, 1 );

            add_filter( "dt_blank_title", [ $this, "_browser_tab_title" ] );
            add_action( 'dt_blank_head', [ $this, '_header' ] );
            add_action( 'dt_blank_footer', [ $this, '_footer' ] );
            add_action( 'dt_blank_body', [ $this, 'body' ] ); // body for no post key

            // load page elements
            add_action( 'wp_print_scripts', [ $this, '_print_scripts' ], 1500 );
            add_action( 'wp_print_styles', [ $this, '_print_styles' ], 1500 );

            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );

        }
    }

    public function _browser_tab_title( $title ){
        $content = get_option('landing_content');
        return $content['title'] ?? '';
    }
    public function theme_redirect() {
        $path = get_theme_file_path('template-blank.php');
        include( $path );
        die();
    }

    public function add_api_routes() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/endpoint', [
                [
                    'methods'  => "POST",
                    'callback' => [ $this, 'endpoint' ],
                ],
            ]
        );
    }

    public function endpoint( WP_REST_Request $request ) {
        // replace in extended class
        $params = $request->get_params();
        return $params;
    }
    public function dt_non_standard_front_page( $url ) {
        if ( dt_is_rest() ) {
            return $url;
        }
        /**
         * This handles a logged in persons urls
         */
//        if ( user_can( get_current_user_id(), 'registered')) {
//            $current_url = dt_get_url_path();
//            // home
//            if ( empty($current_url) ) {
//                $url = home_url( '/' );
//            }
//            else if ( 'examples' === $current_url ) {
//                $url = home_url( '/examples' );
//            }
//        }
        return $url;
    }

    public function _header(){
        ?>
        <?php echo esc_html( $content['google_analytics'] ?? '' ) ?>

        <!--- basic page needs
        ================================================== -->
        <meta charset="utf-8">
        <title><?php echo esc_html( $content['title'] ?? '' ) ?></title>
        <meta name="description" content="<?php echo esc_html( $content['description'] ?? '' ) ?>">
        <meta name="author" content="<?php echo esc_html( $content['title'] ?? '' ) ?>">
        <meta name="author" content="<?php echo esc_html( $content['title'] ?? '' ) ?>">

        <!-- mobile specific metas
        ================================================== -->
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSS
        ================================================== -->
        <link rel="stylesheet" href="<?php echo trailingslashit( plugin_dir_url(__FILE__) ) ?>css/base.css">
        <link rel="stylesheet" href="<?php echo trailingslashit( plugin_dir_url(__FILE__) ) ?>css/vendor.css">
        <link rel="stylesheet" href="<?php echo trailingslashit( plugin_dir_url(__FILE__) ) ?>css/main.css">

        <!-- script
        ================================================== -->
        <script src="<?php echo trailingslashit( plugin_dir_url(__FILE__) ) ?>js/modernizr.js"></script>
        <script src="<?php echo trailingslashit( plugin_dir_url(__FILE__) ) ?>js/pace.min.js"></script>

        <!-- favicons
        ================================================== -->
        <link rel="shortcut icon" href="<?php echo trailingslashit( plugin_dir_url(__FILE__) ) ?>favicon.png" type="image/x-icon">
        <link rel="icon" href="<?php echo trailingslashit( plugin_dir_url(__FILE__) ) ?>favicon.png" type="image/x-icon">

        <style>
            .header-logo {
                z-index: 501;
                display: inline-block;
                margin: 0;
                padding: 0;
                position: absolute;
                left: 110px;
                top: 50%;
                -webkit-transform: translateY(-50%);
                -ms-transform: translateY(-50%);
                transform: translateY(-50%);
            }
            .header-logo a {
                display: block;
                padding: 0;
                outline: 0;
                border: none;
                -webkit-transition: all .3s ease-in-out;
                transition: all .3s ease-in-out;
                background-image: url(<?php echo trailingslashit( plugin_dir_url(__FILE__) ) ?>images/p4m-logo.png);
                background-repeat: no-repeat;
                background-size: 50px;
                background-position: left center;
                padding-left: 60px;
                font-size: 3em;
                font-weight: 900;
                color: #fff;
                font-family: 'times new roman';
            }
            @media only screen and (max-width: 1000px) {
                .header-logo a {
                    font-size: 1em;
                }
            }
        </style>
        <?php
        wp_head();
        $this->header_style();
        $this->header_javascript();
    }
    public function header_javascript(){
        ?>
        <script>
            let jsObject = [<?php echo json_encode([
                'map_key' => DT_Mapbox_API::get_key(),
                'mirror_url' => dt_get_location_grid_mirror( true ),
                'theme_uri' => trailingslashit( get_stylesheet_directory_uri() ),
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'trans' => [
                    'add' => __( 'Add Magic', 'disciple_tools' ),
                ],
            ]) ?>][0]

            jQuery(document).ready(function(){
                clearInterval(window.fiveMinuteTimer)
            })
        </script>
        <?php
        return true;
    }
    public function _footer(){
        wp_footer();
    }
    public function scripts() {
    }
    public function _print_scripts(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php

        $allowed_js = apply_filters( 'public_porch_allowed_js', [
            'jquery',
//            'lodash',
//            'site-js',
//            'shared-functions',
//            'mapbox-gl',
//            'mapbox-cookie',
//            'mapbox-search-widget',
//            'google-search-widget',
//            'jquery-cookie',
//            'jquery-touch-punch',
        ] );

        global $wp_scripts;

        if ( isset( $wp_scripts ) ){
            foreach ( $wp_scripts->queue as $key => $item ){
                if ( ! in_array( $item, $allowed_js ) ){
                    unset( $wp_scripts->queue[$key] );
                }
            }
        }
        unset( $wp_scripts->registered['mapbox-search-widget']->extra['group'] );
    }
    public function _print_styles(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_css = apply_filters( 'public_porch_allowed_css', [
            'foundation-css',
            'jquery-ui-site-css',
//            'site-css',
//            'mapbox-gl-css',
        ] );

        global $wp_styles;
        if ( isset( $wp_styles ) ) {
            foreach ($wp_styles->queue as $key => $item) {
                if ( !in_array( $item, $allowed_css )) {
                    unset( $wp_styles->queue[$key] );
                }
            }
        }
    }
    public function header_style(){
    }
    public function body(){
        require_once( 'template.php');
    }


}
Pray4Movement_Site_Porch_Home::instance();
