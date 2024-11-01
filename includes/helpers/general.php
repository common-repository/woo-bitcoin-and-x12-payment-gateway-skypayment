<?php
    class WC_SP_Helpers_General
    {
        /**
         * load by path
         * if class and function not empty, call function
         * 
         * @param  string  $path
         * @param  array   $options
         *
         * @since  1.0.0
         * 
         */
        public static function load( $path, $options = array() )
        {
            extract( shortcode_atts( 
                                    array(
                                        'class'         => '',
                                        'function'      => '',
                                        'full_path'     =>  false,
                                        'one_time'      =>  true,
                                        'variables'     =>  array(),
                                        'extension'     =>  'php'
                                        ), 
                                    $options 
                                )
                );

            if( sizeof( $variables ) != 0 )
                extract( $variables );

            $path = ( $full_path ? $path : WC_SP_Manager::$path.$path ).'.'.$extension;
            
            if( $one_time )
                require_once( $path );
            else
                require( $path );

            if( !empty( $function ) && !empty( $class ) )
                call_user_func( array( $class, $function ) );
        }

        /**
         * check WooCommerce on site ( availability and version )
         * 
         * @return boolean
         *
         * @since  1.0.0
         * 
         */
        public static function check_woocommerce()
        {
            if( !class_exists('WC_Payment_Gateway') )
                return FALSE;

            global $woocommerce;
            
            if( version_compare( $woocommerce->version, WC_SP_Manager::get_environment( 'WooCommerce' ), ">=" ) )
                return TRUE;

            return FALSE;
        }

        /**
         * add notice for dashboard
         * 
         * @param string $message
         * @param array  $options
         *
         * @since  1.0.0
         * 
         */
        public static function add_notice( $message, $options = array() )
        {
            extract( shortcode_atts( 
                                    array(
                                        'class'         => '',
                                        'type'          => 'warning',
                                        'dismissible'   =>  false,
                                        ), 
                                    $options 
                                )
                );
            
            WC_SP_Manager::$notices['dashboard'][] = self::get_template_part( 
                                                                        'notice', 
                                                                        array( 
                                                                            'dashboard'     =>  true,
                                                                            'to_variable'   =>  true,
                                                                            'variables'     =>  array(
                                                                                                    'message'       =>  $message,
                                                                                                    'class'         =>  $class,
                                                                                                    'type'          =>  $type,
                                                                                                    'dismissible'   =>  $dismissible,
                                                                                                    )
                                                                            ) 
                                                                        );
        }

        /**
         * get template part
         * 
         * @param  string  $path 
         * @param  array   $options
         * 
         * @since  1.0.0
         * 
         */
        public static function get_template_part( $path, $options = array() )
        {
            extract( shortcode_atts( 
                                    array(
                                        'variables'     =>  array(),
                                        'dashboard'     =>  false,
                                        'to_variable'   =>  false,
                                        'extension'     =>  'php'
                                        ), 
                                    $options 
                                )
                );

            $full_path = WC_SP_Manager::$assets_path.( $dashboard ? 'dashboard' : 'frontend' ).'/views/'.$path;
            
            if( !file_exists( $full_path.'.'.$extension ) )
                return;
            
            ob_start();
            
            self::load( 
                        $full_path,
                        array(
                            'full_path'     =>  true,
                            'one_time'      =>  false,
                            'variables'     =>  $variables,
                            'extension'     =>  $extension
                            )
                    );

            $content = ob_get_contents();
            ob_end_clean();

            if( $to_variable )
                return $content;

            echo $content;
        }

        /**
         * convert locale to language code
         * 
         * @return string
         *
         * @since  1.0.0
         * 
         */
        public static function get_language_code()
        {
            $locale = explode( '_', get_locale() );
            return isset( $locale[0] ) ? $locale[0] : $locale;
        }
    }