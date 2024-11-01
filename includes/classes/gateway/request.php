<?php
  class WC_SP_Gateway_Request
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

    public function __construct( $gateway ) 
    {
      $this->gateway    = $gateway;
    }

    /**
     * Get URL for API
     * 
     * @return string
     *
     * @since  1.0.0
     * 
     */
    protected function get_request_url()
    {
      return $this->gateway->sandbox ? 'https://stage.skywallet.com:9018/v1/api/' : 'https://app.skywallet.com/v1/api/';
    }

    /**
     * Make request to skywallet
     *
     * @param string $method
     * @param array  $additional_data
     * 
     * @return array||Boolean
     *
     * @since  1.0.0
     * 
     */
    protected function request( $method, $additional_data = array() )
    {
        $data = array(
                        'headers'       =>  array(
                                                'Content-Type'  =>  'application/json',
                                                'Authorization' =>  'sky-wallet <'.$this->gateway->get_option( 'api_key' ).'>',
                                                ),
                        'method'        =>  'GET',
                        'user-agent'    =>  'WooCommerce/' . WC()->version
                    );
        
        if( !empty( $additional_data ) )
            $data = array_merge( $data, $additional_data ); 

        $response = wp_remote_request( $this->get_request_url().$method, $data );
        
        if( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 )
            return FALSE;

        $body = json_decode( wp_remote_retrieve_body( $response ) );
        return !isset( $body->status ) || !$body->status ? FALSE : $body->result;
    }

    /**
     * get rate for convert from $from to $to
     * GET https://app.skywallet.com/api/rate/EUR/X12
     * 
     * @param  string $from
     * @param  string $to
     * 
     * @return double||boolean
     *
     * @since  1.0.0
     * 
     */
    public function get_rate( $from = 'EUR', $to = 'X12' )
    {
        $response = $this->request( sprintf( 'rate/%s/%s', $from, $to ) );
        
        if( $response === FALSE )
            return FALSE;

        return isset( $response->rate ) && empty( $response->rate ) ? FALSE : $response->rate;
    }

    /**
     * create order in SkyPayment
     * POST https://app.skywallet.com/api/order
     * 
     * @param  WC_Order $order
     * @param  double $rate
     * 
     * @return string||boolean
     *
     * @since  1.0.0
     * 
     */
    public function create_order( $order, $rate )
    {
        $products = array();
        foreach( $order->get_items() as $item )
            $products[] = $item->get_name().' x '.$item->get_quantity();

        $data = array(
                    'requestedAmount'       =>  $order->get_total() / $rate,
                    'invoiceNumber'         =>  (string)$order->get_id(),
                    'price'                 =>  (double)$order->get_total(),
                    'currency'              =>  $order->get_currency(),
                    'rate'                  =>  $rate,
                    'backToMerchantUrl'     =>  $this->gateway->get_return_url( $order ),
                    'language'              =>  WC_SP_Helpers_General::get_language_code(),
                    'description'           =>  implode( ', ', $products ),
                    );

        update_post_meta( $order->get_id(), '_sp_crypto_amount', $data['requestedAmount'] );
        update_post_meta( $order->get_id(), '_sp_crypto_currency', 'X12' );

        $response = $this->request( 'order', array( 'method' => 'POST', 'body' => json_encode( $data ) ) );
        
        if( $response === FALSE )
            return FALSE;

        return isset( $response->paymentUrl ) && empty( $response->paymentUrl ) ? FALSE : $response->paymentUrl;
    }
  }