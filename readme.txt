=== mango-ssl ===

Company: Synthetic Solutions ltd.
Website: www.mangossl.com
Author: Franck Janini
Contributors: mangossl
Donate link: www.mangossl.com
Tags: accounting, inventory, payment
Requires at least: 4.6
Tested up to: 5.2.1
Requires WooCommerce 3 or higher
Stable tag: trunk
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

mango-ssl plugin is sending WooCommerce orders to mango server (www.mangossl.com) to avoid entering those orders manually 
in the mango accounting system.

== Description ==

This plugin is designed to be used by mango accounting system users that are using woocommerce as their online point of sale 
and wishes to have the 2 systems synchronized. 

mango-ssl plugin is sending WooCommerce orders changes to mango server (www.mangossl.com). The plugin is capturing all the
order events (creation, update, cancellation) and send them in real time to mango for accounting and inventory management 
purpose. The order created or updated automatically from WooCommerce are kept 'on Hold' in mango until they are processed 
or completed.
When an order is send from woocommerce to mango with static data details such as product or client that have not been declared 
on mango previously, those will be created automatically.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Install and activate WooCommerce if you haven't already done so
1. Use the Settings->Mango Setup screen to get authenticated to mango with your mango login details (domain, login, password). 

== Frequently Asked Questions ==

= What is required to connect to mango? =

mango requires valid user credentials to be entered in the setup menu (Settings > Mango Setup). It also requires this user 
to be authorized (within mango) to create or update invoices. As for normal login access to mango, the number of attempts
is limited to 3 attempts.

= where will I found woocommerce orders stored in mango? =

woocomerce orders will appear in the income->invoice section of mango. Orders will be created or updated in an holding status 
while canceled orders will appear with canceled status.

= what about client and product details? =

unknown client or products entered in woocommerce will be automatically created in mango while existing clients or products
will just be linked to the invoice as reference. Client or product update on a woocomerce invoice will not update client and 
product static data in mango but them will be taken into account at the level of the mango invoice.

== Screenshots ==

1. Screenshot of the setup menu to authenticate mango user:

/assets/screenshot-1.png

== Changelog ==

= 1.0 =
* Fist release

= 1.1 =
* Updated to support paid and cancelled order status

== Upgrade Notice ==

= 1.0 =
First release

= 1.1 =
* Updated to support paid and cancelled order status
