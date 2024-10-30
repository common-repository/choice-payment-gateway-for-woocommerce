## Choice Payment Gateway for WooCommerce
This extension allows WooCommerce to use the Choice Payment Gateway. All card data is tokenized using Choice Payment's PAYNT API.

## Installation

This plugin can be installed using the WordPress admin portal or downloaded as a zip file and extracted to the plugins folder.

## Usage

After installing the plugin, activate and configure settings in WooCommerce > Settings | Payment.  You will need a 
Choice Payment merchant account in order to use this plugin.

To sign up for a merchant account go to https://choice.xyz/.  If you already have
a merchant account but have not received your username and password or API key,
contact Choice Payment support at support@choiceinc.biz.


## Changelog

#### 2.1.2 - 2022-11-23
* bug fixes for order number validation

#### 2.1.1 - 2022-11-21
* minor bug fixes

#### 2.1.0 - 2022-11-20 
* fix JSON encoding updates to the Choice Payment API
* fix data validation during checkout

#### 2.0.6 - 2022-11-19
* minor fixes

#### 2.0.5 - 2022-05-22
* security fixes
* minor bug fixes

#### 2.0.4 - 2022-05-11
* fixes for recurring billing and ACH payments
* save token securely in subscription order

#### 2.0.3 - 2022-05-10
* fixes for subscription processing

#### 2.0.2 - 2022-05-02
* security fixes

#### 2.0.1 - 2022-02-08
* updates URL for payment APIs

#### 2.0.0 - 2021-12-03
* adds ACH payment method
* adds service fee option with cash discount
* removes API key option, username and password is required for authentication with PAYNT API
* configurable cash discount disclaimer text
* adds cash discount disclaimer in checkout and email templates

#### 1.0.3 - 2021-10-29
* simplify expiration payment form fields
* fix input validation of card number
* limit input length on form fields

#### 1.0.2 - 2021-09-28
* bug fix: Cannot turn off Sandbox mode, it is always on

#### 1.0.1 - 2021-09-24
* adds support for refunds
* minor bug fixes
* fix order notes missing order total in payment message

#### 1.0.0 - 2021-09-23
* initial public release
* tokenize credit card data
* authorization only
* delayed capture
* debug logging to WooCommerce logs

