=== Choice Payment Gateway for WooCommerce ===
Contributors: versacomp, mlipayon
Tags: payment processing, cash discount, choice woocommerce, ach, woocommerce payment
Requires at least: 5.5
Tested up to: 6.1.1
Requires PHP: 7.3
Stable tag: 2.1.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Choice gives your customers more ways to pay.

== DESCRIPTION ==
Accept credit cards, debit cards, ACH, eCheck, Bank Account, Pay Later options, and more all with a FREE single plugin for a seamless checkout experience.

The Choice for WooCommerce plugin is easy to install and quickly enables your store to begin accepting payments online instantly.

== WHY CHOOSE CHOICE? ==
Choice gives your customers more ways to pay with support for all major credit cards, ACH / eCheck, Pay Later, and more. Choice offers multiple pricing and billing models with instant approvals so you can get up and running in minutes.

== MULTIPLE PRICING & BILLING MODELS TO CHOOSE FROM: ==

=== Standard Merchant Account: ===
Your customers can pay at checkout with a credit card, debit card, bank account (ACH, eCheck), and pay later options.

Merchant rates are 2.9% and $0.30 per transaction, no setup fees, no monthly fees.

=== SURCHARGING MERCHANT ACCOUNT: ===
Substantially reduce your credit card processing fees with our surcharging program. Surcharging can be applied to credit card transactions. Debit and pre-paid transactions are excluded. Currently available in all states except CO, CT, MA, OK.

Apply a compliant surcharge up to 4% to credit card transactions and this fee is paid by the cardholder at checkout. Monthly service charge applies.
Surcharging currently available in all states except CO, CT, MA, OK.

=== CASH DISCOUNT MERCHANT ACCOUNT: ===
Reduce your credit card processing by 90%! Our cash discount program helps you increase your profit margin by encouraging your customers to pay by bank account (ACH/eCheck).

Merchant applies a compliant cash discount fee of 3.99% and this fee is paid by the customer at checkout when paying by credit card. If customer pays with bank account (ACH / eCheck), the discount is applied. Monthly service charge applies. Cash discount program available in all 50 states.

=== BUY NOW PAY LATER: (included with all merchant accounts) ===
Offer flexible integrated financing options that gives your customer even more ways to pay.
•	Real-time loan approvals from $1000-$25,000
•	Flexible terms from 36-60 months
•	Zero risk and no setup fees
•	Low interest rate programs for your customers

For more information on our Buy Now Pay Later options, visit [https://www.payterms.com](https://www.payterms.com)

=== SALES OVER $50K per MONTH? ===
If you process over 50K per month, contact us for a custom quote: [sales@choiceinc.biz](mailto:sales@choiceinc.biz)

== FEATURES: ==
* Seamless integration into your WooCommerce checkout page giving you the ability to accepts payments directly on your store.
* Choice supports recurring payments using the WooCommerce Subscriptions extension. The Choice plugin handles all the subscription functionality
* Process Refunds, voids, and capture via Dashboard: Process full or partial refunds, voids, and sales directly from your WooCommerce dashboard
* Supports both “Authorize Only” and “Authorize & Capture” transactions
* All card data is tokenized, and Choice meets PCI Compliance standards

== HOW DO I GET STARTED? ==
You’ll need an active Choice merchant account. To sign up for a new merchant account select an option above. If you already have a merchant account but have not received your username and password or API key, contact Choice support at [support@choiceinc.biz](support@choiceinc.biz).

=== Minimum Requirements ===
* WordPress 5.5
* PHP 7.3

=== Installation ===
Automatic installation is the easiest option as WordPress handles the file transfers itself, and you don’t need to leave your web browser. To do an automatic install, click download.

After downloading and installing the plugin, activate and configure settings. You’ll need your merchant account credentials provided by Choice to use this plugin.

1.	After you have installed the Choice Payment Gateway for WooCommerce plugin, go to WooCommerce > Settings and click on the Payments tab.
2.	You’ll see Choice listed along with 2 payment methods: Credit Card and ACH. You can choose credit card or ACH or both
3.	Follow the steps below to enable Credit Card. You’ll follow the same instructions to enable ACH.
4.	Follow the steps below to enable ACH.
5.	After you have successfully connected your Choice account, click on the Enable Choice Payment Gateway checkbox to enable Choice.
6.	Click Save changes.

Enable Credit Card:
* Click Set Up
* Add Title: This is set to Credit Card by default
* Add Description: This is set by default: Pay with your credit card via the Choice Payment Gateway. You can make changes if needed.
* Add your Merchant Name
* Add Username/Password/Device Credit Card GUID that was provided by Choice
* Select Payment Action: Authorize or Capture
* If you want to add a Service Fee, enable Charge a Service Fee
* Then add Service Fee Percent, Tax Type and Tax Class if applicable
* Cash Discount Disclaimer is set to default. It is recommended to leave this language as is.
* Click Save changes.

Enable ACH:
* Add Title: This is set to ACH/Bank Clearing by default
* Add Description: This is set by default: Pay with ACH via the Choice Payment Gateway. You can make changes if needed.
* Add your Merchant Name
* Add Username/Password/Device Credit Card GUID that was provided by Choice
* Click Save changes.

== Screenshots ==

1. Payment methods enabled
2. Configure payment methods

== Changelog ==

= 2.1.2 - 2022-11-23 =
* bug fixes for order number validation

= 2.1.1 - 2022-11-21 =
* minor bug fixes

= 2.1.0 - 2022-11-20 =
* fix JSON encoding updates to the Choice Payment API
* fix data validation during checkout

= 2.0.6 - 2022-11-19 =
* minor updates

= 2.0.5 - 2022-05-22 =
* security fixes
* minor bug fixes

= 2.0.4 - 2022-05-11 =
* fixes for recurring billing and ACH payments
* save token securely in subscription order

= 2.0.3 - 2022-05-10 =
* fixes for subscription processing

= 2.0.2 - 2022-05-02 =
* security fixes

= 2.0.1 - 2022-02-08 =
* update URLs to payment APIs

= 2.0.0 - 2021-12-03 =
* adds ACH payment method
* adds service fee option with cash discount
* removes API key option, username and password is required for authentication with PAYNT API
* configurable cash discount disclaimer text
* adds cash discount disclaimer in checkout and email templates

= 1.0.3 - 2021-10-29 =
* simplify expiration payment form fields
* fix input validation of card number
* limit input length on form fields

= 1.0.2 - 2021-09-28 =
* bug fix: Cannot turn off Sandbox mode, it is always on

= 1.0.1 - 2021-09-24 =
* adds support for refunds
* minor bug fixes
* fix order notes missing order total in payment message

= 1.0.0 - 2021-09-23 =
* initial public release
* tokenize credit card data
* authorization only
* delayed capture
* debug logging to WooCommerce logs
