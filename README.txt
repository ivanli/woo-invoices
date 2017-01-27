=== Woo Invoices - Quotes and Invoices ===
Contributors: SlicedInvoices
Tags: woocommerce, woocommerce invoice, woocommerce quote, woocommerce estimate, invoice, invoicing, quotes, quoting, estimates, billing, bills, paypal, invoice clients, invoice generator, invoice system, woocommerce add-on, woocommerce extension, woocommerce plugin
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 1.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An extension for Woocommerce that allows you to automatically create invoices & quotes from your Woocommerce orders.

== Description ==
= REQUIREMENTS =

*   [Woocommerce Plugin](https://wordpress.org/plugins/woocommerce/) (free)
*   [Sliced Invoices Plugin](https://wordpress.org/plugins/sliced-invoices/) (free)

This Woocommerce extension acts as the middle-man between the Woocommerce plugin and Sliced Invoices plugin, allowing you to easily create invoices and quotes from Woocommerce orders. Both plugins are free and both plugins are required. 

= KEY FEATURES =

*   Automatically create quotes from Woocommerce orders
*   Automatically create invoices from Woocommerce orders
*   Invoices and quotes can be created from the back end (admin creating the order)
*   Invoices only can be created from the front end (upon client checkout)
*   Adds 'Pay by Invoice' as a Checkout option
*   Enable default payment methods on invoices
*   Enable invoices only for certain shipping methods
*   Include instructions and a link to the Invoice in emails
*   Easily print quotes &amp; invoices to PDF by using the [PDF invoices extension](https://slicedinvoices.com/extensions/pdf-email/?utm_source=WordPress&utm_medium=Readme&utm_content=PDF-Email&utm_campaign=Free)
*   The [PDF invoices extension](https://slicedinvoices.com/extensions/pdf-email/?utm_source=WordPress&utm_medium=Readme&utm_content=PDF-Email&utm_campaign=Free) also allows clients to print their quotes and invoices
*	With the PDF extension installed, the PDF will also be attached to the Woocommerce emails that go to the client

= HOW IT WORKS =

Woocommerce and Sliced Invoices are both ecommerce plugins that have very different uses and different functions, but there is also some overlap with some of their features. This plugin takes some features of Sliced Invoices and some features of Woocommerce to achieve an extremely flexible ecommerce setup that can be used in many, many different ways.

You can still use Sliced Invoices independantly of Woocommerce and vice versa.

So how do the plugins work together?

**Admin Area**

*	Automatically create a Quote or an Invoice by creating a Woocommerce Order
*	Invoices are created when the Woocommerce Order Status is set to Invoice (the Invoice is now tied to the Order)
*	Quotes are created when the Woocommerce Order Status is set to Quote (the Quote is now tied to the Order)
*	Editing the Order will edit the tied Invoice or Quote
*	You can convert a Quote to Invoice by simply changing the Order Status to Invoice
*	Mark an Invoice as Paid by changing the Order Status to Completed
*	Quotes and Invoices will get their statuses updated depending on the status of the Order

**Front End**

*	A user can choose the 'Pay via Invoice' option when they check out
*	An order is created as per normal, and an invoice is also generated
*	Uses the Woocommerce Billing Details as the 'To' address on the Invoice or Quote
*	Uses the Woocommerce Order ID as the 'Order Number' on the Invoice or Quote
*	Payment Methods from Sliced Invoices can be added to the Invoice (bank, cheque, PayPal, generic)

**Emailing the Invoice or Quote**

There are a couple of different ways you could send the client the Quote or Invoice.

*	Via the Order Actions section from within the order (this will send a Woocommerce styled email)
*	Via the Sliced Invoices Quotes or Invoices menu (this will send a Sliced Invoices styled email)


= SCENARIOS FOR USE =

**Scenario 1 - Creating Invoices**

You simply need to create invoices from Woocommerce orders.

*	User adds products to cart
*	User goes to checkout and chooses 'Pay via Invoice' option
*	Sliced Invoices creates the Invoice in the background
*	User is taken to the 'Order Received' page. Instructions and a 'View The Invoice' button are shown
*	User can view the Invoice and see the payment methods on the invoice that you have set

**Scenario 2 - Creating Quotes**

You want to create Quotes from your Woocommerce orders and send to clients for approval.

*	You create a Woocommerce Order 
*	You add all of your products, taxes, shipping, billing details etc 
*	You set the Order Status to Quote
*	Sliced Invoices creates the Quote in the background
* 	You can then send an email (via one of the 2 options above) to the client with the link to view the Quote
* 	Client views the Quote, hopefully approves it and then an Invoice is automatically created
*	If you also have the [PDF invoices extension](https://slicedinvoices.com/extensions/pdf-email/?utm_source=WordPress&utm_medium=Readme&utm_content=PDF-Email&utm_campaign=Free) installed, a PDF version of the Invoice or Quote will be attached


== Installation ==
1. Upload plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Woocommerce Checkout settings and then to Sliced Invoices to enable and setup

== Frequently Asked Questions ==

= Setup =

1.	Go to Woocommerce Checkout Settings and click on Sliced Invoices
1.	Ensure that 'Enable/Disable' is checked
1.	Configure the rest of the settings as you require and hit Save

= Where can I get help? =

We are currently working on documentation but in the meantime, you can use the support forums here or email us directly at support(at)slicedinvoices.com

== Screenshots ==

Coming soon

== Changelog ==

= 1.0.5 =
* UPDATE: compatibility with Sliced Invoices v3.3.0

= 1.0.4 =
* FIX: conflict between Sliced Invoices and WooCommerce tax settings

= 1.0.3 =
* FIX: issue with payment amount passed to PayPal when using Deposit Invoices Extension

= 1.0.2 =
* FIX: issues with tax calculation
* UPDATE: Compatibility update with Sliced Invoices Deposit Invoices Extension v2.0.0

= 1.0.1 =
* FIX: Error on edit invoice when not related to a Woocommerce order

= 1.0 =
* Initial release at WordPress.org
