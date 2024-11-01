<?php
  class WC_SP_Gateway extends WC_Payment_Gateway
  {
    /**
     * list supported currencies
     *
     * @param array
     * 
     * @since  1.0.0
     * 
     */
    const SUPPORTED_CURRENCIES = array( 'EUR' );

    public function __construct()
    {
      $this->id             = WC_SP_Manager::$action;
      $this->has_fields     = false;
      $this->method_title   = __( 'SkyPayment', WC_SP_Manager::$action );

      $this->init_form_fields();
      $this->init_settings();

      $this->title                      = $this->get_option( 'title' );
      $this->description                = $this->get_option( 'description' );
      $this->enabled                    = $this->get_option( 'enabled' );

      $this->sandbox                    = 'yes' === $this->get_option( 'sandbox' );
      $this->autocomplete_downloadable  = 'yes' === $this->get_option( 'autocomplete_downloadable' );

      $this->api_key                    = $this->get_option( 'api_key' );
      $this->secret_key                 = $this->get_option( 'secret_key' );

      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

      if( $this->is_valid_for_use() )
      {
        WC_SP_Helpers_General::load( 'classes/gateway/ipn' );
        new WC_SP_Gateway_IPN( $this );
      }
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @since  1.0.0
     * 
     */
    public function init_form_fields() 
    {
      $this->form_fields = include( 'settings.php' );
    }

    /**
     * Generate custom icon block
     *
     * @since  1.0.0
     * 
     */
    public function get_icon() 
    {
      $icon_html = '<img src="'.WC_SP_Manager::$assets_url.'frontend/images/logo.png" alt="' . $this->title . '" />';

      $icon_html .= WC_SP_Helpers_General::get_template_part( 'what_is', array( 'dashboard' => false, 'to_variable' => true, 'variables' => array( 'link' => esc_url( 'https://skywallet.com/' ) ) ) );

      return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
    }

    /**
     * Check if this gateway is enabled and available in the user's country.
     * 
     * @return bool
     *
     * @since  1.0.0
     * 
     */
    public function is_valid_for_use()
    {
      return in_array( get_woocommerce_currency(), self::SUPPORTED_CURRENCIES );
    }

    public function admin_options()
    {
      if( $this->is_valid_for_use() )
        parent::admin_options();
      else
        WC_SP_Helpers_General::get_template_part( 'disabled', array( 'dashboard' => true, 'variables' => array( 'label' => __( 'SkyPayment does not support your store currency.', WC_SP_Manager::$action ) ) ) );
    }

    /**
     * Process the payment and return the result.
     * 
     * @param  int $order_id
     * 
     * @return array
     * 
     * @since  1.0.0
     * 
     */
    public function process_payment( $order_id )
    {
      $order = new WC_Order( $order_id );
      
      if( !in_array( $order->get_currency(), self::SUPPORTED_CURRENCIES ) )
      {
        wc_add_notice( __( 'SkyPayment does not support your store currency.', WC_SP_Manager::$action ), 'error' );
        return;
      }

      WC_SP_Helpers_General::load( 'classes/gateway/request' );
      $request = new WC_SP_Gateway_Request( $this );

      $rate = $request->get_rate( 'X12', $order->get_currency() );
      if( !$rate )
      {
        wc_add_notice( __( 'SkyPayment can not convert your amount to crypto amount.', WC_SP_Manager::$action ), 'error' );
        return;
      }
      
      $redirect_url = $request->create_order( $order, $rate );

      if( !$redirect_url )
      {
        wc_add_notice( __( 'Payment error: Error generate link to wallet.', WC_SP_Manager::$action ), 'error' );
        return;
      }

      // Reduce stock levels
      if( function_exists( 'wc_reduce_stock_levels' ) )
        wc_reduce_stock_levels( $order->get_id() );
      else
        $order->reduce_order_stock();

      // Remove cart
      global $woocommerce;
      $woocommerce->cart->empty_cart();

      return array(
                  'result'    => 'success',
                  'redirect'  => $redirect_url
                  );
    }
  }