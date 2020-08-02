<?php

/**
 * Mutiple Domain WordPress plugin.
 *
 * Core features.
 *
 * Contributors:
 *
 *  - Clay Allsopp <https://github.com/clayallsopp>
 *  - Alexander Nosov <https://github.com/cyberaleks>
 *  - João Faria <https://github.com/jffaria>
 *  - Raphael Stäbler <https://github.com/blazer82>
 *  - Tobias Keller <https://github.com/Tobias-Keller>
 *  - Maxime Granier <https://github.com/maxgranier>
 *
 * @author  Gustavo Straube <https://github.com/straube>
 * @version 1.0.6
 * @package multiple-domain
 */
class MultipleDomain
{

    /**
     * The plugin version.
     *
     * @var   string
     * @since 0.3
     */
    const VERSION = '1.0.6';

    /**
     * The number of the default HTTP port.
     *
     * @var integer
     */
    const PORT_HTTP = 80;

    /**
     * The number of the default HTTPS port.
     *
     * @var integer
     */
    const PORT_HTTPS = 443;

    /**
     * The plugin instance.
     *
     * @var   \MultipleDomain
     * @since 0.8.4
     */
    private static $instance;

    /**
     * The current domain.
     *
     * This property's value also may include the host port when it's
     * different than `80` (the default HTTP port) and `443` (the default HTTPS
     * port).
     *
     * @var   string
     * @since 0.2
     */
    private $domain = null;

    /**
     * The original domain set in WordPress installation.
     *
     * This property's value also may include the host port when it's
     * different than `80` (the default HTTP port) and `443` (the default HTTPS
     * port).
     *
     * @var   string
     * @since 0.3
     */
    private $originalDomain = null;

    /**
     * The list of available domains.
     *
     * This array holds all available domains as its keys. Each item in the
     * array is also an array containing the following keys:
     *
     *  - `base`
     *  - `lang`
     *  - `protocol`
     *
     * @var string
     */
    private $domains = [];

    /**
     * Indicate whether the default ports should be ingored.
     *
     * This check is used when redirecting from a domain to another, for
     * example.
     *
     * @var   bool
     * @since 0.11.0
     */
    private $ignoreDefaultPorts = false;

    /**
     * Indicate whether canonical link should be added to pages.
     *
     * @var   bool
     * @since 0.11.0
     */
    private $addCanonical = false;

    /**
     * Plugin activation tasks.
     *
     * The required plugin options are added to WordPress. We also make sure
     * this plugin is the first loaded here.
     *
     * @return void
     * @since  0.7
     */
    public static function activate()
    {
        add_option('multiple-domain-domains', []);
        add_option('multiple-domain-ignore-default-ports', true);
        add_option('multiple-domain-add-canonical', false);

        self::loadFirst();
    }

    /**
     * Update plugin loading order to load this plugin before any other plugin
     * and make sure all plugins use the right domain replacements.
     *
     * @return void
     * @since  0.8.7
     */
    public static function loadFirst()
    {
        /*
         * Relative path to this plugin. The array of active plugins has the
         * plugin path as its keys. We'll use this path to move Multiple Domain
         * to the first position in that array.
         */
        $path = str_replace(WP_PLUGIN_DIR . '/', '', MULTIPLE_DOMAIN_PLUGIN);
        $plugins = get_option('active_plugins');

        if (empty($plugins)) {
            return;
        }

        if (($key = array_search($path, $plugins))) {
            array_splice($plugins, $key, 1);
            array_unshift($plugins, $path);
            update_option('active_plugins', $plugins);
        }
    }

    /**
     * Get the single plugin instance.
     *
     * @return \MultipleDomain The plugin instance.
     * @since  0.8.4
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create a new instance.
     *
     * Adds actions and filters required by the plugin.
     */
    private function __construct()
    {
        $this->initAttributes();
        $this->hookActions();
        $this->hookFilters();
        $this->hookShortcodes();

        new MultipleDomainSettings($this);
    }

    //
    // WordPress API integration
    //

    /**
     * Initialize the class attributes.
     *
     * @return void
     * @since  0.8
     */
    private function initAttributes()
    {
        $this->ignoreDefaultPorts = (bool) get_option('multiple-domain-ignore-default-ports');
        $this->originalDomain = $this->getDomainFromUrl(get_option('home'), $this->ignoreDefaultPorts);

        $this->domain = $this->getDomainFromRequest();

        $domains = (array) get_option('multiple-domain-domains');
        $this->resetDomains();
        foreach ($domains as $domain => $options) {
            $options = wp_parse_args($options, [
                'base' => null,
                'lang' => null,
                'protocol' => null,
            ]);
            $this->addDomain($domain, $options['base'], $options['lang'], $options['protocol']);
        }

        if (!array_key_exists($this->domain, $this->domains)) {
            $this->domain = $this->originalDomain;
        }

        $this->addCanonical = (bool) get_option('multiple-domain-add-canonical');
    }

    /**
     * Hook plugin actions to WordPress.
     *
     * @return void
     * @since  0.8
     */
    private function hookActions()
    {
        add_action('init', [ $this, 'redirect' ]);
        add_action('wp_head', [ $this, 'addHrefLangTags' ]);
        add_action('wp_head', [ $this, 'addCanonicalTag' ]);
        add_action('plugins_loaded', [ $this, 'loaded' ]);
        add_action('activated_plugin', [ self::class, 'loadFirst' ]);
        add_action('wpseo_register_extra_replacements', [ $this, 'registerYoastVars' ]);
    }

    /**
     * Hook plugin filters to WordPress.
     *
     * @return void
     * @since  0.8
     */
    private function hookFilters()
    {

        // Generic domain replacement
        add_filter('content_url', [ $this, 'fixUrl' ]);
        add_filter('option_siteurl', [ $this, 'fixUrl' ]);
        add_filter('option_home', [ $this, 'fixUrl' ]);
        add_filter('plugins_url', [ $this, 'fixUrl' ]);
        add_filter('wp_get_attachment_url', [ $this, 'fixUrl' ]);
        add_filter('get_the_guid', [ $this, 'fixUrl' ]);

        // Specific domain replacement filters
        add_filter('upload_dir', [ $this, 'fixUploadDir' ]);
        add_filter('the_content', [ $this, 'fixContentUrls' ], 20);
        add_filter('allowed_http_origins', [ $this, 'addAllowedOrigins' ]);

        // Add body class based on domain
        add_filter('body_class', [ $this, 'addDomainBodyClass' ]);

        // Stop WP built in Canonical URL if this plugin has 'Add canonical links' enabled
        add_filter('get_canonical_url', [ $this, 'getCanonicalUrl' ]);
    }

    /**
     * Hook plugin shortcodes to WordPress.
     *
     * @return void
     * @since  0.8.5
     */
    private function hookShortcodes()
    {
        add_shortcode('multiple_domain', [ $this, 'shortcode' ]);
    }

    //
    //
    //

    /**
     * Return the current domain.
     *
     * Since this value is checked against plugin settings, it may not reflect
     * the actual current domain in `HTTP_HOST` key from global `$_SERVER` var.
     *
     * Depending on the plugin settings, the domain also may include the host
     * port when it's different than `80` (the default HTTP port) and `443` (the
     * default HTTPS port).
     *
     * @return string|null The domain.
     * @since  0.2
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Return original domain set in WordPress installation.
     *
     * Notice this method may return an unexpected value when running the site
     * using the `server` command from wp-cli. That's because wp-cli changes the
     * value of `site_url` and `home_url` options through a filter.
     * Unfortunately, it's not possible to change this behaviour.
     *
     * The domain also may include the host port when it's different than `80`
     * (the default HTTP port) and `443` (the default HTTPS port).
     *
     * @return string The domain.
     * @since  0.3
     */
    public function getOriginalDomain()
    {
        return $this->originalDomain;
    }

    /**
     * Return all domains available.
     *
     * The keys in the returned array are the domain name. Each item in the
     * array is also an array containing the following keys:
     *
     *  - `base`
     *  - `lang`
     *  - `protocol`
     *
     * @return array The list of domains.
     * @since  0.11.0
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * Indicate whether the default ports (`80` or `443`) should be ingored.
     *
     * This check is used when redirecting from a domain to another, for
     * example.
     *
     * @return bool A boolean indicating if the default port should be ignored.
     * @since  0.8.2
     */
    public function shouldIgnoreDefaultPorts()
    {
        return $this->ignoreDefaultPorts;
    }

    /**
     * Indicate whether the canonical tags should be added to page.
     *
     * @return bool A boolean indicating if the default port should be ignored.
     * @since  0.11.0
     */
    public function shouldAddCanonical()
    {
        return $this->addCanonical;
    }

    /**
     * Get the base path associated to the given domain.
     *
     * If no domain is passed to the function, it'll return the base path for
     * the current domain.
     *
     * Notice this function may return `null` when no base path is set for a
     * given domain in the plugin config.
     *
     * @param  string|null $domain The domain.
     * @return string|null The base path.
     * @since  0.10.3
     */
    public function getDomainBase($domain = null)
    {
        return $this->getDomainAttribute('base', $domain);
    }

    /**
     * Get the language associated to the given domain.
     *
     * If no domain is passed to the function, it'll return the language for
     * the current domain.
     *
     * Notice this function may return `null` when no language is set for a
     * given domain in the plugin config.
     *
     * @param  string|null $domain The domain.
     * @return string|null The language code.
     * @since  0.8
     */
    public function getDomainLang($domain = null)
    {
        return $this->getDomainAttribute('lang', $domain);
    }

    /**
     * Get the protocol option for the given domain.
     *
     * If no domain is passed to the function, it'll return the option for the
     * current domain.
     *
     * The possible returned values are `http`, `https`, or `auto` (default). If
     * no protocol is defined for a given domain, the default value will be
     * returned.
     *
     * @param  string|null $domain The domain.
     * @return string The protocol option.
     * @since  0.10.0
     */
    public function getDomainProtocol($domain = null)
    {
        $protocol = $this->getDomainAttribute('protocol', $domain);
        return in_array($protocol, [ 'http', 'https' ]) ? $protocol : 'auto';
    }

    /**
     * Reset the list of domains.
     *
     * In case the `$keepOriginal` param is `true`, which is the default, the
     * list of domains will have only the original domain where WordPress was
     * installed.
     *
     * @param  bool $keepOriginal Indicates whether the original domain should
     *         be kept.
     * @return void
     * @since  1.0.0
     */
    public function resetDomains($keepOriginal = true)
    {
        if (!$keepOriginal || empty($this->originalDomain)) {
            $this->domains = [];
            return;
        }

        $this->domains = [
            $this->originalDomain => [
                'base' => null,
                'lang' => null,
                'protocol' => 'auto',
            ],
        ];
    }

    /**
     * Add a new domain to the list of domains.
     *
     * Besides the `$domain` param, all other are optional.
     *
     * @param  string $domain The domain.
     * @param  string $base The base path.
     * @param  string $lang The language.
     * @param  string $protocol The protocol option. It can be `http`, `https`
     *         or `auto`.
     * @return void
     * @since  1.0.0
     */
    public function addDomain($domain, $base = null, $lang = null, $protocol = 'auto')
    {
        $this->domains[$domain] = [
            'base' => $base,
            'lang' => $lang,
            'protocol' => $protocol,
        ];
    }

    /**
     * Store the current list of domains in the WordPress options.
     *
     * This is can be used to persist changes made to the list of domains with
     * `resetDomains` and `addDomain` methods.
     *
     * @return void
     * @since  1.0.0
     */
    public function storeDomains()
    {
        update_option('multiple-domain-domains', $this->domains);
    }

    /**
     * When the current domain has a base URL restriction and the current
     * request URI doesn't match it, redirects the user.
     *
     * @return void
     */
    public function redirect()
    {
        /*
         * Allow developers to create their own logic for redirection.
         */
        do_action('multiple_domain_redirect', $this->domain);

        $base = $this->getDomainBase();
        $uri = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;

        $base = ltrim($base, '/');
        $uri = ltrim($uri, '/');

        if (empty($base) || preg_match('/^wp-[a-z]+(\.php|\/|$)/i', $uri)) {
            return;
        }

        if (strpos($uri, $base) !== 0) {
            wp_redirect(home_url('/' . $base));
            exit;
        }
    }

    /**
     * Replaces the domain in the given URL.
     *
     * The domain in the given URL is replaced with the current domain. If the
     * URL contains `/wp-admin/` it'll be ignored when replacing the domain and
     * returned as is.
     *
     * @param  string $url The URL to fix.
     * @return string The domain replaced URL.
     * @since  0.10.0
     */
    public function fixUrl($url)
    {
        if (!preg_match('/\/wp-admin\/?/', $url)) {
            $domain = $this->getDomainFromUrl($url);
            $url = $this->replaceDomain($domain, $url);
        }
        return $url;
    }

    /**
     * Replaces the domain in `upload_dir` filter used by `wp_upload_dir()`.
     *
     * The domain in the given `url` and `baseurl` is replaced by the current
     * domain.
     *
     * @param  array $uploads The array of `url`, `baseurl` and other
     *         properties.
     * @return array The domain-replaced values.
     * @since  0.4
     */
    public function fixUploadDir($uploads)
    {
        $uploads['url'] = $this->fixUrl($uploads['url']);
        $uploads['baseurl'] = $this->fixUrl($uploads['baseurl']);
        return $uploads;
    }

    /**
     * Replaces the domain in post content.
     *
     * All occurrences of any of the  available domains (i.e. all domains set in
     * the plugin config) will be replaced with the current domain.
     *
     * @param  string $content The content to fix.
     * @return string The domain replaced content.
     * @since  0.8
     */
    public function fixContentUrls($content)
    {
        foreach (array_keys($this->domains) as $domain) {
            $content = $this->replaceDomain($domain, $content);
        }
        return $content;
    }

    /**
     * Add all available domains to allowed origins.
     *
     * This filter is used to prevent CORS issues.
     *
     * @param  array $origins The default list of allowed origins.
     * @return array The updated list of allowed origins.
     * @since  0.8
     */
    public function addAllowedOrigins($origins)
    {
        foreach (array_keys($this->domains) as $domain) {
            $origins[] = 'https://' . $domain;
            $origins[] = 'http://' . $domain;
        }
        return array_values(array_unique($origins));
    }

    /**
     * Add the current domain to the body class in a sanitized version.
     *
     * If the current domain is `example.com`, the class added to the page body
     * will be `multiple-domain-example-com`. Notice this filter only has effect
     * when the `body_class()` function is added to the page's `<body> tag`.
     *
     * @param  array $classes The initial list of body class names.
     * @return array Updated list of body class names.
     * @since  0.9.0
     */
    public function addDomainBodyClass($classes)
    {
        $classes[] = 'multiple-domain-' . preg_replace('/[^a-z0-9]+/i', '-', $this->domain);
        return $classes;
    }

    /**
     * Add `hreflang` links to head for SEO purpose.
     *
     * @return void
     * @author Alexander Nosov <https://github.com/cyberaleks>
     * @since  0.4
     */
    public function addHrefLangTags()
    {
        /**
         * The WP class instance.
         *
         * @var WP
         */
        global $wp;

        $uri = trailingslashit('/' . ltrim(add_query_arg([], $wp->request), '/'));
        $currentProtocol = $this->getCurrentProtocol();

        foreach (array_keys($this->domains) as $domain) {
            $protocol = $this->getDomainProtocol($domain);
            if ($protocol === 'auto') {
                $protocol = $currentProtocol;
            }
            $protocol .= '://';

            $lang = $this->getDomainLang($domain);

            if (!empty($lang)) {
                $this->outputHrefLangTag($protocol . $domain . $uri, $lang);
            }

            if ($domain === $this->originalDomain) {
                $this->outputHrefLangTag($protocol . $domain . $uri);
            }
        }
    }

    /**
     * Add `canonical` links to head for SEO purpose.
     *
     * @return void
     * @since  0.11.0
     */
    public function addCanonicalTag()
    {
        if (!$this->shouldAddCanonical()) {
            return;
        }

        /**
         * The WP class instance.
         *
         * @var WP
         */
        global $wp;

        $uri = home_url(add_query_arg([], $wp->request), 'relative') . '/';
        $currentProtocol = $this->getCurrentProtocol();

        $protocol = $this->getDomainProtocol($this->originalDomain);
        if ($protocol === 'auto') {
            $protocol = $currentProtocol;
        }
        $protocol .= '://';

        $this->outputCanonicalTag($protocol . $this->originalDomain . $uri);
    }

    /**
     * This shortcode simply returns the current domain.
     *
     * @return string The current domain.
     * @since  0.8.5
     */
    public function shortcode()
    {
        return $this->domain;
    }

    /**
     * Load text domain when plugin is loaded.
     *
     * @return void
     * @since  0.8.6
     */
    public function loaded()
    {
        $path = dirname(plugin_basename(MULTIPLE_DOMAIN_PLUGIN)) . '/languages/';
        load_plugin_textdomain('multiple-domain', false, $path);
    }

    /**
     * Register vars to be used as text replacements in Yoast tags.
     *
     * @return void
     * @since  0.11.0
     */
    public function registerYoastVars()
    {
        wpseo_register_var_replacement(
            '%%multiple_domain%%',
            [ $this, 'getDomain' ],
            'advanced',
            __('The current domain from Multiple Domain', 'multiple-domain')
        );
    }

    /**
     * Get the current domain via request headers parsing.
     *
     * @return string|null The current domain.
     * @since  0.8.7
     */
    private function getDomainFromRequest()
    {
        $domain = $this->getHostHeader();

        if (empty($domain)) {
            return null;
        }

        $matches = [];
        if (preg_match('/^(.*):(\d+)$/', $domain, $matches) && $this->isDefaultPort($matches[2])) {
            $domain = $matches[1];
        }
        return $domain;
    }

    /**
     * Get the `Host` HTTP header value.
     *
     * To make it compatible with proxies, this function first tries to get the
     * value from `X-Host` header and, then, falls back to the regular `Host`
     * header.
     *
     * It returns `null` in case both headers are empty.
     *
     * @return string|null The HTTP `Host` header value.
     * @since  0.8.7
     */
    private function getHostHeader()
    {
        if (!empty($_SERVER['HTTP_X_HOST'])) {
            return $_SERVER['HTTP_X_HOST'];
        }

        if (!empty($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }

        return null;
    }

    /**
     * Get the current URL protocol based on server settings.
     *
     * The possible returned values are `http` and `https`.
     *
     * @return string The protocol.
     */
    private function getCurrentProtocol()
    {
        return empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';
    }

    /**
     * Get an attribute by its name for the given domain.
     *
     * If no domain is passed to the function, it'll return the attribute value
     * for the current domain.
     *
     * Notice this function may return `null` when the attribute is not set in
     * the plugin config or doesn't exist.
     *
     * @param  string $name The attribute name.
     * @param  string|null $domain The domain.
     * @return string The attribute value.
     * @since  0.10.0
     */
    private function getDomainAttribute($name, $domain = null)
    {
        if (empty($domain)) {
            $domain = $this->domain;
        }
        $attribute = null;
        if (!empty($this->domains[$domain][$name])) {
            $attribute = $this->domains[$domain][$name];
        }
        return $attribute;
    }

    /**
     * Replaces the domain.
     *
     * All occurrences of the given domain will be replaced with the current
     * domain in the content.
     *
     * The protocol may also be replaced following the protocol settings defined
     * in the plugin config for the current domain.
     *
     * @param  string $domain The domain to replace.
     * @param  string $content The content that will have the domain replaced.
     * @return string The domain-replaced content.
     */
    private function replaceDomain($domain, $content)
    {
        if (MULTIPLE_DOMAIN_LOW_MEMORY) {
            return $this->replaceDomainUsingLessMemory($domain, $content);
        }
        if (array_key_exists($domain, $this->domains)) {
            $regex = '/(https?):\/\/' . preg_quote($domain, '/') . '(?![a-z0-9.\-:])/i';
            $protocol = $this->getDomainProtocol($this->domain);
            $replace = ($protocol === 'auto' ? '${1}' : $protocol) . '://' . $this->domain;
            $content = preg_replace($regex, $replace, $content);
        }
        return $content;
    }

    /**
     * Replaces the domain using less memory.
     *
     * This function does the same as `replaceDoamin`, however it uses
     * `mb_eregi_replace` instead of `preg_replace` for less memory consumption.
     * On the other hand, it takes more time to execute.
     *
     * @param  string $domain The domain to replace.
     * @param  string $content The content that will have the domain replaced.
     * @return string The domain-replaced content.
     * @since  1.0.2
     */
    private function replaceDomainUsingLessMemory($domain, $content)
    {
        if (array_key_exists($domain, $this->domains)) {
            $regex = '(https?):\/\/' . preg_quote($domain, '/') . '(?![a-z0-9.\-:])';
            $protocol = $this->getDomainProtocol($this->domain);
            $replace = ($protocol === 'auto' ? '\\1' : $protocol) . '://' . $this->domain;
            $content = mb_eregi_replace($regex, $replace, $content);
        }
        return $content;
    }

    /**
     * Parses the given URL to return only its domain.
     *
     * The server port may be included in the returning value depending on its
     * number and plugin settings.
     *
     * @param  string $url The URL to parse.
     * @param  bool $ignoreDefaultPorts If `true` is passed to this value, a
     *         default HTTP or HTTPS port will be ignored even if it's present
     *         in the URL.
     * @return string The domain.
     * @since  0.2
     */
    private function getDomainFromUrl($url, $ignoreDefaultPorts = false)
    {
        $parts = parse_url($url);
        $domain = $parts['host'];
        if (!empty($parts['port']) && !($ignoreDefaultPorts && $this->isDefaultPort($parts['port']))) {
            $domain .= ':' . $parts['port'];
        }
        return $domain;
    }

    /**
     * Checks if the given port is a default HTTP (`80`) or HTTPS (`443`) port.
     *
     * @param  int $port The port to check.
     * @return bool Indicates if the port is a default one.
     * @since  0.2
     */
    private function isDefaultPort($port)
    {
        $port = (int) $port;
        return $port === self::PORT_HTTP || $port === self::PORT_HTTPS;
    }

    /**
     * Prints a `hreflang` link tag.
     *
     * @param  string $url The URL to be set into `href` attribute.
     * @param  string $lang The language code to be set into `hreflang`
     *         attribute. Defaults to `x-default`.
     * @return void
     * @since  0.5
     */
    private function outputHrefLangTag($url, $lang = 'x-default')
    {
        $url = htmlentities($url);
        $lang = str_replace('_', '-', $lang);
        printf('<link rel="alternate" href="%s" hreflang="%s" />', $url, $lang);
    }

    /**
     * Prints a `canonical` link tag.
     *
     * @param  string $url The canonical URL to be set into `href` attribute.
     * @return void
     * @since  0.11.0
     */
    private function outputCanonicalTag($url)
    {
        $url = htmlentities($url);
        printf('<link rel="canonical" href="%s" />', $url);
    }

    /**
     * Filter override WordPress built-in canonical tag generation if using the this plugin's canonical tag feature
     *
     * @param $url
     * @return string
     */
    public function getCanonicalUrl($url)
    {
        // If *not* using the plugin's canonical tags, then return this URL. Otherwise, don't
        if (!$this->shouldAddCanonical()) {
            return $url;
        }
        return '';
    }
}
