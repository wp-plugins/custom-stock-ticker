=== Custom Stock Ticker ===

Contributors: Relevad
Tags: custom stock ticker, stock ticker, stocks, ticker, stock market, stock price, share prices, market changes, trading, finance, financial
Requires at least: 3.8.0
Tested up to: 4.1.1
Stable tag: 2.0.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create customizable moving stock tickers that can be placed anywhere on a site using shortcodes.


== Description ==

Custom Stock Ticker plugin creates customizable moving stock tickers that can be placed anywhere on a site using shortcodes. You can choose from multiple themes and customize the colors, size, speed, text, and symbols displayed. 

Features:

 * Choice of stocks
 * 4 Pre-built skins/themes
 * Appearance customizations: width, height, background color, opacity, scroll speed, number of stocks displayed at one time, text color, font size, font family, and opacity
 * Ticker features: vertical lines, up/down triangles, different colors for up/down
 * CSS input for entire widget (allows for alignment, borders, margins, padding, etc.)
 * Custom stocks for specific categories
 * Preview of Stock Ticker after saving on settings page
 
Depricated support for pre-defined html colors:
 * Color strings such as blue, yellow, limegreen could be used in old version of this plugin. These are no longer supported and may be lost upon saving settings.

Requirements:

 * PHP version >= 5.3.0 (Dependent on 5.3 functionality. Plugin will not work without 5.3 or higher)
 * Jquery version 1.6 or higher (wordpress 4.1 ships with 1.11.1)
 * Ability to execute wordpress shortcodes in the location(s) you want to place stocks. (see installation)

This plugin was developed by Relevad Corporation. Authors: Artem Skorokhodov, Matthew Hively, and Boris Kletser.

== Installation ==

1. Upload the 'custom-stock-ticker' folder to the '/wp-content/plugins/' directory

1. Activate the Custom Stock Ticker plugin through the Plugins menu in WordPress

1. Configure appearance and Stocks symbols in "Relevad Plugins"->StockTicker

1. Place Shortcodes
 * Pages / Posts: 
  Add the shortcode `[stock-ticker]` to where you want the ticker shown in your post/page.
 * Themes: 
  Add the PHP code `<?php echo do_shortcode('[stock-ticker]'); ?>` where you want the ticker to show up.
 * Widgets: 
  Add `[stock-ticker]` inside a Shortcode Widget or add `<?php echo do_shortcode('[stock-ticker]'); ?>` inside a PHP Code Widget
 
  There are many plugins that enable shortcode or PHP in widgets. 
  Here are two great ones: [Shortcode Widget](http://wordpress.org/plugins/shortcode-widget/) and [PHP Code Widget](http://wordpress.org/plugins/php-code-widget/)


== Frequently Asked Questions ==

= Can I get data for any company? =

The current version of the plugin supports almost all stocks on NASDAQ and NYSE.

= How do I add stocks to the ticker? =

All stocks can be added in the Stock Ticker settings page (Settings -> StockTicker). 
Go to Settings -> StockTicker
Type in your stock list separated by commas in the Stocks input box.

= How do I place the ticker into a widget? =

You need a plugin that enables shortcode or PHP widgets.

There are plenty of such plugins on the WordPress.org. 
These worked well for us: [Shortcode Widget](http://wordpress.org/plugins/shortcode-widget/), [PHP Code Widget](http://wordpress.org/plugins/php-code-widget/)

Install and activate the such your desired shortcode or PHP widget plugin and add it to the desired sidebar/section (Appearance->Widgets)

If you added a shortcode widget, type in `[stock-ticker]` inside it.

If you added a PHP widget, type in `<?php echo do_shortcode('[stock-ticker]'); ?>` inside it.

That will display the ticker in the appropriate space.

= What is the Customize Categories section all about? =

If you want to display a different set of stocks for specific categories on your page, you can specify them for each category. If you leave a field next to a category blank, the default list will be loaded.

= Can I place two tickers with different formatting on one page? =

Yes, simply create a new shortcode from the shortcodes list table page (click add new), then place it's shortcode onto the page where ever you want. Each shortcode can be formatted completel independently and even have their own individuallized stock lists.

= The ticker is too big! Is there some way to shrink it? =

Yes. Put in a smaller number in the width under Ticker Settings (Settings->Stock Ticker). Width is in pixels. 



= Something's not working or I found a bug. What do I do? =

First, please make sure that all Relevad Plugins (including Fit-My-Sidebar) are updated to the latest version.
If updating does not resolve your issue please contact plugins AT relevad DOT com
or
find this plugin on wordpress.org and contact us through the support tab.


== Screenshots ==

1. Example of the stock ticker on live site

2. Another example of the stock ticker on live site

3. More examples of stock ticker themes

4. This is what the back-end looks like

5. Here's how to place the ticker on the site using a PHP Code Widget

6. Here's how to place the ticker inside a page using shortcode


== Changelog ==

= 2.0.1 =

* Changed precision to 3 decimal places up from 2
* Bugfix with Upgrade path from earlier versions
* Bugfix when database tables do not use default auto-increment config
* Bugfix for wordpress plugin standards and best practices

= 2.0 =

* Added functionality to define multiple completely distinct shortcodes. Full style customization and stock lists avaialble.

= 1.4.1 =

* Fixed index issue in database table definition
* Fixed a few typos

= 1.4 =

* Stock tickers are now stored in a separate database table
* Stability improvements incase of version miss-match

= 1.3.5b =

* Bugfix with UI

= 1.3.5a =

* Revised class names to reduce css conflicts

= 1.3.5 =

* Aded persistent UI state on admin panel

= 1.3.4 =

* Changed several HTML input fields in the admin pannel to different types if the browser supports them

= 1.3.3 =

* minor UI bugfix for 1.3.2

= 1.3.2 =

* Added plugin database revision support

= 1.3.1 =
  
* Added timeout to retreive stock data
* Code reorganization

= 1.3 =

* Added alternative csv parser for php < 5.3
* Depricated shortcode parameters
* Added warnings if text sizes + width would lead to overlap
* Cleaned up data storage for plugin options
* Misc minor code cleanups

= 1.2 =

* Updating to maintain compatability with custom-stock-widget changes
* Minor Admin UI formatting improvements

= 1.1.1 =

* Fixed issue with extra white space causing : "Warning: Cannot modify header information - headers already sent"
* Fixed issue with saving checkbox settings

= 1.1 =

* Code clean up and optimization
* Fixed major bug with multiple tickers on the same page
* Numerous minor bug fixes

= 1.0 =

Plugin released.

== Upgrade Notice ==

= 1.4.1 =
 
Major functionality upgrade. Update now to be able to define multiple shortcodes with independent stock lists for each.
