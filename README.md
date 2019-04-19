# Multiple Domain

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

Absolutely. You can use the `MULTPLE_DOMAIN_DOMAIN` and `MULTPLE_DOMAIN_ORIGINAL_DOMAIN` constants to get the current
and original domains. Just notice that since the value of the first one is checked against plugin settings, it may not
reflect the actual domain in `HTTP_HOST` element from `$_SERVER` or user's browser. They also may include the host port
when it's different than 80 (default HTTP port) or 443 (default HTTPS port).

**Can I create a custom access restriction logic for each domain?**

Yes. You can use the `multiple_domain_redirect` action to do that. Please check
https://github.com/straube/multiple-domain/issues/2 for an example on how to do that.

**Can I get the language associated with the current domain?**

Yes. You can use the `MULTPLE_DOMAIN_DOMAIN_LANG` constant to get the language associated with the current domain. Keep
in mind the value in this constant doesn't necessarily reflect the actual user language or locale. This is just the
language set in the plugin config. Also notice the language may be `null`.

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

## Changelog

Refer to [CHANGELOG.md](CHANGELOG.md) for release notes and changes.

## Meta

Contributors: GustavoStraube, cyberaleks, jffaria  
Tags: multiple, domains, redirect  
Requires at least: 4.0  
Tested up to: 5.1.1  
Stable tag: trunk  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

## Contributing

Feel free to open a pull request to address any of the issues reported by the plugin users. In case you have questions
on how to fix or the best approach, start a discussion on the appropriate thread.

If you want to add a new feature, please open an issue explaining the feature and how it would help the users before
start writing your code.

Separate each new feature or improvement into a separate branch in your forked repository.

### Guidelines

To make sure every contribution follows the same code style, please follow these rules:

* Write PSR-2 compliant code
* Use UNIX line returns
* Remove trailing white space
* Use 4 spaces instead of tabs

Also notice that even if Wordpress has its own code styling guidelines, this plugin doesn't follow it in favor of a
global standard (PSR-2).

### Testing

Before running the tests, you may have to prepare the environment. First, install the requirements:

```
$ composer install
```

In case you don't have Composer installed, follow the [instructions](https://getcomposer.org/doc/00-intro.md) to
install it.

Then, install the WordPress test lib and the testing database:

```
$ bash bin/install-wp-tests.sh multiple_domain_test root '' localhost latest
```

To run the command above you need PHP, MySQL and SVN installed in your local env. It'll create a MySQL database named
`multiple_domain_test`.

If you have any trouble or need more details on what are the options, please refer to the official docs on how to
[initialize the testing environment locally](https://make.wordpress.org/cli/handbook/plugin-unit-tests/#running-tests-locally).

Finally, to run the tests, call the PHPUnit program:

```
$ vendor/bin/phpunit
```

### Donations

If you find this plugin helpful, you can support the work involved buying me a coffee, beer or a Playstation 4 game.
You can send donations over PayPal to gustavo.straube@gmail.com.
