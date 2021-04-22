# Multiple Domain

## Support SLA and Next releases

**MULTIPLE DOMAIN IS NOW BEING MAINTAINED BY [goINPUT](https://goinput.de/). THE SOURCE CODE NOW LIVES IN WORDPRESS' SUBVERSION. MORE DETAILS CAN BE FOUND ON THE [OFFICIAL PLUGIN PAGE](https://wordpress.org/plugins/multiple-domain/).**

## About

[![Build Status](https://travis-ci.com/straube/multiple-domain.svg?branch=master)](https://travis-ci.com/straube/multiple-domain)

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

Check the plugin page at WordPress.org: https://wordpress.org/plugins/multiple-domain/

## Installation

Follow the steps below to install the plugin:

1. Upload the plugin files to the `/wp-content/plugins/multiple-domain` directory, or install the plugin through the
    WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings -> General screen to configure your additional domains.

## Frequently Asked Questions

**Does this plugin set extra domains within my host?**

No. You have to set additional domains, DNS, and everything else to use this plugin.

**Can I have a different theme/content/plugins for each domain?**

Nope. If you want a complex set up like this, you may be interested in WordPress Multisite. It's delivered with every
WordPress installation since 3.0, you can find more info here: https://codex.wordpress.org/Create_A_Network.

**There is a way to add domain based logic to my themes?**

Absolutely. You can use the `MULTIPLE_DOMAIN_DOMAIN` and `MULTIPLE_DOMAIN_ORIGINAL_DOMAIN` constants to get the current
and original domains. Just notice that since the value of the first one is checked against plugin settings, it may not
reflect the actual domain in `HTTP_HOST` element from `$_SERVER` or user's browser. They also may include the host port
when it's different than 80 (default HTTP port) or 443 (default HTTPS port).

**Notice**: in prior versions these constants were wrongly prefixed with `MULTPLE_`, missing the "I". The old constants
are now deprecated. They still available for backcompat but will be removed in future releases.

**Can I create a custom access restriction logic for each domain?**

Yes. You can use the `multiple_domain_redirect` action and `multiple_domain_redirect_disable` filter do that. Please
check https://github.com/straube/multiple-domain/issues/2 for an example on how to use both.

**Can I get the language associated with the current domain?**

Yes. You can use the `MULTIPLE_DOMAIN_DOMAIN_LANG` constant to get the language associated with the current domain. Keep
in mind the value in this constant doesn't necessarily reflect the actual user language or locale. This is just the
language set in the plugin config. Also notice the language may be `null`.

**Notice**: in prior versions these constants were wrongly prefixed with `MULTPLE_`, missing the "I". The old constants
are now deprecated. They still available for backcompat but will be removed in future releases.

**Can I show the current domain in the content of posts or pages?**

Yes. There is a shortcode available for that. Just add `[multiple_domain]` to the post/page and it'll be replaced by
the current domain when viewing the content. You can write things like "Welcome to [multiple_domain]!", which would be
rendered as "Welcome to mydomain.com!".

**What domains should I add to the plugin setup?**

Any domain you're site is served from must be added to the plugin configuration. Even `www` variations and the original
domain where your WordPress was installed in must be added. You'll probably see some unexpected output when accessing
the site from a non-mapped domain.

**Can I disable `hreflang` tags output even for the original domain?**

Yes. You may notice that even if you don't set a language for any domain, you still get a default `hreflang` tag in
your page head. To disable this behavior, follow the instructions from
https://github.com/straube/multiple-domain/issues/51.

**I locked myself out, and what am I doing now?**

Under certain circumstances, in the case of a wrong configuration, you may not be able to log in to the admin area
and your page will be redirected. In this case, there are two ways to solve this.

1. Delete the plugin directory `wp-content/plugins/multiple-domain`. You should be able to do that from the hosting
    panel, from an FTP client, or via SSH. The downside of this technique is that it won’t be possible to install the
    plugin again since the configuration will still be in the database.
2. Remove the plugin configuration from the database using the following SQL query `DELETE FROM {YOUR-PREFIX}_options
    WHERE option_name LIKE 'multiple-domain-%'`; (Remember to replace the prefix from your own table name). This can be
    done from the hosting panel when PHPMyAdmin is available or using a MySQL client.

## Changelog

Refer to [CHANGELOG.md](CHANGELOG.md) for release notes and changes.

## Meta

Contributors: GustavoStraube, cyberaleks, jffaria  
Tags: multiple, domains, redirect  
Requires at least: 4.0  
Tested up to: 5.2.4  
Stable tag: trunk  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

## Contributing

Want to help? Please check our [contribution guide](CONTRIBUTING.md).
