<?php
  class WC_SP_Gateway_IPN
  {
    /**
     * Pointer to gateway making the request.
     * 
     * @var WC_SP_Gateway
     *
     * @since  1.0.0
     * 
     */
    protected $gateway;

    /**
     * available payment statuses 
     * 
     * @var array
     *
     * @since  1.0.0
     * 
     */
    protected $available_payment_statuses = array( 'expired', 'fulfilled' );

    /**
     * data from SkyPayment
     * 
     * @var object
     *
     * @since  1.0.0
     * 
     */
    protected $data;

    /**
     * WooCommerce Order object
     * 
     * @var WC_Order
     *
     * @since  1.0.0
     * 
     */
    protected $order;

    public function __construct( $gateway ) 
    {
      $this->gateway    = $gateway;

      add_action( 'woocommerce_api_'.strtolower( WC_SP_Manager::GATEWAY_KEY ), array( $this, 'response' ) );
    }

    /**
     * handler requests from SkyPayment
     * 
     * @since 1.0.0
     * 
     */
    public function response()
    {
      $data = file_get_contents('php://input');

      if( !empty( $data ) && $this->validate_response( $data ) )
      {
        $process_payment = 'process_payment_status_'.$this->data->orderStatus;
        
        if( method_exists( $this, $process_payment ) )
          call_user_func( array( $this, $process_payment ) );

        unset( $process_payment );
        
        $this->answer( TRUE );
      }

      $this->answer( FALSE );
    }

    /**
     * check response from SkyPayment
     *
     * @param  string $data
     * 
     * @return boolean
     *
     * @since  1.0.0
     * 
     */
    protected function validate_response( $data )
    {
      $data = json_decode( stripcslashes( $data ) );
      if( !isset( $data->signature ) )
        return FALSE;

      $this->data = $data;
      
      if( !$this->check_signature() || !in_array( $this->data->orderStatus, $this->available_payment_statuses ) || (int)$this->data->invoiceNumber == 0 )
        return FALSE;

      try {
        $this->order = new WC_Order( (int)$this->data->invoiceNumber );
      } catch (Exception $e) {
        return FALSE;
      }
      
      $check_status = 'check_payment_status_'.$this->data->orderStatus;
      return method_exists( $this, $check_status ) ? call_user_func( array( $this, $check_status ) ) : TRUE;
    }

    /**
     * check signature
     * 
     * @return boolean
     *
     * @since  1.0.0
     * 
     */
    protected function check_signature()
    {
      $data = (array)$this->data;
      unset( $data['signature'] );

      return (boolean)@openssl_verify( 
                                        md5( json_encode( $data ) ), 
                                        hex2bin( $this->data->signature ), 
                                        $this->gateway->get_option( 'secret_key' ), 
                                        OPENSSL_ALGO_SHA256 
                                      );
    }

    /**
     * process payment with status fulfilled
     * 
     * @since 1.0.0
     * 
     */
    protected function process_payment_status_fulfilled()
    {
      if( $this->data->transactionStatus == 'verified' )
        $this->complete_payment();
      else
        $this->order->update_status( 'on-hold', __( 'Payment processing.', WC_SP_Manager::$action ) );   
    }

    /**
     * process payment with status expired
     * 
     * @since 1.0.0
     * 
     */
    protected function process_payment_status_expired()
    {
      if( $this->data->transactionStatus == 'verified' && $this->check_payment_status_fulfilled() )
        $this->complete_payment();
      else
        $this->order->update_status( 'failed', __( 'Payment expired.', WC_SP_Manager::$action ) );
    }

    /**
     * complete payment
     * 
     * @since 1.0.0
     * 
     */
    protected function complete_payment()
    {
      update_post_meta( $this->order->get_id(), '_transaction_id', $this->data->paymentId );

      if( $this->gateway->autocomplete_downloadable && $this->has_only_downloadable() )
      {
        $this->order->add_order_note( __( 'SkyPayment payment completed.', WC_SP_Manager::$action ) );
        $this->order->payment_complete( $this->data->paymentId );
        return;
      }

      $this->order->update_status( 'processing', __( 'SkyPayment payment completed.', WC_SP_Manager::$action ) );
    }

    /**
     * check Order has only downloadable products 
     * 
     * @return boolean
     *
     * @since  1.0.0
     * 
     */
    protected function has_only_downloadable()
    {
      foreach( $this->order->get_items() as $item )
        if( !$item->get_product()->is_downloadable() )
          return FALSE;

      return TRUE;
    }

    /**
     * check payment with status fulfilled
     * 
     * @return boolean
     *
     * @since  1.0.0
     * 
     */
    protected function check_payment_status_fulfilled()
    {
      if( round( $this->data->receivedAmount, 12 ) < round( (double)get_post_meta( $this->order->get_id(), '_sp_crypto_amount', true ), 12 ) )
        return FALSE;

      return TRUE;
    }

    /**
     * answer for SkyPayment
     * 
     * @param  boolean $success
     * 
     * @since  1.0.0
     */
    protected function answer( $success = FALSE )
    {
      echo json_encode( array( 'status' => $success ) );
      die();
    }
  }