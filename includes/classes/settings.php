<?php
  if ( ! defined( 'ABSPATH' ) )
    exit;

  return array(
            'enabled'                       =>  array(
                                                      'title'       =>  __( 'Enable/Disable', WC_SP_Manager::$action ),
                                                      'label'       =>  __( 'Enable SkyPayment', WC_SP_Manager::$action ),
                                                      'type'        =>  'checkbox',
                                                      'description' =>  __( 'Enable SkyPayment in your online shop.', WC_SP_Manager::$action ),
                                                      'default'     =>  'no'
                                                    ),
            'title'                         =>  array(
                                                      'title'       =>  __( 'Title', WC_SP_Manager::$action ),
                                                      'type'        =>  'text',
                                                      'description' =>  __( 'Please fill in the title that is shown to your customers as an payment option.', WC_SP_Manager::$action ),
                                                      'default'     =>  __( 'SkyWallet Payment', WC_SP_Manager::$action ),
                                                    ),
            'description'                   =>  array(
                                                      'title'       =>  __( 'Description', WC_SP_Manager::$action ),
                                                      'type'        =>  'textarea',
                                                      'description' =>  __( 'Fill in a short description for your customers, why they should use SkyPayment as a payment modality.', WC_SP_Manager::$action ),
                                                      'default'     =>  __( 'Pay with Bitcoin and X12: You can pay your order directly with your X12 Coins!', WC_SP_Manager::$action ),
                                                    ),
            'sandbox'                       =>  array(
                                                        'title'       =>  __( 'Skypayment Test Mode', WC_SP_Manager::$action ),
                                                        'label'       =>  __( 'Enable the SkyPayment Test Mode', WC_SP_Manager::$action ),
                                                        'type'        =>  'checkbox',
                                                        'description' =>  sprintf( __( ' The SkyPayment Test Mode Allows us to test new features. <a href="%s" target="_blank">Register for a developer account.</a>', WC_SP_Manager::$action ), 'https://stage.skywallet.com/' ),
                                                        'default'     =>  '',
                                                      ),
            'autocomplete_downloadable'     =>  array(
                                                        'title'       =>  __( 'Digital Products', WC_SP_Manager::$action ),
                                                        'label'       =>  __( 'Automatic email with downloadlink after completed payment.', WC_SP_Manager::$action ),
                                                        'type'        =>  'checkbox',
                                                        'description' =>  '',
                                                        'default'     =>  'no',
                                                      ),
            'api_details'                   => array(
                                                        'title'       => __( 'API-References', WC_SP_Manager::$action ),
                                                        'type'        => 'title',
                                                        'description' => sprintf( __( "<code>Webhook URL</code> %s<br/><br/><code>Server IP address</code> %s", WC_SP_Manager::$action ), WC()->api_request_url( 'WC_SP_Gateway' ), gethostbyname( gethostname() ) ),
                                                      ),
            'api_key'                       =>  array(
                                                        'title'       =>  __( 'Public Key', WC_SP_Manager::$action ),
                                                        'type'        =>  'text',
                                                        'description' =>  __( 'Please fill in your public key.', WC_SP_Manager::$action ).' '.__( 'For more additional instructions go to <a href="https://skywallet.com/skypayment-instructions" target="_blank">this page</a>', WC_SP_Manager::$action ),
                                                        'default'     =>  '',
                                                      ),
            'secret_key'                    =>  array(
                                                        'title'       =>  __( 'Secret Key', WC_SP_Manager::$action ),
                                                        'type'        =>  'textarea',
                                                        'description' =>  __( 'Please fill in your secret (not the public one) key.', WC_SP_Manager::$action ).' '.__( 'For more additional instructions go to <a href="https://skywallet.com/skypayment-instructions" target="_blank">this page</a>', WC_SP_Manager::$action ),
                                                        'default'     =>  '',
                                                      ),
          );