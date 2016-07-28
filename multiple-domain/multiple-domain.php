<?php

/*
Plugin Name: Multiple Domain
Plugin URI:  https://github.com/straube/multiple-domain
Description: This plugin allows you to have multiple domains in a single 
             WordPress installation and enables custom redirects for each 
             domain.
Version:     0.3
Author:      Gustavo Straube (Creative Duo)
Author URI:  http://creativeduo.com.br
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


/**
 * Mutiple Domain WordPress plugin.
 *
 * @author Gustavo Straube <gustavo@creativeduo.com.br>
 * @package multiple-domain
 */
class MultipleDomain
{

    /**
     * The current domain.
     *
     * This property's value also may include the host port when it's 
     * different than 80 (default HTTP port) or 443 (default HTTPS port).
     *
     * @var string
     * @since 0.2
     */
    private $domain = null;

    /**
     * The original domain set in WordPress installation.
     *
     * @var string
     * @since 0.3
     */
    private $originalDomain = null;

    /**
     * The list of available domains.
     *
     * In standard situtations, this array will hold all available domains as 
     * its keys. The optional base URL will be the value for a given domain 
     * (key) when set, otherwise the value will be `NULL`.
     *
     * @var string
     */
    private $domains = [];

    /**
     * Sets the current domain and multiple domain options based on server info 
     * and plugins settings.
     */
    public function __construct()
    {
        $ignoreDefaultPort = true;
        if (!empty($_SERVER['HTTP_HOST'])) {
            $this->domain = $this->getDomainFromUrl($_SERVER['HTTP_HOST'], $ignoreDefaultPort);
        }
        $this->domains = get_option('multiple-domain-domains');
        if (!is_array($this->domains)) {
            $this->domains = [];
        }
        $this->originalDomain = $this->getDomainFromUrl(get_option('home'), $ignoreDefaultPort);
        if (!array_key_exists($this->domain, $this->domains)) {
            $this->domain = $this->originalDomain;
        }
    }

    /**
     * Adds actions and filters required by the plugin.
     *
     * @return void
     */
    public function setup()
    {
        add_action('init', [ $this, 'redirect' ]);
        add_action('admin_init', [ $this, 'settings' ]);
        add_filter('option_home', [ $this, 'filterHome' ]);
    }

    /**
     * Return the current domain.
     *
     * Since this value is checked against plugin settings, it may not reflect 
     * the actual current domain in `HTTP_HOST` element from `$_SERVER`.
     *
     * @return string|null The domain.
     * @since 0.2
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Return original domain set in WordPress installation.
     *
     * @return string The domain.
     * @since 0.3
     */
    public function getOriginalDomain()
    {
        return $this->originalDomain;
    }

    /**
     * When the current domains has a base URL restriction, redirects the user 
     * if the current request URI doesn't match it.
     *
     * @return void
     */
    public function redirect()
    {
        if (!empty($this->domains[$this->domain])) {
            $base = $this->domains[$this->domain];
            if (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $base) !== 0) {
                wp_redirect(home_url($base));
                exit;
            }
        }
    }

    /**
     * Filters home URL.
     *
     * Replace the default home URL domain with the current domain.
     *
     * @param  string $home The default home URL.
     * @return string       The filtered home URL.
     */
    public function filterHome($home)
    {
        if (array_key_exists($this->domain, $this->domains)) {
            $domain = $this->getDomainFromUrl($home);
            $home = str_replace($domain, $this->domain, $home);
        }
        return $home;
    }

    /**
     * Sets up the required settings to show in the admin.
     *
     * @return void
     */
    public function settings()
    {
        add_settings_section('multiple-domain', __('Multiple Domain', 'multiple-domain'), [ $this, 'settingsHeading' ], 'general');
        add_settings_field('multiple-domain-domains', __('Domains', 'multiple-domain'), [ $this, 'settingsField' ], 'general', 'multiple-domain');
        register_setting('general', 'multiple-domain-domains', [ $this, 'sanitizeSettings' ]);
    }

    /**
     * Sanitizes the settings.
     *
     * It takes the value sent by the user in the settings form and parses it 
     * to store in the correct format.
     *
     * @param  string $value The user defined option value.
     * @return array         The sanitized option value.
     */
    public function sanitizeSettings($value)
    {
        $domains = [];
        foreach (explode("\n", $value) as $row) {
            if (empty($row)) {
                continue;
            }
            if (strpos($row, ',') !== false) {
                list($host, $base) = explode(',', $row);
                $domains[$host] = $base;
            } else {
                $domains[$row] = null;
            }
        }
        return $domains;
    }

    /**
     * Renders the settings heading.
     *
     * @return void
     */
    public function settingsHeading()
    {
        echo '<p>' . __('You can use multiple domains in your WordPress defining them below. It\'s possible to limit the access for each domain to a base URL.', 'multiple-domain') . '</p>';
    }

    /**
     * Renders the settings field.
     *
     * @return void
     */
    public function settingsField()
    {
        $value = '';
        foreach ($this->domains as $domain => $base) {
            if (!empty($value)) {
                $value .= "\n";
            }
            $value .= $domain;
            if (!empty($base)) {
                $value .= ',' . $base;
            }
        }
        echo '<textarea id="multiple-domain-domains" name="multiple-domain-domains" class="large-text code" rows="5">' . $value . '</textarea>'
            . '<p class="description">' . __('Add one domain per line, without protocol. It may include the port number when it\'s not the default HTTP (80) or HTTPS (443) port. To define a base URL restriction, add it in the same line as the domain after a comma. All requests to a URL under the domain that don\'t start with the base URL, will be redirected to the base URL. Example: <code>example.com,/base/path</code>', 'multiple-domain') . '</p>';
    }

    /**
     * Parses the given URL to return only its domain.
     *
     * The server port may be included in the returning value.
     *
     * @param string  $url               The URL to parse.
     * @param boolean $ignoreDefaultPort If `true` is passed to this value, a 
     *                                   default HTTP or HTTPS port will be 
     *                                   ignored even if it's present in the 
     *                                   URL.
     * @return string                    The domain.
     * @since 0.2
     */
    private function getDomainFromUrl($url, $ignoreDefaultPort = false)
    {
        $parts = parse_url($url);
        $domain = $parts['host'];
        if (!empty($parts['port']) && !($ignoreDefaultPort && $this->isDefaultPort($parts['port']))) {
            $domain .= ':' . $parts['port'];
        }
        return $domain;
    }

    /**
     * Checks if the given port is a default HTTP (80) or HTTPS (443) port.
     *
     * @param  int $port The port to check.
     * @return boolean   Indicates if the port is a default one.
     * @since 0.2
     */
    private function isDefaultPort($port)
    {
        $port = (int) $port;
        return $port === 80 || $port === 443;
    }
}


// Bootstraping...
$multipleDomain = new MultipleDomain();
$multipleDomain->setup();
$domain = $multipleDomain->getDomain();
$originalDomain = $multipleDomain->getOriginalDomain();


/**
 * The current domain.
 *
 * Since this value is checked against plugin settings, it may not reflect the 
 * actual domain in `HTTP_HOST` element from `$_SERVER`. It also may include 
 * the host port when it's different than 80 (default HTTP port) or 443 
 * (default HTTPS port).
 *
 * @var string
 * @since 0.2
 */
define('MULTPLE_DOMAIN_DOMAIN', $domain);


/**
 * The original domain set in WordPress installation.
 *
 * @var string
 * @since 0.3
 */
define('MULTPLE_DOMAIN_ORIGINAL_DOMAIN', $originalDomain);
