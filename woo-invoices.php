<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Woo Invoices by IKY.LI
 * Plugin URI:        https://iky.li
 * Description:       Create invoices and quotes from your Woocommerce orders. Requirements: Sliced Invoices & Woocommerce Plugins
 * Version:           2.0.0
 * Author:            IKY.LI
 * Author URI:        https://iky.li
 * Text Domain:       woo-invoices
 * Domain Path:       /languages
 * WC requires at least: 2.7
 * WC tested up to: 3.4
 */


// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit;
}

/**
 * Check if WooCommerce and sliced invoices is active
 **/
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;
if ( ! in_array( 'sliced-invoices/sliced-invoices.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;


add_action('plugins_loaded', 'woocommerce_sliced_invoices_init', 49);

function woocommerce_sliced_invoices_init() {

    /*
     * Check if Woocommerce gateway class is loaded
     */
    if ( !class_exists( 'WC_Payment_Gateway' ) ) 
        return;


    add_filter( 'plugin_action_links_woo-invoices/woo-invoices.php', 'plugin_action_links' );
    function plugin_action_links( $links ) {

       $links[] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=wc_sliced_invoices' ) ) .'">' . __( 'Settings', 'woo-invoices' ) . '</a>';
       return $links;

    }

    /**
     * Includes
     */
    require_once plugin_dir_path( __FILE__ ) . 'includes/core.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/process.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/output-filters.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/emails.php';

    /**
     * Localisation
     */
    load_plugin_textdomain('woo-invoices', false, plugin_basename( dirname( __FILE__ ) ) . '/languages');


    /**
     * Gateway class
     * @since   1.0
     */
    class WC_Sliced_Invoices extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         */
        public function __construct() {
            
            $this->plugin_name        = 'woo-invoices';    
            $this->id                 = 'sliced-invoices';
            $this->method_title       = __( 'Sliced Invoices', 'woo-invoices' );
            $this->method_description = sprintf( __( 'Create %1s and %2s from Woocommerce orders using Sliced Invoices.', 'woo-invoices' ), sliced_get_quote_label_plural(), sliced_get_invoice_label_plural() );
            $this->has_fields         = false;

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Get settings
            $this->title                = $this->get_option( 'title' );
            $this->description          = $this->get_option( 'description' );
            $this->thankyou             = $this->get_option( 'thankyou' );
            $this->instructions         = $this->get_option( 'instructions' );
            $this->enable_for_methods   = $this->get_option( 'enable_for_methods', array() );
            $this->enable_for_virtual   = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;
            $this->quote_or_invoice     = $this->get_option( 'quote_or_invoice', 'invoice' ) === 'invoice' ? 'invoice' : 'quote';
            $this->auto_invoice_email   = $this->get_option( 'auto_invoice_email', 'yes' ) === 'yes' ? true : false;
			$this->auto_quote_email     = $this->get_option( 'auto_quote_email', 'yes' ) === 'yes' ? true : false;
            $this->custom_button_text   = $this->get_option( 'custom_button_text' );
            $this->button_product_types = $this->get_option( 'button_product_types' );

            $this->init_hooks();

        }


        /**
         * Hook into actions and filters
         * @since  1.0
         */
        private function init_hooks() {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            add_action( 'woocommerce_thankyou_sliced-invoices', array( $this, 'thankyou_page' ) );
        }


        /**
         * Initialise Gateway Settings Form Fields
         */
        public function init_form_fields() {
            $shipping_methods = array();

            if ( is_admin() )
                foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
                    $shipping_methods[ $method->id ] = $method->get_title();
                }

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __( 'Enable/Disable', 'woocommerce' ),
                    'label'       => __( 'Enable Sliced Invoices as a payment method', 'woo-invoices' ),
                    'type'        => 'checkbox',
                    'description' => sprintf( __( '<strong>Important:</strong> When a user checks out and chooses to \'Pay via %1s\', you need to let them know how they can pay for the %2s.<br>Currently only Sliced Invoices payment methods can be displayed / activated on %3s. Go to Sliced Invoices <a target="_blank" href="%4s">Payment Settings</a> to add payment methods.<br>Woocommerce payment methods are not available to be displayed on %3s as yet.', 'woo-invoices' ), sliced_get_invoice_label(), sliced_get_invoice_label(), sliced_get_invoice_label_plural(), esc_url( admin_url( 'admin.php?page=sliced_invoices_settings&tab=payments' ) ), sliced_get_invoice_label_plural() ),
                    'default'     => 'no'
                ),
                'quote_or_invoice' => array(
                    'title'             => sprintf( __( '%1s/%2s at Checkout', 'woo-invoices' ), sliced_get_quote_label(), sliced_get_invoice_label() ),
                    'type'              => 'select',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 450px;',
                    'default'           => 'invoice',
                    'description'       => sprintf( __( 'Create either %1s or %2s when your users checkout. This only affects the front end checkout.', 'woo-invoices' ), sliced_get_quote_label_plural(), sliced_get_invoice_label_plural() ),
                    'options'           => array(
                        'quote'   => sliced_get_quote_label_plural(),
                        'invoice' => sliced_get_invoice_label_plural()
                    ),
                    'desc_tip'          => true,
                ),
                'title' => array(
                    'title'       => __( 'Title', 'woo-invoices' ),
                    'type'        => 'text',
                    'description' => sprintf( __( 'Payment method title that the customer will see on your website. Suggested: \'Pay via %1s\'.', 'woo-invoices' ), sliced_get_invoice_label() ),
                    'default'     => sprintf( __( 'Pay via %s', 'woo-invoices' ), sliced_get_invoice_label() ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Description', 'woo-invoices' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your website during checkout.', 'woo-invoices' ),
                    'default'     => sprintf( __( 'An %s will be created for payment at a later date.', 'woo-invoices' ), sliced_get_invoice_label() ),
                    'desc_tip'    => true,
                ),
                'thankyou' => array(
                    'title'       => __( 'Instructions', 'woo-invoices' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions or a thank you note that can be added to the Order Received page after checkout.', 'woo-invoices' ),
                    'default'     => sprintf( __( 'You can view and pay for the %s by clicking the button below or by visiting your account page at any time.', 'woo-invoices' ), sliced_get_invoice_label() ),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions in Email', 'woo-invoices' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the emails.', 'woo-invoices' ),
                    'default'     => sprintf( __( 'Thank you for your recent order, you can view and pay for the %s by clicking the button below or by visiting your account page at any time.', 'woo-invoices' ), sliced_get_invoice_label() ),
                    'desc_tip'    => true,
                ),
                'include_link' => array(
                    'title'             => __( 'Button in Email', 'woo-invoices' ),
                    'label'             => sprintf( __( 'This will include a View %1s Button in the email below the instructions.', 'woo-invoices' ), sliced_get_invoice_label() ),
                    'type'              => 'checkbox',
                    'default'           => 'yes'
                ),
                'auto_invoice_email' => array(
                    'title'             => sprintf( __( 'Auto send %s email', 'woo-invoices' ), sliced_get_invoice_label() ),
                    'label'             => sprintf( __( 'Send %s email automatically once user checks out (if applicable)', 'woo-invoices' ), sliced_get_invoice_label(), sliced_get_invoice_label() ),
                    'description'       => sprintf( __( 'You can automatically attach a PDF version of the %1s by using the Sliced Invoices <a target="_blank" href="%2s">PDF Extension</a>.<br>The extension also adds a \'Print PDF\' button to each %3s allowing your clients to easily print their %4s.', 'woo-invoices' ), sliced_get_invoice_label(), esc_url( 'https://slicedinvoices.com/extensions/pdf-email/' ),sliced_get_invoice_label_plural(), sliced_get_invoice_label_plural() ),
                    'type'              => 'checkbox',
                    'default'           => 'yes',
                ),
                'auto_quote_email' => array(
                    'title'             => sprintf( __( 'Auto send %s email', 'woo-invoices' ), sliced_get_quote_label() ),
                    'label'             => sprintf( __( 'Send %s email automatically once user checks out (if applicable)', 'woo-invoices' ), sliced_get_quote_label(), sliced_get_quote_label() ),
                    'type'              => 'checkbox',
                    'default'           => 'yes',
                ),
				'show_order_item_sku' => array(
                    'title'             => __( 'Show SKU on line items', 'woo-invoices' ),
                    'label'             => '',
                    'type'              => 'checkbox',
                    'default'           => 'yes',
				),
				'show_order_item_meta' => array(
                    'title'             => __( 'Show meta fields on line items', 'woo-invoices' ),
                    'label'             => '',
                    'type'              => 'checkbox',
                    'default'           => 'yes',
				),
                'enable_for_methods' => array(
                    'title'             => __( 'Enable for shipping methods', 'woo-invoices' ),
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 450px;',
                    'default'           => '',
                    'description'       => sprintf( __( 'If %s method is only available for certain Shipping methods, set it up here. Leave blank to enable for all Shipping methods.', 'woo-invoices' ), sliced_get_invoice_label() ),
                    'options'           => $shipping_methods,
                    'desc_tip'          => true,
                    'custom_attributes' => array(
                        'data-placeholder' => __( 'Select shipping methods', 'woo-invoices' )
                    )
                ),
                'enable_payment_methods' => array(
                    'title'             => __( 'Choose Payment Methods', 'woo-invoices' ),
                    'description'       => sprintf( __( 'Choose the Payment Methods to display on %s. These are set in the Sliced Invoices Payment Settings and are separate to Woocommerce Checkout Options.', 'woo-invoices' ), sliced_get_invoice_label_plural() ),
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 450px;',
                    'desc_tip'          => true,
                    'default'           => '',
                    'options'           => sliced_get_accepted_payment_methods(),
                ),
                'enable_for_virtual' => array(
                    'title'             => __( 'Accept for virtual orders', 'woo-invoices' ),
                    'label'             => sprintf( __( 'Accept %s method if the order is virtual', 'woo-invoices' ), sliced_get_invoice_label() ),
                    'type'              => 'checkbox',
                    'default'           => 'yes'
                ),
                /*
                 * Slated for version 2
                 */
                // 'buttons' => array(
                //     'title'       => __( 'Button Options', 'woocommerce' ),
                //     'type'        => 'title',
                //     'description' => 'Modify the text on the \'Add to cart\' button, and choose which product types to do this on. This can be useful to have quotes for certain product types.',
                // ),
                // 'custom_button_text' => array(
                //     'title'       => __( 'Custom Button Text', 'woo-invoices' ),
                //     'type'        => 'text',
                //     'description' => sprintf( __( 'If you want to enable %s on the front end, you can rename \'Add to cart\' buttons here. Leave blank to keep as \'Add to cart\'.', 'woo-invoices' ), sliced_get_quote_label_plural() ),
                //     'default'     => '',
                //     'desc_tip'    => true,
                // ),
                // 'button_product_types' => array(
                //     'title'             => __( 'Product Types', 'woo-invoices' ),
                //     'description'       => sprintf( __( 'Choose the Product Types to add the custom button text to.', 'woo-invoices' ), sliced_get_invoice_label_plural() ),
                //     'type'              => 'multiselect',
                //     'class'             => 'wc-enhanced-select',
                //     'css'               => 'width: 450px;',
                //     'desc_tip'          => true,
                //     'default'           => '',
                //     'options'           => array(
                //         'external'  => __( 'External', 'woocommerce' ),
                //         'grouped'   => __( 'Grouped', 'woocommerce' ),
                //         'simple'    => __( 'Simple', 'woocommerce' ),
                //         'variable'  => __( 'Variable', 'woocommerce' ),
                //     )
                // ),

           );
        }

        /**
         * Check If The Gateway Is Available For Use
         *
         * @return bool
         */
        public function is_available() {
            $order          = null;
            $needs_shipping = false;

            // Test if shipping is needed first
            if ( WC()->cart && WC()->cart->needs_shipping() ) {
                $needs_shipping = true;
            } elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
                $order_id = absint( get_query_var( 'order-pay' ) );
                $order    = wc_get_order( $order_id );

                // Test if order needs shipping.
                if ( 0 < sizeof( $order->get_items() ) ) {
                    foreach ( $order->get_items() as $item ) {
                        $_product = $order->get_product_from_item( $item );
                        if ( $_product && $_product->needs_shipping() ) {
                            $needs_shipping = true;
                            break;
                        }
                    }
                }
            }

            $needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

            // Virtual order, with virtual disabled
            if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
                return false;
            }

            // Check methods
            if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {

                // Only apply if all packages are being shipped via chosen methods, or order is virtual
                $chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

                if ( isset( $chosen_shipping_methods_session ) ) {
                    $chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
                } else {
                    $chosen_shipping_methods = array();
                }

                $check_method = false;

                if ( is_object( $order ) ) {
                    if ( $order->shipping_method ) {
                        $check_method = $order->shipping_method;
                    }

                } elseif ( empty( $chosen_shipping_methods ) || sizeof( $chosen_shipping_methods ) > 1 ) {
                    $check_method = false;
                } elseif ( sizeof( $chosen_shipping_methods ) == 1 ) {
                    $check_method = $chosen_shipping_methods[0];
                }

                if ( ! $check_method ) {
                    return false;
                }

                $found = false;

                foreach ( $this->enable_for_methods as $method_id ) {
                    if ( strpos( $check_method, $method_id ) === 0 ) {
                        $found = true;
                        break;
                    }
                }

                if ( ! $found ) {
                    return false;
                }
            }

            return parent::is_available();
        }


        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {

            $order  = wc_get_order( $order_id );
            $items  = wc_get_order( $order_id )->get_items();

            $id = sliced_woocommerce_create_quote_or_invoice( $this->quote_or_invoice, $order, $items ); // create the quote or invoice
            
            // Mark as quote or invoice status
            $order->update_status( 'wc-' . $this->quote_or_invoice, '' );

            // Reduce stock levels
			if ( version_compare( WC()->version, '3.0.0', '>=' ) ) {
				wc_reduce_stock_levels( $order );
			} else {
				$order->reduce_order_stock();
			}

            if( $this->quote_or_invoice === 'invoice' && $this->auto_invoice_email ) {
                // send the invoice
                $this->customer_invoice( $order );
            } elseif ( $this->quote_or_invoice === 'quote' && $this->auto_quote_email ) {
				// send the quote
				$this->customer_quote( $order );
			}

            // Remove cart
            WC()->cart->empty_cart();
			
			// trigger new order email
			$mailer = WC()->mailer();
			$mails = $mailer->get_emails();
			if ( ! empty( $mails ) ) {
				foreach ( $mails as $mail ) {
					if ( $mail->id == 'new_order' ) {
						$mail->trigger( $order->id, $order );
					}
				}
			}

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );

        }

        /**
         * Prepare and send the customer invoice email on demand.
         */
        public function customer_invoice( $order ) {
            $email = new WC_Email_Customer_Invoice;
            $email->trigger( $order );
        }

        /**
         * Prepare and send the customer quote email on demand.
         */
        public function customer_quote( $order ) {
            $email = new WC_Email_Customer_Quote;
            $email->trigger( $order );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page( $order_id ) {
            if ( $this->thankyou ) {
                echo wpautop( wptexturize( $this->thankyou ) );
            }
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			
			if (
				$this->instructions &&
				! $sent_to_admin &&
				'sliced-invoices' === sliced_woocommerce_get_object_property( $order, 'order', 'payment_method' )
			) {

                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;

                $sliced_id  = (int) sliced_woocommerce_get_invoice_id( sliced_woocommerce_get_object_property( $order, 'order', 'id' ) );
                $btn_text   = sprintf( __( 'View this %s online', 'sliced-invoices' ), sliced_get_the_type( $sliced_id ) );
                $color      = get_option( 'woocommerce_email_base_color' );
                $base_text  = wc_light_or_dark( $color, '#202020', '#ffffff' );

                echo "<br><a href='" . esc_url( sliced_get_the_link( $sliced_id ) ) . "' style='font-size: 100%; line-height: 2; color: " . $base_text . "; border-radius: 3px; display: inline-block; cursor: pointer; font-weight: bold; text-decoration: none; background: " . $color . "; margin: 20px 0 10px 0; padding: 0; border-color: " . $color . "; border-style: solid; border-width: 7px 15px;'>" . esc_html( $btn_text ) . "</a>";

            }
        }

    }    


} 
