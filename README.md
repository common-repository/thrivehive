ThriveHive WordPress Plugin
===

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/19101fbe71794f45aa08af8a141eb7b4)](https://www.codacy.com?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=propelmarketing/warp-prism&amp;utm_campaign=Badge_Grade)

Contributors: thrivehive
Donate link:
Tags: web analytics, tracking, thrivehive thrive hive
Requires at least: 3
Tested up to: 4.1
Stable tag: .1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will automatically instrument a site with ThriveHive's tracking code.

## Description

This plugin will automatically instrument a site with ThriveHive's tracking code, insert a tracked phone number, and insert a tracked form.

## Installation

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Get your account's tracking assets and paste them in the appropriate field in the plugin settings.
4. The tracking code will automatically be added to your site. To insert a phone number, add this PHP to your template in the desired location on your page. get_option ('th_phone_number'). To insert a form, use get_option('th_form_html').

## Frequently asked questions
1. How do I get the assets to start using this plugin?
Answer: To get started with ThriveHive, you'll need to create an account with ThriveHive at http://thrivehive.com/free-trial/signup. Once you have an account, go to the account page to get your tracking code (which contains your account ID). ThriveHive will help you get your form HTML and tracked phone lines as well.
2. How do I insert the phone number and form into my pages, posts, and templates?
Answer: There are two ways to insert the forms and phone numbers. The first uses "shortcodes" which you can use in your pages and posts. Just type [th_form] or [th_phone] in a post or a page and it will pull in the appropriate asset (assuming you have set one up in the ThriveHive plugin settings page. To insert your phone number and form into your php template files, you will need to include <?php th_display_form(); ?> or <?php th_display_form(); >? in your template.

## QA Prior to Deployment
Make sure that your code was updated in the skeleton directory of QA(`alice.metallicocean.com`) and site spinup was tested prior to releasing to production. To do this...

1. Download `whm.pem` from Google Drive Dev and place it in `~/Thrivehive/keys/`
2. Run `sh scripts/update_skeleton_directory.sh`

## --- IMPORTANT ---

The version number of the plugin MUST COMPLY with the following format:

```
vX.X(XX)
```

For example:

`v1.1 (Regular release)`

or

`v1.101 (Bugfix)`

The version needs to be updated in thrivehive.php and the change needs to be added to the changelog here along with the version number.

## Deployment
1. Download `whm.pem` from Google Drive Dev and place it in `~/Thrivehive/keys/`
2. Run `sh scripts/production_deployment.sh`

-or-

1. Zip the contents of the repo into a zip named `thrivehive.zip`. If you are inside the warp-prism folder you can select everything inside and put that into a zip.
2. Now you need to initiate an SFTP connection to all of the wordpress servers:
 - customers.thrivehivesite.com
 - customers2.thrivehivesite.com
 - customers3.thrivehivesite.com
 - customers4.thrivehivesite.com

 Creds for these can be accessed from AWS EC2.

 <b>NOTE</b>: EC2 says to use the "WHM KeyPair.pem" file - use the "WHM.pem" file on dropbox instead.

3. Copy the thrivehive.zip file into `/root/plugins` folder on each of the servers.
4. Also on each of the servers copy the contents of the repo (sans `thrivehive.zip`) into `/root/cpanel3-skel/public_html/wp-content/plugins/thrivehive`
5. Now that we have all the files on the servers, on each server we can run the script `/scripts/update_thrivehive_addins.py --plugin`
6. These scripts may run for a while, but once they are done, all plugins should be up to date!

Here's a simple script to replicate this process:

```
cd ~/thrivehive/repos/warp-prism
git pull
cp -r ~/thrivehive/repos/warp-prism /tmp/
cd /tmp/warp-prism
rm -rf .git*
rm -rf .circleci
zip -r /tmp/thrivehive.zip .
for c in customers customers2 customers3 customers4; do
  server="${c}.thrivehivesite.com"
  scp /tmp/thrivehive.zip $server:/tmp/
  ssh $server "cp /tmp/thrivehive.zip /root/plugins/"
  ssh $server "unzip -o /tmp/thrivehive.zip -d /root/cpanel3-skel/public_html/wp-content/plugins/thrivehive/"
  ssh $server screen -d -m "/scripts/update_thrivehive_plugin.py"
done
```

## Update the Subversion Repo - ask somebody for the login creds
```
svn co https://plugins.svn.wordpress.org/thrivehive
cp -a ~/thrivehive/repos/warp-prism/. thrivehive/trunk/
rm -rf thrivehive/trunk/.circleci
rm -rf thrivehive/trunk/.git
rm -rf thrivehive/trunk/.gitignore
cd thrivehive
svn add --force * --auto-props --parents --depth infinity -q
svn commit -m "mygitbranch"
```

## Screenshots

## Changelog
* V 2.602 Fixed preview changes link for existing pages
* V 2.601 Added wysiwyg support for remove logo
* V 2.6 Added endpoint to duplicate pages
* V 2.512 Fixed issue with changing button id in header
* V 2.511 Fixed redirect when logging in using params
* V 2.51 Added Thrivehive Wysiwyg Button widget
* V 2.5 Added Wysiwyg button functionality
* V 2.301 Remove slashes and ensure correct character encoding for saved CSS
* V 2.3 Added ability to login using params
* V 2.201 Bugfix for newly spun up Changeling sites data not loading
* V 2.2 Added endpoint to expose the website vertical/template
* V 2.1 Added endpoint to search for Global Styling categories and values
* V 2.0 Enable support for new ThriveHive website editor
* V 1.138 Return th_editor on get_posts
* V 1.137 Fixing bug where data was being returned when no additional images were necessary
* V 1.136 Fixing bug where drafts that are scheduled are published right away
* V 1.135 Hotfix for 1.134 added logic for $currentPostStatus
* V 1.134 Fixing bug with published post urls changing when updated
* V 1.133 Returning date for blog posts in get_posts endpoint
* V 1.132 Added paging support for gallery images
* V 1.131 Adding support for scheduling blog posts
* V 1.130 Fixing an issue preventing meta values from being set on post update
* V 1.129 Modifying Youtube shortcode to selectively load scripts depending on website editor version
* V 1.128 Extracting shortcode rendering logic and adding an endpoint to utilize it
* V 1.127 Adding set meta value capability for posts
* V 1.126 Adding in do_shortcode to snippet rendering and fixing parallax css
* V 1.125 Fixing a bug with clearing css cache, and logo min height css
* V 1.124 Adding setting of website phone number via the API
* V 1.123 Adding support for non-hardcoded forms where applicable
* V 1.122 Fixing issues with static maps API
* V 1.121 Forcing a reauth when viewing draft posts if not logged in.
* V 1.120 Fixing bug with header style options and json deserialization
* V 1.119 Adding support for user-tokens in snippets
* V 1.118 Adding support for clearing WPFC Cache
* V 1.117 Fixing bug with fontawesome yelp icon
* V 1.116 Fixing bug with fontawesome twitter link
* V 1.115 Use 'target' parameter when creating a menu item with an internal link
* V 1.114 Fixing some potential errors that we might see on non managed sites.
* V 1.113 Fixing single quote bug with button text
* V 1.112 Adding in fontawesome social media icons
* V 1.111 Fixing a potential bug with a blank PDF shortcode
* V 1.110 CSS fixes: limit prior quattro bugfix to quattro, fix bugs in quattro and lifestyle pro themes
* V 1.109 Adding endpoint for getting blog post permalinks
* V 1.108 Fixing potential bug with empty galleries
* V 1.107 Updating AIOSEOP options when featured image is set.
* V 1.106 Sugar slider styling: support full-width and hiding prev/next buttons
* V 1.105 Fixing a bug with the google maps rendering
* V 1.104 Adding a feature to hide/show title on posts/pages
* V 1.103 Include featured image data in response to set_featured_image
* V 1.102 Fix usage of $wpdb->prepare()
* V 1.101 Add support for reusable snippets and posts' featured images
* V 1.100 Support custom title when adding a post's menu item
* V 1.99 Minified CSS
* V 1.98 Added changes that didn't make it in previous commit
* V 1.97 Bug fix for CSS selector for floating last-child in columns
* V 1.96 Bug fix for inconsistent default layout display
* V 1.95 Use 5px margin on all sides of images
* V 1.94 Fixing a bug with pdf uploads
* V 1.93 Blocking comments marked as spam from syndicating
* V 1.92 Syndicating blog comments
* V 1.91 Fixing a bug with the update call when we upgrade tables
* V 1.90 Allowing forms to be rendered with what they represent in thrivehive and storing them locally
* V 1.84 Temporarily removing auto slug generation when titles change
* V 1.83
    * Fixed a bug with updating blog post categories
    * Fixed a bug with relative urls for social media links (patch for frontend bug)
* V 1.82 Allowing users to set category names to whatever they want
* V 1.81 Minor bug fix with displaying PDF links only
* V 1.8 Minor Release Featuring:
    * Added target setting to buttons
    * Added target setting to nav items
    * Added links only for PDFs
* V 1.74 Fixing a bug with pinterest pinning and images that are too small
* V 1.73 Fixed small bug with margin on gallery slider images
* V 1.72 Fixing a small bug with social widget settings on the sidebar
* V 1.71 Fixing minor bug in new pdf properties
* V 1.7 Adding mappings so that we can move to our new PDF embedder plugin
* V 1.69 Fix for slugs switching back and forth on post update
* V 1.68 Temporary fix for plugin version check
* V 1.67
    * Added default margin to images in .content
    * Added float right to <li> elements in #menu-main
    * Adding in endpoints for getting and setting genesis layout for pages.
    * Adding in sharing for pinterest and linked in on blog pages and phone widget header editing
    * Adding in plugin versioning checks to detect version issues in TH
* V 1.65 Fix for issues with PDF uploads and poor thumbnails for them
* V 1.64 Fix for older PHP version
* V 1.63 Adding dynamic logo tweaks
* V 1.62 Fixing but with landing page template showing nav menu
* V 1.61 Fixing a bug with displaying landing pages in TH
* V 1.60 Fixing a bug introduced with pdf uploads in 1.59
* V 1.59 Fixing a bug with older versions of PHP and accessing the post controller
* V 1.58 Fixing bug with non canonical category slugs and saving posts
* V 1.57 Adding PDF management
* V 1.56 Fixing an issue with landing page template and newer versions of genesis
* V 1.55 Fixing bug with the map shortcode and <br> tags
* V 1.54 Auto-approving comments our authors make in reply to other comments
* V 1.53 Updating comment management to give the gmt date
* V 1.52 Updating get_all_users to return email addresses
* V 1.51 Updating user creation to include email address and adding method to update it
* V 1.5 Minor release changing functionalities for filtering pages by types and comment management
* V 1.40 Fix for excessive slashes in seo homepage
* V 1.39 Fix for RSS XML Feed
* V 1.38 Adding footer changes to include address
* V 1.35 Major release to fuix issues with Metro Pro theme
* V 1.28 Major release to support custom header style options
* V 1.27 Fix for YouTube video tracking
* V 1.26 Fix for overlapping forms on landing pages
* V 1.25 increasing version number to allow  update on some sites
* V 1.24 Major release supporting forms, custom css/js, lightbox, categories, authors, background * image
* V 1.23 Added comments field to default contact us form
* V 1.22 Fix for YouTube embed showing up at top of page/post
* V 1.21 Fix for Youtube tracking environment url
* V 1.20 Major release supporting new shortcodes for youtube and image gallery, LinkedIn and Yelp * widget buttons,
* V 1.10 Fixed a bug for opening blog posts in thrivehive
* V 1.09 Styling fix on contact us form generator
* V 1.08  Function naming conflict issue with a specific theme
* V 1.07 Fix to make sure menu item shows up, and shows up last
* V 1.06  Bug fix for theme page templates not showing up
* V 1.05  Bug fix for PHP Version <5.4
* V 1.04  Updating social buttons to be optional
* V 1.03  changed social buttons to be echo'd to the page rather than written directly
* V 1.02	Adding validation for current PHP version
* V 1.00  MAJOR Release integrating with the new Thrivehive wordpress interface
* V 0.59: Fixing but with getting blog post content
* V 0.58: Changing the method for getting public previews
* V 0.54: Added rewrite flushing on activation
* V 0.51: Bug fix for creating blog posts with no title having all content wiped out
* V 0.5:  Major update adding integration with ThriveHive to create and view blog posts as well as various usability enhancements


## Upgrade notice
