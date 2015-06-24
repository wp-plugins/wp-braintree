=== WP Braintree ===
Contributors: Tips and Tricks HQ, josh401
Donate link: https://www.tipsandtricks-hq.com/development-center
Tags: braintree, payment gateway, cart, checkout, e-commerce, store, sales, sell, accept payment, payment, card payment
Requires at least: 3.0
Tested up to: 4.2
Stable tag: 1.2
License: GPLv2 or later

Easily accept payments via Braintree payment gateway. Quick on-site checkout functionality.

== Description ==

This plugin allows you to accept payments using Braintree payment gateway on your WordPress site. 

Users can easily pay with credit cards for your products or services using one-click "Buy Now" button.

You can accept credit card payment for products, services or digital downloads using this plugin.

= Settings Configuration =

Once you have installed the plugin you need to provide your Braintree merchant details in the settings menu (Settings -> WP Braintree).

* Merchant ID
* Public Key
* Private Key
* CSE Key
 
Now create a new post/page and insert Braintree shortcode for your product. For example:

`[wp_braintree_button item_name="Test Product" item_amount="5.00"]`

Use the following shortcode to sell a digital item/product using Braintree:

`[wp_braintree_button item_name="Test Product" item_amount="5.00" url="example.com/downloads/myproduct.zip"]`

The plugin will let the customer download the digital item after a successful payment.

You can customize the buy now button text using the "button_text" parameter in the shortcode. For example:

`[wp_braintree_button item_name="Test Product" item_amount="5.00" button_text="Buy This Item"]`

For screenshots, detailed documentation, support and updates, please visit: [WordPress Braintree plugin](https://www.tipsandtricks-hq.com/wordpress-braintree-plugin) page

== Usage ==

You need to embed the appropriate shortcode on a post/page to create Braintree Buy Now button.

Instructions for using the shortcodes are available at the following URL: 
[Accept Braintree Payments Usage Instruction](https://www.tipsandtricks-hq.com/wordpress-braintree-plugin)

== Installation ==

Upload the plugin to the plugins directory via WordPress Plugin Uploader (Plugins -> Add New -> Upload -> Choose File -> Install Now) and Activate it.

== Frequently Asked Questions ==

= Can this plugin be used to create Buy Now button for Braintree payment gateway? =
Yes

= Can I accept Braintree payments using this plugin? =
Yes

= Can I process credit card payments on my site using this plugin? =
Yes

== Screenshots ==

None

== Upgrade Notice ==

None

== Changelog ==

= 1.2 =
* Added a new parameter in the shortcode so the Buy Now button text can be customized.

= 1.1 =
* Added a new feature to accommodate the selling of a digital item via this plugin. You can specify the URL of a digital item in the shortcode using the "url" parameter.

= 1.0 = 
* First commit to the wordpress repository