=== Business Directory Plugin ===
Contributors: businessdirectoryplugin
Donate link: http://businessdirectoryplugin.com/premium-modules/
Tags: wordpress business directory, wordpress directory, wordpress directory plugin, wordpress directory theme, wordpress business directory, business directory,classified ads,classifieds,directory plugin,business directory plugin,directory widget, church directory, address book, address, member directory, members directory
Requires at least: 3.0
Tested up to: 3.5.2
Last Updated: 2013-Jul-5
Stable tag: tags/3.1.1
License: GPLv2 or later

Business Directory Plugin allows you to build local directories, business provider listings, Yellow-Pages style business directories, Yelp-like review sections and more. 

== Description ==

[Main Site](http://businessdirectoryplugin.com/) | [Support Forum](http://businessdirectoryplugin.com/support-forum/) | [Documentation](http://businessdirectoryplugin.com/docs/) | [Showcase](http://businessdirectoryplugin.com/showcase/) | [Premium Modules](http://businessdirectoryplugin.com/premium-modules/)

Business Directory Plugin is the most popular, widely-used, and best-supported WordPress Business Directory plugin available.  Increase interaction on your website, improve customer retention and a add revenue-generating section to your site with Business Directory Plugin!

Business Directory Plugin allows you to build local directories, business provider listings, Yellow-Pages style business directories, Yelp-like review sections, create a church directory, build an address book and much more.  You can add any kind of directory using Business Directory plugin.

= Features =
* Fully customizable form fields
* Accept payment for listings OR have free listings
* Allow for featured/sticky listings for an upgrade fee
* Create multiple fee plans, which can be assigned to categories for posting
* Support for reCAPTCHA to avoid spam listings
* Users who post can edit listings without access to WP dashboard
* SEO Friendly
* Rate businesses in your directory (now with Microformat support) (Ratings Module)
* Display the location of the business (categories, search, details--now with custom map sizes) (Google Maps Module)
* Location based result filtering (Regions Module) (NEW!)
* ZIP code radius searches (ZIP Search Module) (NEW!)
* Assign features to paid or sticky listing plans to give users incentive to upgrade (Featured levels module) (NEW!)
* Allow users to upload PDFs (File Upload Module) (NEW!)
* Display Category Icons/Allow Parent-Child Category Navigation (Enhanced Category Module) (NEW!)

For a complete and detailed list of features, please visit:  http://businessdirectoryplugin.com/features/
 
Please report any bugs you find via http://businessdirectoryplugin.com/support-forum/  

= Premium Module Links: =
These are optional modules that you can use to enhance Business Directory Plugin's functionality and
support future development:

NEW! [Featured Levels Module](http://businessdirectoryplugin.com/premium-modules/featured-levels-module?ref=wp)

NEW! [ZIP Code Search Module](http://businessdirectoryplugin.com/premium-modules/zip-search-module?ref=wp)

NEW! [File Upload Module](http://businessdirectoryplugin.com/premium-modules/file-upload-module?ref=wp)

NEW! [Regions Module](http://businessdirectoryplugin.com/premium-modules/regions-module?ref=wp)

[Ratings Module](http://businessdirectoryplugin.com/premium-modules/ratings-module?ref=wp)

[Google Maps Module](http://businessdirectoryplugin.com/premium-modules/google-maps-module?ref=wp)

[PayPal Gateway Module](http://businessdirectoryplugin.com/premium-modules/paypal-module?ref=wp)

[2Checkout Gateway Module](http://businessdirectoryplugin.com/premium-modules/2checkout-module?ref=wp)

For more information and support please visit [http://businessdirectoryplugin.com](http://businessdirectoryplugin.com?ref=wp)
For plugin support please visit [http://businessdirectoryplugin.com/forums](http://businessdirectoryplugin.com/forums)

If you love the plugin, [please consider purchasing a Premium Module](http://businessdirectoryplugin.com/premium-modules/?ref=wp)!


== Credits ==
Copyright 2009-13, D. Rodenbaugh 
Original Author: A. Lewis

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA



== Installation ==
Please see our detailed [Installation Guide](http://businessdirectoryplugin.com/docs/#install), the [Quick Start Guide](http://businessdirectoryplugin.com/quick-start-guide/) for common how-to scenarios, and our [Initial Setup](http://businessdirectoryplugin.com/docs/#setup) page for instructions on how to get going quickly with Business Directory Plugin.

[Full documentation](http://businessdirectoryplugin.com/docs/) for the plugin is also available.

If you are having problems please visit [support forum](http://www.businessdirectoryplugin.com/support-forum/) for help.


== Screenshots ==

[Visit the main site for feature lists and screenshots](http://businessdirectoryplugin.com/features/)


== Changelog ==

= Version 3.1.1 =
* Fixed add listing shortcode issue
* Fixed featured levels field association save problem
* Changed workflow to not display fee step when nothing is payable
* Fixed Edit button problem (where it showed the Add Listing screen instead of edit)
* Fixed search results to use the blog setting instead of 10 listings
* Fixed CSV import issue

= Version 3.1 =
* New module: Featured Levels
* New module: Category Icons
* New module: ZIP code search
* Allow search form to be hidden when results are displayed. 
* Fix handling of NULL/empty column values in some MySQL databases that confused the Form Fields API. 
* Do not fail when a listing's 'sticky' flag appears twice (or more) in the database .
* Honor edit-post status after a listing edit .
* Make WordPress generate valid permalinks for a recently submitted listing
* Use update_post() instead of publish_post() to force WP not to trash existing permalink slugs when bulk-publishing listings
* Do not print empty subcategory lists in the main category view. 
* If options are manually specified in a tag field use them as choices instead of the whole tag taxonomy list. 
* Add a workaround for a known PHP issue where empty and zero values are incorrectly compared. 
* Improve title handling compatibility with Yoast SEO plugin.  
* Reviewed compatibility with PHP 5.3 & WP 3.5.2. 
* Make uninstall process actually delete all settings and BD content. 
* Do not display the upgrade to featured option when submitting a listing if featured listings are disabled. 
* Fix numbering of submit process steps. 
* Make category fields respect configured category ordering when they are rendered. 
* Categories are now displayed hierarchically when using checkbox/radio category fields. 
* Fix unpaid listing count in admin. 
* Do not change the listing status back to pending if it was already published when a transaction is approved. 
* Extend Yoast SEO support to include category & tag descriptions. 
* Payment process page now displays error messages that come from the payment gateway. 
* Allow plugin to work correctly when its directory is a symlink. 
* Workaround Relevanssi plugin causing problems uploading images. 
* Correctly escape & unescape strings when saving and displaying a field's name. 
* Restore 'preview' functionality for listings admin-side. 
* Use category/tag description for category/tag pages metatags. 
* Compatibility with XML site maps reviewed. 
* Fix handling of comma/semicolon-separated tags in CSV import. 
* Make category fields work correctly when using radio buttons. 
* Add a pending expiration/renew ad email that is sent a configurable number of days before the actual listing expiration happens
* Listings get republished after a renewal. 
* New core filters/actions to allow plugins to display and handle field settings in the admin. 
* Improve handling of expired categories in listings admin-side. 
* Allow users to opt-out of a listing renewal. 
* Add a new 'expired' filter to quickly find listings with expired categories. 
* New "Transactions" section to administrate listing transactions.
* Use a better return/cancel URL for the PayPal module .
* Fix SQL query that returned incorrect results when filtering listings by rating in Advance Search. 
* Allow ratings tooltips to be overridden by the user. 
* Change default start tooltips to more sane default values. 
* Geolocate listings server-side instead of using the JS API to avoid hitting Google Maps limits. 
* Performance enhancements. 
* Show all relevant listings in a category or search results page instead of just the ones visible on the page. 
* Use newlines instead of commas to separate address parts when displaying a map pin.  
* Use a special hash to mark listings that need to be geolocated to avoid unnecessary hits to the Maps API.
* Do not fail when an expected region form field was manually deleted by the admin
* Workaround WordPress showing incorrect listing counts for the Regions taxonomy. 
* Make Regions cache regeneration work even if some region fields are not present. 
* Fix display of incorrect regions in the sidelist when no region field was visible. 
* Allow region selector to be completely hidden. 
* Show an admin warning when regions is incorrectly configured
* Allow listings to be assigned a region admin-side even before the first save. 
* Reset current page variable when changing the current region. 
* Move all settings to "Manage Options" section. 
* Add a display flag specific for the region selector. 
* Fix region fields display so that the order in which the fields appear doesn't affect functionality. 
* New location search widget (ZIP code module).
* Added support for US/UK and US+UK postal code databases in ZIP module.


= Version 3.0.2 =
* Further additions to allow for restoration of "invalid" fields for 3.0 users affected by 2.2->3.0 upgrade


= Version 3.0.1 =
* Added code to workaround critical WordPress dbDelta issue with PHP 5.2.x users 
* Fixed category display bug where "Hide Empty Categories" would hide all categories instread.


= Version 3.0 =
* New form fields API
* Allow for label hiding in form fields
* New URL field
* URL Validator now supports more schemes other than http or https
* Social fields marked as "optional" do no longer fail to validate when empty
* Make social fields work correctly with URL Validator
* Fix an issue with single checkbox fields
* Now possible to specify if a URL field has rel="nofollow" or not
* Fix a re-ordering issue with bottom most fields
* Fix an issue with radio button fields showing labels twice
* Restore ability to hide the search form
* Simplify WordPress queries to improve loading speed for directory pages
* Search results are now sorted using the directory configured setting
* Added option to notify admins via email when new listings are posted
* Allow listings to be upgraded to featured during the initial submit process, This option is controlled by the "Offer upgrades during submit process?" setting.
* Include listings in children categories in search results when searching a category
* Make tag association work correctly with checkboxes
* All BD buttons have now class 'button' or 'submit' depending on their type for easier theming
* Form elements in widgets, search form, etc. now use specific IDs for easier theming
* Handle accented words and apostrophes correctly when importing listings via CSV files.
* Allow users with edit permissions to change listing images in backend
* Fix category posts being incorrectly counted when there were expired listings
* CSV import now complaints when a required field column is not present
* CSV import now complaints when a user isn't found
* Do not change listing author when using quick edit in the admin
* Plugin checked for WPMU compatibility
* Preserve the language parameter in BD links when qTranslate plugin is found to be active
* Fix image issue due to conflicts with Jetpack plugin
* Add "Listings Tagged: [tag]" to the title & header of tag pages
* Fix an issue where paid listings were being published even when the payment was skipped
* Add Lightbox support
* Admin menus minor reorganization
* Add a "Clear" button to the search form
* Make use of listing emails before others for delivering renewal emails
* Detect some conflicting social plugins (AddThis Social Bookmarking Widget, Jamie Social Icons, TF Social Share, Shareaholic, Quick AdSense) and disable them on directory pages
* Call 'handle_action' further down the action pipeline to avoid problems with other plugins
* Rewrite rules and query vars cleanup
* Fix canonical redirects so the BD page works even when set as home page
* Lots of deprecated functions removed and code cleanups.
* Better detection of the current action being performed inside BD pages
* Do not overwrite titles of pages outside of BD. Fixes a conflict with AWPCP
* Produce correct 'canonical' link head tags for listings, categories and tag pages
* Make BD support Yoast SEO plugin's metadata and title tags on listing pages.
* Include RSS feed links for listings and categories in directory pages


= Version 2.2 =
* Added support for Regions module
* Fixed CSV import error for PHP character munging


= Version 2.1.6.1 =
* Fix PHP 5.4 error during install
* Fix error "Fee must be associated to a category" when a category was selected.


= Version 2.1.6 =
* Support for all currencies supported by PayPal.
* Do not make URL fields required when marked as optional.
* When searching a field with multiple options, the options are now ORed.
* Twitter field type for @ handles. On display, a "Follow" button appears.
* Facebook field type for pages. On display, a "Like" button appears.
* LinkedIn field type for company IDs (not supported for individual profiles). On display, a "Follow" button appears.
* Image settings in "Manage Options" are always respected (no approximate sizes are used). Thumbnails of the correct size are automatically created when an image is uploaded.
* If image settings change, new thumbnails are created on the fly when a listing is visited. This happens only on "single view" to avoid consuming a lot of server resources.
* When uploading images at the submit/edit listing form useful errors are now displayed. This happens when images are of a bigger size than allowed or are not really image files. Previously, it wasn't clear why an upload could fail.
* Long description can now be set to Optional, required fields that can't be changed have disabled checkbox shown
* Empty URL field labels are not displayed
* CSV importer fixes for descriptions with newlines
* Fixed PO file to include Directory button label
* Hide payment & fee info on posts from non-administrators
* Upgrade PayPal interface for compatibility alert due in Feb 2013 (HTTP headers)


= Version 2.1.5.2 =
* Added support for PayPal currencies:  BRL, TRY, MRY
* Fixed bug where upgrade of Website address did not auto-copy the link text
* Fixed advanced search issues with categories
* Fixed advanced search to use OR instead of AND for multi-select listboxes
* Fixed URL validator to not make field required when URL is empty
* Fixed permalink rules to work with index.php-based approach


= Version 2.1.5.1 =
* Fixed WP API change: Missing argument 2 for wp_kses()


= Version 2.1.5 =
* Added a Back to Directory button for listings
* Added sorting order for ratings (by ratings and count)
* Minor HTML tweaks for layout
* Added Link Text for Business Website (to create a complete anchor tag with SEO value)
* Changed fields to be hideable from Advanced Search Form
* Added better error messages displayed when payment gateways are not correctly configured for use
* Added payment skipping for admins, including a message that clearly shows the payments are being skipped because the current user is an admin
* Comments in listings are correctly displayed now
* CSV import now supports newlines in the import fields
* Contributors can't manage listings now
* Removed HTML comments from core templates that caused problems with some themes
* Category and tag names are now displayed in the page title
* Fixed Google Checkout Seller ID issue (removed field)
* Fixed labels for directory so that the colon is not outside the label
* rel attribute added to images
* Search widget added
* CSV import now supports multiple categories using the category separator
* Fixed stripslashes bug with field labels


= Version 2.1.4.1 = 
* Fixed issue with default display of form fields (caused fields to hide in listing)


= Version 2.1.4 =
* Added support for Ratings module
* Fixed problems with comments
* Fixed problem with featured listings not displaying on the page with the correct count
* Improved speed of queries for main listings
* Fixed search pagination issue with lengthy result sets
* Fixed issue where number of posts per page did not include featured listings


= Version 2.1.3 =
* Fixed shortcode usage problems with new codes
* Fixed permalink issues with categories and listings
* Added the Directory button back to most pages for simpler navigation.
* Added the ability to hide the directory button in the admin options
* Fixed HTML template issues for listings and single view pages (buttons showed HTML as text)
* Added error message when the main shortcode is not present on any page
* Fixed 2Checkout plugin for 2.1.2
* Fixed search page pagination
* Added back pagination API from WordPress for listings page, fixed bugs where post display was not honored
* Added checks for tags and slugs using permalink rules
* Fixed submit listing to only submit listing once when clicked repeatedly (or refreshed by accident)
* Added reCAPTCHA support to comments AND listings both
* Fixed CSV import issues with permalinks and dates
* Fixed payment gateway deactivation issues when plugin is upgraded.
* Improved search query speeds
* Added ability to select whether a field should appear on the listing's single view and/or excerpt view separately.
* Admins can edit / upgrade listings through the frontend regardless of the listing payment status.
* A confirmation dialog is now shown when deleting a listing.
* Comments on listings always follow the setting inside "Manage Options" (don't have to enable/disable comments individually on listings through the admin side).
* Fixed issue with custom CSS: files can be placed inside the "wp-plugin" directory now (per the docs).


= Version 2.1.2 =
* Fixed critical query issue that prevented short code pages from working (Add Listing/Edit Listing)


= Version 2.1.1 =
* Fixed Directory button to display in listings
* Minor CSS tweaks to show labels in bold
* Fixed bug with category and single listing links where they would not display when clicked (404 error)


= Version 2.1 =
* Added support for CSV import, including images in ads
* Added support for custom CSS extensions via CSS files outside of BD
* Added new shortcode names (easier to remember).  Old shortcodes still supported.
* Added support for parameters on WPBUSDIRMANMVIEWLISTINGS/businessdirectory-viewlistings to allow for single category display on a page.
* Upgraded search to have "Advanced Search" link now, all fields are searchable.
* Streamlined the submit process for Free ads (no longer asks for payment)
* Website URL opens in new window (new field option for URLValidated fields)
* New widgets:  Featured Listings, Latest Listings, Random Listings
* HTML emails:  Email sent in HTML format instead of text now
* Preview Form is back.
* Google Maps now recognized on Premium Modules panel as installed/not installed
* MASSIVE upgrade to theming/theme integration, much easier to get BD going now.  Still have override templates, but fewer than before.
* Page based navigation--allows for tighter theme integration and easier upgrades.  Requires the use of permalinks for pretty URLs
* Fixed problem with URL validator not working right.
* Fixed problem with IE9 and button display
* Fixed ad pagination problem.
* Fixed problem with ad expiration validation (where 9999 caused weird 404 errors)
* Category list call from API now working again.
* Subcategory display problem in admin panel fixed.
* Users must select a category when creating a fee plan
* Fixed bug with Manage Form Fields button on Main Menu (permission issue)


= Version 2.0.5 =
* Added ability to use bulk operations from admin menu (Upgrade/Downgrade, Set Paid, etc)
* Fixed problem with PayPal module not redirecting after payment on some sites
* Removed BD metadata from deleted posts (speeds searches)
* Fixed wp_pagenavi() warning
* Fixed bug where website field could not be cleared of data
* Fixed issue where admin could not edit other listings
* Added confirmation email to be sent after listing is complete
* Changed Upgrade to only work for Admins
* Updated plugin to use wpdb->query for all DB operations
* Fixed pagination problem with "Older listings" link
* Fixed duplicate search issue (duplicate results returned on searches)
* Fixed sticky/featured listing sorting problem to be consistent for category, listing and page views


= Version 2.0.4.1 =
* Fixed image upgrade issues (missing thumbnails, duplicate pictures in listings)
* Fixed edit glitch with empty tags field
* Added control over Submit a Listing button to show/hide.  Added support for other listing buttons as well.
* Fixed minor _x() calls with missing parameters
* Fixed an issue with submitting listings for all blank fields (PHP warning)
* Fixed issue where empty fields would display labels when no value was present

= Version 2.0.4 =
* Upgrade to image upload/management system
* Fixed form for creating new listings, both admin and user side
* Fixed listing renewal, emails now go out prior to expiration
* Payment modules upgraded to handle renewals
* POST variables now properly configured for Windows and Linux
* div's properly closed on Submit Listing
* Comments will display when option is checked now
* Directory definition issue with wp-content fixed
* Require registration option now properly displays in admin panel
* Field metadata is hidden and properly namespaced
* Sticky listings in category no longer cause No Listings Found
* Translation issue fixed
* Location field will not disappear on upgrade
* Invalid argument foreach() issue fixed
* Email now goes to site owner as expected
* Submit Listing layout problems fixed
* Submit listing duplicate fields display fixed
* Manage fees upgraded for renewals
* Image displays in list and single view properly now
* Editing listings will not randomly change category now
* Users can control which image is set for the thumbnail of a listing now
* Support for multiple categories in a listing added
* Add New Listing navigation bug fixed

= Version 2.0.3 =
* Autocreate required fields
* Directory admin menu set to be visible only to administrator role
* Content field will not show as link now
* Reply emails go to the business listing instead of site owner
* Listings without photos now no longer have placeholder
* Submit Listing no longer displays raw HTML code to user
* Plugin now uses UTF-8 functions to properly handle non-ASCII characters
* BD now integrates in a more friendly way with themes, avoids overriding when possible
* preg_match() warning fixed
* Extra line breaks removed from listing display
* Conflict with Yoast All in One SEO fixed
* Changing the slug now checks for reserved/already used slugs to avoid conflict
* Form fields now custom ordered
* Module detection code fixed, now displays which modules are installed for PayPal/2Checkout


= Version 2.0.2 =
* A revamped form-fields system that would allow plugins to create or use their own custom fields and validation rules.
* A new "Manage Form Fields" page which looks better and conveys information in a more friendly way.
* Custom fields can now be ordered as the user wishes. This ordering is used when displaying both the add/edit forms and listing information.
* Allow users to specify if a particular field should be displayed in excerpt or not at all.
* A better add/edit form-field page including some AJAX tricks to hide unnecessary fields. For instance, when you select a "Textfield" the "Options" field is hidden because the field isn't multivalued. The form is now also a standard WP form.
* A new "field description" field that could be used by admins to display additional information along the form fields.
* Validation when adding/editing custom form-fields to prevent users from removing or modifying required fields (like title, content, etc.)
* New validation framework for fields.
* Fixed bug where Featured ads could not be downgraded.
* Fixed delete field bug.
* Fixed ereg() warning bug.


= Version 2.0.1 =
* Fixed scripting error with booleans for some WP installs
* Fixed category 404 problems, clean up navigation
* Fixed short description display issue
* Fixed shortcode usage problems (content displayed below directory, no matter where shortcode was)


= Version 2.0 =
* Reset versioning to sensible convention
* Fixed search CSS and searching functionality
* Changed category management to simplify
* Added payment status, featured status into listing screen directly
* Google Checkout bug fix
* Fixed various scripting errors (22 bug fixes total)
* Button alignment fixes and admin UI cleanup



= Version 1.0 (1.9.6) =
Business Directory Plugin carries over from the last version of WP Business Directory Manager (1.9.5) Fixes include:

	1. Sticky listings bug fix
	
	2. Fixed broken submit form (raw html code displaying on front end)
	
	3. Tags fixed (previously clicking on tag links did not display any tag related content)
	
	4. New classes added for sticky listing styling
	
	5. Addition of basic search feature
	

Previous changelog from WP Business Directory Manager

Version 1.9.3 - Sticky listings fix for error where upgraded listings were not being listed under "Manage Featured". 
		
		Paid listings fix for error where paid listings were not being listed under "Manage Payments"
		
		Applied fix for payment calculation and handling where users are allowed to submit listing to multiple categories
		
		Fixed bug with images where number of images left wass sometimes greater than the total number of images allowed
		
		[ Thanks to Mike Bronner for his help with the below list of improvements and fixes to the plugin]

		- Fixed issue where shortcodes would always cause the Add Listing Form to display prior to content.

		- XHTML compliant.

		- More versatile arrangement of form elements.

		- Improved label handling (added proper IDs and FOR attributes).

		- Changed 'wp_dropdown_categories' function call to pass in an array of arguments, and adding arguments "echo" and "class".

		- Added functionality so that required fields receive a class name 'required'. This will allow for highlighting of required fields, etc.

		- Added "required" class to input fields in AddListing form, allowing them to be stylized.

		- Internal code optimization and cleanup.

		- Added hooks (filters) on functions that display HTML to allow user overrides of those functions in functions.php in their template. This is possible by removing the filters, then re-adding them and pointing to their own functions using the functions.php file in their theme.

		- "Add Listing" form will now submit back to itself. This will allow you to retain styling of the form separate from the directory. It is also no longer necessary for the Add Listing page to be a child of the Directory page.

		- Expanded slightly on the hookability of the add listing functionality, further allowing other developers to extend the plugin.

		- Updated text to be displayed on add listing form is user wasn't logged in.


		Bug Fixes:

		========

		- Addressed issue where shortcodes would always be displayed above content. Now you can nest shortcodes between content.

		- Updated HTML code to be XHTML complient 

		- Wrapped notifications in <p> tags, where not already done so.

		- Wrapped free-standing text in token function, where not already done so (__("text", "WPBDM")).

		- Fixed heading "Step 2" on image upload form to be H2 tag instead of H3 tag (syntactically correct, as there is otherwise no H2 tag).



Version 1.9.2 - Attempts to fix "undefined function flush_rewrite_rules" issue
		Fixes "Wrong datatype for second argument" error when using checkbox field type for category field
		Fixes default picture bug ( default picture not displaying )


Version 1.9.1 - Fixes a bug that defaults all extra fields to a type of text box regardless what the administrator selects. This bug was only present in version 1.9.0.

Version 1.9.0 - This version attempts yet again to fix the issue with categories showing "No Listings found in category" even while there are actually listings in the category. 
		Also attempts to fix situation where listings show up across all categories.
		Adds the ability to post listings to multiple categories. To use this you must set the category associated field as a checkbox type.
		Attempts to fix problem where when editing a listing if you had not uploaded any pictures the first time around it does not load the upload form on edit but says you don't have the ability to upload images
		Attempts to fix the issue with the View button not taking you to the actual listing in Internet Explorer browser
		Fixes a problem in the fields manager where if you saved a field without a label you then cannot delete the field.
		Fixes URL validation bug
		

Version 1.8.9 - This version has a small fix for the Google checkout page and a fix for some permalink issues causing a variety of problems with pages. Regarding the google checkout it seems some previous versions were displaying the shortcode at all times and not showing the actual page content. Change made: The line 
		
		add_shortcode('WPBUSDIRMANGOOGLECHECKOUT', 'wpbusdirman_do_googlecheckout'); 
		
		was changed to 
		
		if($wpbusdirman_hasgooglecheckoutmodule == 1)
		{
			add_shortcode('WPBUSDIRMANGOOGLECHECKOUT', 'wpbusdirman_do_googlecheckout');
		}
		
		and moved to below
		
		if($wpbusdirman_hastwocheckoutmodule == 1)
		{
			add_shortcode('WPBUSDIRMANTWOCHECKOUT', 'wpbusdirman_do_twocheckout');
		}
		
		The update also adds 3 new options under a new setting "Permalink Options" that allows you to change the wpbdm-directory slug, the wpbdm-category slug and the wpbdm-tags slug. Please take note of the fact that you cannot use "category" or "tags" or any already reserved WordPress taxonomy names for your slugs as it will make your pages fail to load.


Version 1.8.8 - This version has a small fix in a function related to the payment process on sites that have only one fee setting applied to all categories.

Version 1.8.7 - This version attempts to fix the issues with the contact form not sending the email to the business but instead sending it to the user who was logged in when the listing was submitted.
		
		This version attempts to fix the issues with some top level categories showing no listings found. 
		
		This version fixes the issue with the extra thumbnails not showing up in the listing
		
		This version should fix the issue with the cannot redeclare username_exists() error 
		
		
		

Version 1.8.6 - Removes call to include the WordPress registration.php in order to fix "Fatal error: Cannot redeclare username_exists() (previously declared in..." error 

Version 1.8.5 - Fixes a bug that prevented the payment step from kicking in (applies for version 1.8 and above only)

Version 1.8.4 - Removes automatic creation of pages at setup to correct bug that caused multiple instances of the business directory page and sub pages to be created.
`		Installation process now includes manual creation of the necessary pages.

Version 1.8.3 - Removes conversion process that attempted to convert listings made with versions 1.0 - 1.7 to the new 1.8 custom post type format


Version 1.8.2 - Fixes IE bug where "View" button does not work in listing excerpts
		Fixes Missing argument 1 for wpbusdirman_the_listing_meta() bug
		Fixes missing quote bug near img alt on a couple of lines 



Version 1.8 of Business Directory Plugin makes use of WordPress 3.0+ custom post types. The way the plugin works has been completely changed to use custom post types. When you upgrade to version 1.8 it will convert your previous installation to the new format. The new format conversion is not guaranteed to work 100% so you are very strongly advised if you have listings in your directory presently to export those listings in case you might need to re-add them manually. 

Plugin now uses a custom post type for posts related to the directory

Plugin now uses a custom category taxonomy for categories related to the directory

Plugin now uses a custom tags taxonomy for tags related to the directory listings

Plugin now allows for better integration with your theme. See the "Usage Doc" section for information on how to customize your theme to use the plugin functions.

Bug related to "Manage Fees" has been fixed. This bug resulted in admin being told payments were not turned on even though payments were in fact turned on.



== Upgrade Notice ==

1. If you are upgrading please export your current data first. There is always the possibility of something going wrong during the upgrading process. Failing to save your data before you upgrade can prove costly if something goes wrong and your data gets lost. Backup your database. Backup your files. 

2. If you are not using WP 3.0 or above you should not install Business Directory Plugin. 

3. If you are upgrading it is recommended that you do a manual upgrade rather than using the automatic upgrade option available via your dashboard. If you use the automatic upgrade option please, again, backup your data first.

== Usage Doc ==

For complete documentation on the plugin, please visit the [BD Documentation Site](http://businessdirectoryplugin.com/docs)

1. Setting up your categories

Go to your Dashboard and look for the "Directory" menu box. Listed under the Directory menu box are the links "Add New", "Categories" and "Tags". Click the "Categories" link and add your categories just the same way you would normally add categories under Posts->Categories.

2. Matching the design to your theme

Please visit this page for complete and up-to-date instructions on matching BD to your theme. 

[How to match BD to your theme](http://businessdirectoryplugin.com/docs/matching-the-design-to-your-theme/)

3. Adding a menu of the directory categories

To add a menu of the directory categories to your theme use the code below:
<?php global $wpbdmposttypecategory; wp_list_categories('title_li=&taxonomy='.$wpbdmposttypecategory); ?>
You can filter the function the way you would normally filter wp_list_categories. Just make sure it includes 'taxonomy='.$wpbdmposttypecategory so that it knows you only want the plugin directory categories.

4. Adding a latest posts widget

NEW:  BD now comes with 3 standard widgets--Latest Listings, Random Listings and Featured Listings.  Place in any
widget-ready theme.

== Frequently Asked Questions ==

For the definitive FAQ section, please vist the [FAQ Section of the Forum](http://businessdirectoryplugin.com/support-forum/faq)

1. How do I setup my categories?
You setup your categories under the "Directory" menu tab that will appear in the dashboard menu when you install the plugin. Under the Directory menu tab there is a link "Categories". You click this and add your categories the same way you do with your normal WordPress blog.

2. Why isn't the email address showing up in the listings?
The email address is not showing up because it is being programmatically blocked. The reason for this is to protect the privacy of your users so that their email address does not appear for public viewing where email harvesting bots can grab them and add them to spam email lists. 

3. Okay, I understand that the plugin is blocking the email address to protect the user's privacy, but I still prefer to display the email address in the listing pages. How can I do that?
To override the email block, go to the WPBusDirMan menu tab that appears in the dashboard menu. Click the "Manage Options" link. Look under "Miscellaneous settings". You will see an option "Override email Blocking?". Check that option and it should make the email address visible.





