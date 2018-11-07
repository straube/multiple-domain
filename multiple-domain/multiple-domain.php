<?php

/*
Plugin Name: Multiple Domain
Plugin URI:  https://github.com/straube/multiple-domain
Description: This plugin allows you to have multiple domains in a single
             WordPress installation and enables custom redirects for each
             domain.
Version:     0.8.1
Author:      Gustavo Straube (straube.co)
Author URI:  http://straube.co
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


require 'MultipleDomain.php';


/**
 * Register the activation method.
 */
register_activation_hook(__FILE__, [ 'MultipleDomain', 'activate' ]);


/*
 * Bootstrap...
 */
$multipleDomain = new MultipleDomain();
$multipleDomain->setup();

// Changed variable name form `$domain` to `$md_domain` to fix `Overriding WordPress globals is prohibited` error.
$md_domain      = $multipleDomain->getDomain();
$originalDomain = $multipleDomain->getOriginalDomain();
$domainLang     = $multipleDomain->getDomainLang();


/**
 * The current domain.
 *
 * Since this value is checked against plugin settings, it may not reflect the
 * actual domain in `HTTP_HOST` element from `$_SERVER`. It also may include
 * the host port when it's different than 80 (default HTTP port) or 443
 * (default HTTPS port).
 *
 * @var   string
 * @since 0.2
 */
define('MULTPLE_DOMAIN_DOMAIN', $md_domain);


/**
 * The original domain set in WordPress installation.
 *
 * @var   string
 * @since 0.3
 */
define('MULTPLE_DOMAIN_ORIGINAL_DOMAIN', $originalDomain);


/**
 * The current domain language.
 *
 * This value is the language associated with the current domain in the plugin
 * settings. No check is made to verifiy if it reflects the actual user
 * language or locale. Also, notice this constant may be `null` when no
 * language is set in the plugin config.
 *
 * @var   string
 * @since 0.8
 */
define('MULTPLE_DOMAIN_DOMAIN_LANG', $domainLang);
