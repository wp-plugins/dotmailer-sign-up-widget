=== dotMailer Sign-up Form Widget ===
Contributors: dotMailer
Donate link: http://www.dotmailer.co.uk
Tags: email marketing, newsletter sign-up
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add a "Subscribe to Newsletter" widget to your WordPress powered website.

== Description ==

Add the dotMailer sign-up form plugin to your site to allow your visitors to sign up to your newsletter and email marketing campaigns, sent using the dotMailer email marketing system. The email address of your new subscriber can be added to one or more dotMailer address books, which you can specify within your settings in WordPress. If you're not already a dotMailer user you can find out more about dotMailer at (http://www.dotmailer.co.uk)

== Installation ==

If you already have v1 installed, a message will pop up in the admin area of your WordPress account informing you that a new version is available. Simply update from there.

If you don't already have v1, log into your WordPress account and follow these steps:

1. Go to 'Plugins' in the left-hand menu
2. Select 'Add New'
3. Search for 'dotMailer Sign Up Widget'
4. Click on 'Install Now'
5. When installed, click on 'Activate Plugin'

The plugin will appear as 'dotMailer' in your left-hand menu.

For more detailed information on installation, find it at (https://dotmailer.zendesk.com/entries/23228992-Using-the-dotMailer-WordPress-sign-up-form-plugin-v2#install)


== Frequently Asked Questions ==

= Q. Which WordPress versions is the plugin compatible with? =
A. It is compatible with versions 3.0 or higher.

= Q. My site is hosted by WordPress.com. Will the plugin work for me? =
A. No. The plugin can only be uploaded to the installed version of WordPress (WordPress.org), not the hosted version (WordPress.com).

= Q. I'm receiving an error about the 'SoapClient' not being found. How do I fix this? =
A. You will need to enable the SOAP extension in the php.ini file (your installed PHP configuration file). This is typically done by uncommenting the following line:

extension=php_soap.dll


Depending on your host, you may not have access to this file. If you don't, you should contact your host to ask them to do this for you.

= Q. Can I select more than one address book to sign contacts up to? =
A. Yes you can. This latest version of the plugin allows you to put addresses into multiple address books.

= Q. Can contacts who have previously unsubscribed from my mailing lists re-subscribe through the plugin? =
A. Yes they can.

= Q. My contacts are not appearing in my address book. Why is this? =
A. Check you have followed the installation steps correctly and that your API email and API password appear exactly as they do in your dotMailer account. Remember that the API email is automatically generated and should not be changed. 

= Q. I can't drag and drop the widget from the 'Available Widgets' area. What should I do? =
A. There is an alternative way. Click on 'Screen Options' in the top right-hand corner and select 'Enable accessibility mode' which appears over to the left. 'Add' links will then appear on inactive widgets and 'Edit' links will appear on active ones. Clicking on 'Add' will allow you to choose where you want to place the widget on your page. 

== Screenshots ==

1. The plugin will appear as 'dotMailer' in your left-hand menu
2. Selecting an address book
3. Changing address book visibility
4. Reordering address books
5. Adding the form to your website
6. Click on screen options
7. Select  'Enable accessibility mode'. 'Add' links will appear on inactive widgets and 'Edit' links will appear on active ones

== Changelog ==

The current version of this plugin is 2.1. 

== Upgrade Notice ==

= 2.1 =
* Smashed out a fix for Jquery Min File, upgraded version to fix conflict

= 2.0 =
* Using the new Settings API.
* Extra features added.

= 1.1 =
* Fixed an error thrown 




