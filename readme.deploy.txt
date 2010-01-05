=== dsSearchAgent: WordPress Edition ===
Contributors: amattie, jmabe
Tags: mls, idx, rets, housing, real estate
Requires at least: 2.8
Tested up to: 2.9
Stable tag: 1.0-beta8

This plugin allows WordPress to embed live real estate data directly into the blog. You MUST have a Diverse Solutions account to use this plugin.

== Description ==

[Diverse Solutions]: http://www.diversesolutions.com "Diverse Solutions, plugin author"

dsSearchAgent: WordPress Edition is an extension of our ([Diverse Solutions]) [MLS IDX solution](http://idx.diversesolutions.com). With this plugin, bloggers can embed **live** real estate listings (using what is known as *I*nternet *D*ata E*x*change, or IDX) into their blog's posts and pages using something WordPress calls "shortcodes" and into their sidebars using the included widgets. The plugin also functions as a full IDX solution by allowing visitors to search for, and view the details of, listings in the MLS.

## IMPORTANT REQUIREMENTS TO USE THIS PLUGIN:

* You must be an active member with a multiple listing service (MLS). This means that anyone other than real estate agents and brokers (and, in some MLS's, even agents are excluded) cannot use this plugin.
* The executives at the MLS must be progressive enough to allow the data to be syndicated to your blog from our (Diverse Solutions) API.
* You must have a dsSearchAgent account in order to get the required activation key.
* Your web host must be running at least PHP 5.2. PHP 5.2 has been out for 3 years at this point, so if they aren't using PHP 5.2, they're quite a ways behind the times. This is almost never an issue nowadays.

dsSearchAgent: WordPress Edition contains many advanced features that enable bloggers to create "sticky content," visitors to find properties they like, and search engines to crawl the MLS data so that the listings show up with the blogger's domain in the search engines. It is intended to be a real estate agent's / broker's all-inclusive interface between the MLS they belong to and their WordPress site / blog. Following is a very high-level overview of the plugin's functionality.

* It actually embeds the live MLS data INTO the blog -- **it does NOT use HTML "iframes!"**
* It is **extremely easy** to set up, requiring 17.43 seconds on average. It's downloaded and installed like any other WordPress plugin and there's only one field to fill in (the activation key) to activate all of the plugin's functionality.
* The plugin is **exceptionally fast**. In some cases, loading the MLS data is actually faster than loading the WordPress data!
* It has built-in support for WordPress shortcodes, allowing bloggers to **embed live listing data from the MLS** into their blog posts / pages.
	* The `idx-listings` shortcode embeds listings for particular areas *into* their blog pages / posts. For example, if a blogger typed `[idx-listings city="Laguna Beach" count="10"]` into their post, the 10 newest listings from the MLS in Laguna Beach would show up in place of that text when the post is displayed; each listing / photo links to the full property details. The data is *live*, so whether the post is viewed the next day or the next month, the 10 newest listings would always be displayed.
	* The `idx-listing` shortcode embeds a single listing into a blog post / page. For example, putting `[idx-listing mlsnumber="U8000471"]` into the post would show the LIVE primary information for that MLS #. If the price gets changed, photos get added, the property goes off the market, or otherwise anything at all changes, the data will always reflect the changes from the MLS. A blogger could also use the `showall="true"` option (i.e. `[idx-listing mlsnumber="U8000471" showall="true"]`) to show ALL of the data for that area (extended details and features, price changes, schools, and even a map that will show up in Google Reader).
* It comes with a number of **built-in IDX widgets** that allow bloggers to rapidly get started with the utilization of the MLS data.
	* The **IDX Listings widget** allows the blog owner to show listings within an area (city, community, tract, or zip), show their own listings, show their office's listings, or show listings based on a completely customizable search. The widget can be configured to show up to 50 listings at a time and can be set to show the properties in a list, on a map, or in a detailed slideshow.
	* The **IDX Areas widget** allows the blog owner to display a simple list of links to the different areas (cities, communities, tracts, or zips) they service. This makes it super easy for both website visitors and search engines to view all of the listings in that area.
	* The **IDX Search widget** allows the blog owner to show an MLS search form. The results are displayed as HTML on the user's blog.
* The plugin has a great deal of **intelligent URL handling** functionality built-in. It supports and actively enforces canonical URLs and 301 redirects where appropriate to the functionality of the IDX. The URL structure itself is designed to be clean, simple, and readable.
	* A property URL is in the form of `/mls-<MLS_NUMBER>-address`. For example, the url for MLS # L29755 looks like this: `yourblog.com/idx/mls-l29755-2665_riviera_dr_laguna_beach_ca_92651`. If the address changes, a 301 redirect is issued to the new URL.
	* The search results URL is in the form of `/city/<CITY_NAME>`. Similar to the property URLs, 301 redirects are issued where appropriate to ensure that the base URL is always correct.
	* Canonical URLs are set for every IDX page to ensure search engines know the "true" url for the content -- even when the base URL is correct.
* **... and so much more!**

Following are some sites that have already implemented dsSearchAgent: WordPress Edition.

* [Phoenix Real Estate Guy](http://www.phoenixrealestateguy.com/idx/city/phoenix/)
* [Charlottesville Real Estate Blog](http://www.realcentralva.com/idx/city/crozet/)
* Our own super-simple [demo site](http://wp.idx.diversesolutions.com/)

If you'd like more information about the plugin or you would like to obtain an activation key to use this plugin on your blog, please feel free to [contact our sales department](http://www.diversesolutions.com). **This plugin is in beta**, so availability is limited (invite-only) until beta testing is completed and until final pricing is determined.

== Installation ==

[Diverse Solutions]: http://www.diversesolutions.com "Diverse Solutions, plugin author"

1. Go to your WordPress admin area, then go to the "Plugins" area, then go to "Add New".
2. Search for "dssearchagent" (sans quotes) in the plugin search box.
3. Click "Install" on the right, then click "Install" at the top-right in the window that comes up.
4. Go to the "Settings" -> "dsSearchAgent" area.
5. Fill in the activation key provided by [Diverse Solutions].

== Screenshots ==

1. Example use of the `idx-listings` shortcode within a blog post.
2. Example use of the `idx-listing` shortcode within a blog post.
3. The IDX Areas widget and the IDX Search widget embedded into the sidebar.
4. The three different modes of the IDX Listings widget embedded into the sidebar.
5. Partial screenshot of the property details with photo slideshow.
6. Partial screenshot of the property results.

== Frequently Asked Questions ==

= How often will the data on my blog be updated? =

With most MLS's, the data will be pushed to your blog every 2 hours. With other MLS's, data updates could take as long as 24 hours.

= Can I use this without being a member of an MLS? =

No. Due to MLS rules, we can only provide activation keys to MLS members.

= Can I use this without an activation key if I'm a member of an MLS? =

No. The data comes from our (Diverse Solutions') servers, and we require an activation key before any of the data is released.

= How much will an activation key cost? =

We have not yet determined the pricing for this plugin.