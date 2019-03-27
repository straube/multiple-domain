<?php

/**
 * Mutiple Domain WordPress plugin.
 *
 * Core class.
 *
 * @author  Gustavo Straube <https://github.com/straube>
 * @author  Vivek Athalye <https://github.com/vnathalye>
 * @author  Clay Allsopp <https://github.com/clayallsopp>
 * @author  Alexander Nosov <https://github.com/cyberaleks>
 * @author  João Faria <https://github.com/jffaria>
 * @author  Raphael Stäbler <https://github.com/blazer82>
 * @version 0.11.0
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
    const VERSION = '0.11.0';

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
     * different than 80 (default HTTP port) or 443 (default HTTPS port).
     *
     * @var   string
     * @since 0.2
     */
    private $domain = null;

    /**
     * The original domain set in WordPress installation.
     *
     * @var   string
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
     * Indicate whether the default ports should be ingored.
     *
     * This check is used when redirecting from a domain to another, for
     * example.
     *
     * @var    bool
     * @since  0.11.0
     */
    private $ignoreDefaultPorts = false;

    /**
     * Indicate whether canonical link should be added to pages.
     *
     * @var    bool
     * @since  0.11.0
     */
    private $addCanonical = false;

    /**
     * Plugin activation tasks.
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
     * Make this plugin load first to make sure all other plugins use the right
     * domain replacements.
     *
     * @return void
     * @since  0.8.7
     */
    public static function loadFirst()
    {
        $path = str_replace(WP_PLUGIN_DIR . '/', '', MULTPLE_DOMAIN_PLUGIN);
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
     * @return \MultipleDomain
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
     *
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
        $this->domains = array_merge([
            // Defaults to always include the original domain.
            $this->originalDomain => [
                'base' => null,
                'lang' => null,
                'protocol' => 'auto',
            ],
        ], get_option('multiple-domain-domains'));
        if (!is_array($this->domains)) {
            $this->domains = [];
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
     * the actual current domain in `HTTP_HOST` element from `$_SERVER`.
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
     * Indicate whether the default ports should be ingored.
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
     * Notice this function may return `null` when no base path is set in the
     * plugin config.
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
     * Notice this function may return `null` when no language is set in the
     * plugin config.
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
     * The possible returned values are `http`, `https`, or `auto` (default).
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
     * When the current domains has a base URL restriction, redirects the user
     * if the current request URI doesn't match it.
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

        if (empty($base) || empty($uri)) {
            return;
        }

        $base = ltrim($base, '/');
        $uri = ltrim($uri, '/');

        if (strpos($uri, $base) !== 0) {
            wp_redirect(home_url('/' . $base));
            exit;
        }
    }

    /**
     * Replaces the domain in the given URL.
     *
     * The domain in the given URL is replaced by the current domain. If the
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
     * Replaces the domain in upload_dir filter used by `wp_upload_dir()`.
     *
     * The domain in the given `url` and `baseurl` is replaced by the current domain.
     *
     * @param  array $uploads The array of `url`, `baseurl` and other properties.
     * @return array The domain replaced URLs in the given array.
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
     * All occurrences of the original domain will be replaced by the current
     * domain.
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
     * Add all plugin domains to allowed origins.
     *
     * This filter is used to avoid CORS issues.
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
     * @param  array $classes The initial list of body class names.
     * @return array New list of body class names.
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

        $uri = '/' . ltrim(add_query_arg([], $wp->request), '/');
        $currentProtocol = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

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
        /**
         * The WP class instance.
         *
         * @var WP
         */
        global $wp;

        $uri = '/' . ltrim(add_query_arg([], $wp->request), '/');
        $currentProtocol = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

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
        load_plugin_textdomain('multiple-domain', false, dirname(plugin_basename(MULTPLE_DOMAIN_PLUGIN)) . '/languages/');
    }

    /**
     * Register vars to be used as text replacements in Yoast tags.
     *
     * @return void
     * @since  0.11.0
     */
    public function registerYoastVars()
    {
        $domain = $this->domain;
        wpseo_register_var_replacement(
            '%%multiple_domain%%',
            function () use ($domain) { return $domain; },
            'advanced',
            __('The current domain from Multiple Domain', 'multiple-domain')
        );
    }

    /**
     * Get the current domain through parsing request headers.
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
     * @return string|null The HTTP Host header value.
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
     * The domain in the given URL is replaced by the current domain. If the
     * URL contains `/wp-admin/` it'll be ignored when replacing the domain and
     * returned as is.
     *
     * @param  string $domain The domain to replace.
     * @param  string $content The content that will have the domain replaced.
     * @return string The domain replaced content.
     */
    private function replaceDomain($domain, $content)
    {
        if (array_key_exists($domain, $this->domains)) {
            $regex = '/(https?):\/\/' . preg_quote($domain) . '([^a-z0-9\.\-:])/i';
            $protocol = $this->getDomainProtocol($this->domain);
            $replace = ($protocol === 'auto' ? '${1}' : $protocol) . '://' . $this->domain . '${2}';
            $content = preg_replace($regex, $replace, $content);
        }
        return $content;
    }

    /**
     * Parses the given URL to return only its domain.
     *
     * The server port may be included in the returning value.
     *
     * @param string $url The URL to parse.
     * @param bool $ignoreDefaultPorts If `true` is passed to this value, a
     *              default HTTP or HTTPS port will be ignored even if it's
     *              present in the URL.
     * @return string The domain.
     * @since 0.2
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
     * Checks if the given port is a default HTTP (80) or HTTPS (443) port.
     *
     * @param  int $port The port to check.
     * @return bool Indicates if the port is a default one.
     * @since  0.2
     */
    private function isDefaultPort($port)
    {
        $port = (int) $port;
        return $port === 80 || $port === 443;
    }

    /**
     * Prints a `hreflang` link tag.
     *
     * @param  string $url The URL to be set into `href` attribute.
     * @param  string $lang The language code to be set into `hreflang`
     *              attribute. Defaults to `'x-default'`.
     * @return void
     * @since  0.5
     */
    private function outputHrefLangTag($url, $lang = 'x-default')
    {
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
        printf('<link rel="canonical" href="%s" />', $url);
    }
}
