<?php
    /**
    * 
    * Plugin Name:      WooCommerce Bitcoin and X12 Payment Gateway - SkyPayment
    * Version:          1.0.0
    * Plugin URI:       https://skywallet.com/skypayment
    * Description:      With SkyPayment Plugin you are able to accept Cryptocurrency Payment in your Woocommerce Online Shop.
    * Author:           SkyWallet
    * Author URI:       https://skywallet.com/
    * Text Domain:      skypayment
    * 
    **/


    add_action( 'plugins_loaded', array( 'WC_SP_Manager', '_instance' ), 10 );
    register_activation_hook( __FILE__, array( 'WC_SP_Manager', '_activate' ), 10 );


    class WC_SP_Manager
    {
        private static $_instance = NULL;

        /**
         * name of SkyPayment gateway
         * 
         * @var string
         *
         * @since  1.0.0
         * 
         */
        const GATEWAY_KEY = 'WC_SP_Gateway';

        /**
         * path to include folder
         * 
         * @var string
         *
         * @since 1.0.0
         * 
         */
        static $path;

        /**
         * path to assets folder
         * 
         * @var string
         *
         * @since 1.0.0
         * 
         */
        static $assets_path;

        /**
         * URL to assets folder
         * 
         * @var string
         *
         * @since 1.0.0
         * 
         */
        static $assets_url;

        /**
         * version of plugin
         * 
         * @var string
         *
         * @since 1.0.0
         * 
         */
        static $version;

        /**
         * slug
         * 
         * @var string
         *
         * @since 1.0.0
         * 
         */
        static $action = '';

        /**
         * version environment of PHP, WP, WOO
         * 
         * @var array
         *
         * @since  1.0.0
         * 
         */
        protected static $environment = array( 'PHP' => '5.3', 'WordPress' => '4.4.8', 'WooCommerce' => '3.0.0' );

        /**
         * list of notices
         * 
         * @var array
         *
         * @since  1.0.0
         * 
         */
        public static $notices = array( 'frontend' => array(), 'dashboard' => array() );

        private function __construct()
        {
            self::$path           =   dirname(__FILE__).'/includes/';
            self::$assets_path    =   dirname(__FILE__).'/assets/';

            self::$assets_url     =   plugins_url( '', __FILE__ ).'/assets/';

            self::$version        =   self::get_plugin_info( 'Version' );
            self::$action         =   self::get_plugin_info( 'TextDomain' );

            add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ), 15 );

            $flag = self::check_environment();

            if( !is_null( $flag ) )
            return;

            require_once( self::$path.'helpers/general.php' );

            add_action( 'plugins_loaded', array( __CLASS__, 'init_gateway' ), 20 );
        }

        /**
         * show admin notices
         * 
         * @since 1.0.0
         * 
         */
        public static function admin_notices()
        {
            if( sizeof( self::$notices['dashboard'] ) != 0 )
                foreach( self::$notices['dashboard'] as $notice )
                    echo $notice;
        }

        /**
         * after activate plugin
         * 
         * @since 1.0.0
         * 
         */
        public static function _activate()
        {
            $flag = self::check_environment();

            if( is_null( $flag ) )
                return;

            deactivate_plugins( basename( __FILE__ ) );

            wp_die( 
                sprintf( __( '<p>The <strong>SkyPayment</strong> plugin requires %s version %s or greater.</p>', self::$action ), $flag, self::get_environment( $flag ) ), 
                __( 'Plugin Activation Error', self::$action ), 
                array( 
                    'back_link' => true 
                    ) 
                );
        }

        /**
         * load textdomain for skywallet
         * 
         * @since 1.0.0
         * 
         */
        public static function load_textdomain()
        {
            if( is_textdomain_loaded( self::$action ) )
                return true;

            $locale = apply_filters(
                            'plugin_locale',
                            ( is_admin() && function_exists( 'get_user_locale' ) ) ? get_user_locale() : get_locale(),
                            self::$action
                                );

            if( in_array( $locale, array( 'de_DE_formal', 'de_CH', 'de_CH_informal' ) ) )
                $locale = 'de_DE';

            return load_textdomain( self::$action, self::$path . 'languages/' . self::$action . '-' . $locale . '.mo' );
        }

        /**
         * load and init gateway
         * 
         * @since 1.0.0
         * 
         */
        public static function init_gateway()
        {
            $woo = WC_SP_Helpers_General::check_woocommerce();
            
            if( !$woo )
                WC_SP_Helpers_General::add_notice(
                                            sprintf( __( '<p>The <strong>SkyPayment</strong> plugin requires %s version %s or greater.</p>', self::$action ), 'WooCommerce', self::get_environment( 'WooCommerce' ) )
                                                );

            add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ), 15 );

            if( !$woo )
                return;

            WC_SP_Helpers_General::load( 'classes/gateway' );

            add_filter( 'woocommerce_payment_gateways', function( $methods ){
                return array_merge( $methods, array( self::GATEWAY_KEY ) );
            });
        }

        /**
         * return environment by key
         * 
         * @param  string $title
         * 
         * @return string
         *
         * @since  1.0.0
         * 
         */
        public static function get_environment( $key )
        {
            return self::$environment[ $key ];
        }

        /**
         * get info about environment
         * 
         * @return string
         *
         * @since  1.0.0
         * 
         */
        protected static function check_environment()
        {
          global $wp_version;

          $flag = null;

          if ( version_compare( PHP_VERSION, self::get_environment( 'PHP' ), '<' ) )
              $flag = 'PHP';
          elseif( version_compare( $wp_version, self::get_environment( 'WordPress' ), '<' ) )
              $flag = 'WordPress';

          return $flag;
        }

        /**
         * get information about plugin by type
         * 
         * @param  string $name Type of data field. 
         *                      Types https://codex.wordpress.org/File_Header
         * @return string       
         *
         * @since  1.0.0
         * 
         */
        public static function get_plugin_info( $name )
        {
          /** WordPress Plugin Administration API */
          require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

          $data = get_plugin_data( __FILE__ );
          
          if( $name == 'Release' )
          {
            $d = explode( '.', $data['Version'] );
            return $d[ sizeof($d) - 1 ];
          }

          return $data[$name];
        }

        private function __clone() {}

        public static function _instance()
        {
          if ( NULL === self::$_instance)
            self::$_instance = new self();

          return self::$_instance;
        }
    }