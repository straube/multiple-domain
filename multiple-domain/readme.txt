=== Multiple Domain ===
Contributors: GustavoStraube, cyberaleks, jffaria
Tags: multiple, domains, redirect
Requires at least: 4.0
Tested up to: 5.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to have multiple domains in a single Wordpress installation and enables custom redirects for each
domain.

== Description ==

Multiple Domain allows you having more than one domain in a single WordPress installation. This plugin doesn't support
more than one theme or advanced customizations for each domain. It's only intended to enable constant navigation under
many domains. For a more complex setup, there is
[WordPress Multisite (MU)](https://codex.wordpress.org/Create_A_Network).

When there is more than one domain set in your host, all links and resources will point to the default domain. This is
the default WordPress behavior. With Multiple Domain installed and properly configured, it'll update all link on the
fly. This way, the user navigation will be end-to-end under the same domain.

You can also set an optional base URL. If you want only a set of URL's available under a given domain, you can use this
restriction.

Additionally, a language can be set for each domain. The language will be used to add `<link>` tags with `hreflang`
attribute to document head. This is for SEO purposes.

== Installation ==

Follow the steps below to install the plugin:

1. Upload the plugin files to the `/wp-content/plugins/multiple-domain` directory, or install the plugin through the
    WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings -> General screen to configure your additional domains.

== Frequently Asked Questions ==

= How can I help the plugin development? =

Feel free to open a [pull request](https://github.com/straube/multiple-domain/pulls) to address any of the
[issues](https://github.com/straube/multiple-domain/issues) reported by the plugin users. In case you have questions
on how to fix or the best approach, start a discussion on the appropriate thread.

If you want to add a new feature, please [open an issue](https://github.com/straube/multiple-domain/issues/new)
explaining the feature and how it would help the users before start writing your code.

**Donations**

If you find this plugin helpful, you can support the work involved buying me a coffee, beer or a Playstation 4 game.
You can send donations over PayPal to gustavo.straube@gmail.com.

= Does this plugin set extra domains within my host? =

No. You have to set additional domains, DNS, and everything else to use this plugin.

= Can I have a different theme/content/plugins for each domain? =

Nope. If you want a complex set up like this, you may be interested in WordPress Multisite. It's delivered with every
WordPress installation since 3.0, you can find more info here: https://codex.wordpress.org/Create_A_Network.

= There is a way to add domain based logic to my themes? =

Absolutely. You can use the `MULTIPLE_DOMAIN_DOMAIN` and `MULTIPLE_DOMAIN_ORIGINAL_DOMAIN` constants to get the current
and original domains. Just notice that since the value of the first one is checked against plugin settings, it may not
reflect the actual domain in `HTTP_HOST` element from `$_SERVER` or user's browser. They also may include the host port
when it's different than 80 (default HTTP port) or 443 (default HTTPS port).

**Notice**: in prior versions these constants were wrongly prefixed with `MULTPLE_`, missing the "I". The old constants
are now deprecated. They still available for backcompat but will be removed in future releases.

= Can I create a custom access restriction logic for each domain? =

Yes. You can use the `multiple_domain_redirect` action to do that. Please check
https://github.com/straube/multiple-domain/issues/2 for an example on how to do that.

= Can I get the language associated with the current domain? =

Yes. You can use the `MULTIPLE_DOMAIN_DOMAIN_LANG` constant to get the language associated with the current domain. Keep
in mind the value in this constant doesn't necessarily reflect the actual user language or locale. This is just the
language set in the plugin config. Also notice the language may be `null`.

**Notice**: in prior versions these constants were wrongly prefixed with `MULTPLE_`, missing the "I". The old constants
are now deprecated. They still available for backcompat but will be removed in future releases.

= Can I show the current domain in the content of posts or pages? =

Yes. There is a shortcode available for that. Just add `[multiple_domain]` to the post/page and it'll be replaced by
the current domain when viewing the content. You can write things like "Welcome to [multiple_domain]!", which would be
rendered as "Welcome to mydomain.com!".

= What domains should I add to the plugin setup? =

Any domain you're site is served from must be added to the plugin configuration. Even `www` variations and the original
domain where your WordPress was installed in must be added. You'll probably see some unexpected output when accessing
the site from a non-mapped domain.

= Can I disable `hreflang` tags output even for the original domain? =

Yes. You may notice that even if you don't set a language for any domain, you still get a default `hreflang` tag in
your page head. To disable this behavior, follow the instructions from
https://github.com/straube/multiple-domain/issues/51.

= I locked myself out, and what am I doing now? =

Under certain circumstances, in the case of a wrong configuration, you may not be able to log in to the admin area
and your page will be redirected. In this case, there are two ways to solve this.

1. Delete the plugin directory `wp-content/plugins/multiple-domain`. You should be able to do that from the hosting
    panel, from an FTP client, or via SSH. The downside of this technique is that it won’t be possible to install the
    plugin again since the configuration will still be in the database.
2. Remove the plugin configuration from the database using the following SQL query `DELETE FROM {YOUR-PREFIX}_options
    WHERE option_name LIKE 'multiple-domain-%'`; (Remember to replace the prefix from your own table name). This can be
    done from the hosting panel when PHPMyAdmin is available or using a MySQL client.

== Screenshots ==

== Changelog ==

= 1.0.6 =

* Fix URI generated for canonical tag.

= 1.0.5 =

* Fixed issue with system routes when a base path is defined.

= 1.0.4 =

* Fixed assertions in admin views.

= 1.0.3 =

* Fixed XSS vulnerability in canonical/alternate tags.

= 1.0.2 =

* Added low memory option. (Refer to https://github.com/straube/multiple-domain/issues/45 on how to enable it)
* Constants starting with `MULTPLE_` are now deprecated. They have a matching `MULTIPLE_` prefixed constant.
* Fixed constants starting with `MULTPLE_`, changed to `MULTIPLE`.

= 1.0.1 =

* Fixed issue with regex used in domain replacement.

= 1.0.0 =

* Locked out instructions to readme file.
* API to programmatically change the domains list.
* Don't add canonical link if settings are `false`.

= 0.11.2 =

* FAQ about removal of `hreflang` tags.
* Fixed bug in domain replacement when it contains a slash (the regex delimiter).
* Fixed issue in the domain replacement regex.

= 0.11.1 =

* Fixed URI validation when there is a domain's base restriction.

= 0.11.0 =

* Add CHANGELOG.md file.
* Added option to enable canonical tags.
* Added `%%multiple_domain%%` advanced variable for Yoast.
* Moved WordPress admin features to a separate class.
* Renamed hreflang related methods.
* Inline documentation review.
* Minor refactoring.
* Fixed issue with domain replacement.

= 0.10.3 =

* Added public method to retrieve a given (or current) domain base path: `getDomainBase($domain = null)`.
* Minor code refactoring.

= 0.10.2 =

* Fix minor notice message when loading the non-mapped original domain.
* Added FAQ about plugin settings and domains.

= 0.10.1 =

* Fix bug introduced in 0.10.0 with setups where the original domain is not present in the plugin settings.

= 0.10.0 =

* Fix #31: Don't add SSL when accessing via a Tor domain name
* Moved HTML to view files.

= 0.9.0 =

* Fixed bug in backward compatibility logic.
* Added a class to `<body>` tag containing the domain name (e.g. `multipled-domain-name-tld`) to allow front-end customizations.

= 0.8.7 =

* Loading Multiple Domain before other plugins to fix issue with paths.
* Fix #38: Missing locales on language list (this issue was reopened and now it's fixed)
* Refactored `initAttributes` method.

= 0.8.6 =

* Fix #39: Rolling back changes introduced in 0.8.4 and 0.8.5 regarding to avoid URL changes in the WP admin.

= 0.8.5 =

* Fixed an issue introduced in 0.8.4 that breaks the admin URLs.
* Fix #38: Missing locales on language list
* Add `[multiple_domain]` shortcode to show the current language.

= 0.8.4 =

* Fix: #36 Wrong host in URLs returned by the JSON API
* Using singleton pattern for main plugin class.
* Avoiding URL changes in the admin panel.

= 0.8.3 =

* Fix: #34 hreflang tag error

= 0.8.2 =

* Fix: #32 Image URLs not being re-written properly via Tor.

= 0.8.1 =

* Fix: #23 Undefined index when using wp-cli.

= 0.8.0 =

* Moved `MultipleDomain` class to its own file.
* Fix: #14 Remove `filter_input` from plugin.
* Attempt to fix #22.
* Added `MULTIPLE_DOMAIN_DOMAIN_LANG` constant for theme/plugin customization.
* Fix: #21 No 'Access-Control-Allow-Origin' header is present on the requested resource

= 0.7.1 =
* Make the plugin compatible with PHP 5.4 again.

= 0.7 =
* Code review/refactoring.
* Added activation hook to fix empty settings bug.

= 0.6 =
* Fix: #11 Redirect to original domain if SSL/https.

= 0.5 =
* Added http/https for alternate link.

= 0.4 =
* Fixed resolving host name to boolean.
* Added Reflang links to head for SEO purpose. E.g.
    `<link rel="alternate" hreflang="x-default" href="https://example.com/">`
    `<link rel="alternate" hreflang="de-DE" href="https://de.example.com/">`

= 0.3 =
* Fixed bug when removing the port from current domain.
* Added `MULTIPLE_DOMAIN_ORIGINAL_DOMAIN` constant to hold the original WP home domain.
* Allowing developers to create custom URL restriction logic through `multiple_domain_redirect` action.
* Improved settings interface.

= 0.2 =
* Improved port verification.
* Added `MULTIPLE_DOMAIN_DOMAIN` constant for theme/plugin customization.
* And, last but not least, code refactoring.

= 0.1 =
This is the first release. It supports setting domains and an optional base URL for each one.

== Upgrade Notice ==
