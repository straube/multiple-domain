<?php

/**
 * Mutiple Domain WordPress plugin.
 *
 * @author  Gustavo Straube <https://github.com/straube>
 * @author  Vivek Athalye <https://github.com/vnathalye>
 * @author  Clay Allsopp <https://github.com/clayallsopp>
 * @author  Alexander Nosov <https://github.com/cyberaleks>
 * @author  João Faria <https://github.com/jffaria>
 * @version 0.8.5
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
    const VERSION = '0.8.5';

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
     * Plugin activation tasks.
     *
     * @return void
     * @since  0.7
     */
    public static function activate()
    {
        add_option('multiple-domain-domains', []);
        add_option('multiple-domain-ignore-default-ports', true);
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
    }

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
     * Get the language associated to the given domain.
     *
     * If no domain is passed to the function, it'll return the language for
     * the current domain.
     *
     * Notice this function may return `null` when no language is set in the
     * plugin config.
     *
     * @param  string|null $domain
     * @return string|null
     * @since  0.8
     */
    public function getDomainLang($domain = null)
    {
        if (empty($domain)) {
            $domain = $this->domain;
        }
        $lang = null;
        if (!empty($this->domains[$domain]['lang'])) {
            $lang = $this->domains[$domain]['lang'];
        }
        return $lang;
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

        $base = !empty($this->domains[$this->domain]) ? $this->domains[$this->domain] : '';
        $base = is_array($base) ? $base['base'] : $base;
        if (!empty($base) && !empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $base) !== 0) {
            wp_redirect(home_url($base));
            exit;
        }
    }

    /**
     * Sets up the required settings to show in the admin.
     *
     * @return void
     */
    public function settings()
    {
        add_settings_section('multiple-domain', __('Multiple Domain', 'multiple-domain'), [
            $this,
            'settingsHeading',
        ], 'general');
        add_settings_field('multiple-domain-domains', __('Domains', 'multiple-domain'), [
            $this,
            'settingsFieldsForDomains',
        ], 'general', 'multiple-domain');
        add_settings_field('multiple-domain-options', __('Options', 'multiple-domain'), [
            $this,
            'settingsFieldsForOptions',
        ], 'general', 'multiple-domain');

        register_setting('general', 'multiple-domain-domains', [
            $this,
            'sanitizeDomainsSettings',
        ]);
        register_setting('general', 'multiple-domain-ignore-default-ports', [
            $this,
            'castToBool',
        ]);
    }

    /**
     * Sanitizes the domains settings.
     *
     * It takes the value sent by the user in the settings form and parses it
     * to store in the correct format.
     *
     * @param  array $value The user defined option value.
     * @return array The sanitized option value.
     */
    public function sanitizeDomainsSettings($value)
    {
        $domains = [];
        if (is_array($value)) {
            foreach ($value as $row) {
                if (empty($row['host'])) {
                    continue;
                }
                $base = !empty($row['base']) ? $row['base'] : null;
                $lang = !empty($row['lang']) ? $row['lang'] : null;
                $domains[$row['host']] = [
                    'base' => $base,
                    'lang' => $lang,
                ];
            }
        }
        return $domains;
    }

    /**
     * Casts the given value to boolean.
     *
     * @param  mixed $value The value to cast.
     * @return bool A bolean representing the passed value.
     * @since  1.0.0
     */
    public function castToBool($value)
    {
        return (bool) $value;
    }

    /**
     * Renders the settings heading.
     *
     * @return void
     */
    public function settingsHeading()
    {
        echo '<p id="multiple-domain">' . __('You can use multiple domains in your WordPress defining them below. '
            . 'It\'s possible to limit the access for each domain to a base URL.', 'multiple-domain') . '</p>';
    }

    /**
     * Renders the fields for setting domains.
     *
     * @return void
     */
    public function settingsFieldsForDomains()
    {
        $fields = '';
        $counter = 0;
        foreach ($this->domains as $domain => $values) {
            /*
             * Backward compatibility with earlier versions.
             */
            if (is_string($values)) {
                $base = $value;
                $lang = null;
            } else {
                $base = !empty($values['base']) ? $values['base'] : null;
                $lang = !empty($values['lang']) ? $values['lang'] : null;
            }
            $fields .= $this->getDomainFields($counter++, $domain, $base, $lang);
        }
        if (empty($fields)) {
            $fields = $this->getDomainFields(0);
        }
        $fieldsToAdd = $this->getDomainFields('COUNT');
        echo $fields
            . '<p><button type="button" class="button multiple-domain-add">'
            . __('Add domain', 'multiple-domain') . '</button></p>'
            . '<p class="description">'
            . __('A domain may contain the port number. If a base URL restriction is set for a domain, '
            . 'all requests that don\'t start with the base URL will be redirected to the base URL. '
            . '<b>Example</b>: the domain and base URL are <code>example.com</code> and </code>/base/path</code>, '
            . 'when requesting <code>example.com/other/path</code> it will be redirected to '
            . '<code>example.com/base/path</code>. Additionaly, it\'s possible to set a language for each domain, '
            . 'which will be used to add <code>&lt;link&gt;</code> tags with a <code>hreflang</code> '
            . 'attribute to the document head.', 'multiple-domain') . '</p>'
            . '<script type="text/javascript">var multipleDomainFields = ' . json_encode($fieldsToAdd) . ';</script>';
    }

    /**
     * Renders the fields for plugin options.
     *
     * @return void
     * @since  1.0.0
     */
    public function settingsFieldsForOptions()
    {
        $checked = $this->shouldIgnoreDefaultPorts() ? 'checked' : '';
        echo '<label><input type="checkbox" name="multiple-domain-ignore-default-ports" value="1" ' . $checked . '> '
            . __('Ignore default ports', 'multiple-domain') . '</label>'
            . '<p class="description">' . __('When enabled, removes the port from URL when redirecting and '
            . 'it\'s a default HTTP (<code>80</code>) or HTTPS (<code>443</code>) port.', 'multiple-domain') . '</p>';
    }

    /**
     * Enqueues the required scripts.
     *
     * @param  string $hook The current admin page.
     * @return void
     * @since  0.3
     */
    public function scripts($hook)
    {
        if ($hook !== 'options-general.php') {
            return;
        }
        $settingsPath = plugins_url('settings.js', MULTPLE_DOMAIN_PLUGIN);
        wp_enqueue_script('multiple-domain-settings', $settingsPath, [ 'jquery' ], self::VERSION, true);
    }

    /**
     * Replaces the domain.
     *
     * The domain in the given URL is replaced by the current domain. If the
     * URL contains `/wp-admin/` it'll be ignored when replacing the domain and
     * returned as is.
     *
     * @param  string $url The URL to fix.
     * @return string The domain replaced URL.
     */
    public function replaceDomain($url)
    {
        if (!$this->shouldReplaceDomain()) {
            return $url;
        }

        if (array_key_exists($this->domain, $this->domains) && !preg_match('/\/wp-admin\/?/', $url)) {
            $domain = $this->getDomainFromUrl($url);
            $url = str_replace($domain, $this->domain, $url);
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
        $uploads['url'] = $this->replaceDomain($uploads['url']);
        $uploads['baseurl'] = $this->replaceDomain($uploads['baseurl']);
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
        if (array_key_exists($this->domain, $this->domains)) {
            $regex = '/(https?:\/\/)' . preg_quote($this->originalDomain) . '/i';
            $content = preg_replace($regex, '${1}' . $this->domain, $content);
        }
        return $content;
    }

    /**
     * Add all plugin domains to allowed origins.
     *
     * This filter is used to avoid CORS issues.
     *
     * @param  array $origins
     * @return array
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
     * Add the "Settings" link to the plugin row in the plugins page.
     *
     * @param  array $links
     * @return array
     * @since  1.0.0
     */
    public function actionLinks($links)
    {
        $url = admin_url('options-general.php#multiple-domain');
        $link = '<a href="' . $url . '">' . __('Settings', 'multiple-domain') . '</a>';
        array_unshift($links, $link);
        return $links;
    }

    /**
     * Add `hreflang` links to head for SEO purpose.
     *
     * @return void
     * @author Alexander Nosov <https://github.com/cyberaleks>
     * @since  0.4
     */
    public function addHrefLangHeader()
    {
        /**
         * The WP class instance.
         *
         * @var WP
         */
        global $wp;

        $uri = '/' . ltrim(add_query_arg([], $wp->request), '/');
        $protocol = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http://' : 'https://';
        $this->outputHrefLangHeader($protocol . $this->originalDomain . $uri);

        foreach ($this->domains as $domain => $values) {
            if (!is_array($values) || empty($values['lang'])) {
                continue;
            }

            $url = $domain . $values['base'] . $uri;

            /*
             * Prepend the current protocol if none is set.
             */
            if (!preg_match('/https?:\/\//', $values['base'])) {
                $url = $protocol . $url;
            }
            $this->outputHrefLangHeader($url, $values['lang']);
        }
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
     * Initialize the class attributes.
     *
     * @return void
     * @since  0.8
     */
    private function initAttributes()
    {
        $ignoreDefaultPort = $this->shouldIgnoreDefaultPorts();
        $headerHost = !empty($_SERVER['HTTP_X_HOST']) ? $_SERVER['HTTP_X_HOST'] : ( !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '' );
        if (!empty($headerHost)) {
            $domain = $headerHost;
            $matches = [];
            if (preg_match('/^(.*):(\d+)$/', $domain, $matches) && $this->isDefaultPort($matches[2])) {
                $domain = $matches[1];
            }
            $this->domain = $domain;
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
     * Indicate whether the default ports should be ingored.
     *
     * This check is used when redirecting from a domain to another, for
     * example.
     *
     * @return bool
     * @since  1.0.0
     */
    private function shouldIgnoreDefaultPorts()
    {
        return (bool) get_option('multiple-domain-ignore-default-ports');
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
        add_action('admin_init', [ $this, 'settings' ]);
        add_action('admin_enqueue_scripts', [ $this, 'scripts' ]);
        add_action('wp_head', [ $this, 'addHrefLangHeader' ]);
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
        add_filter('content_url', [ $this, 'replaceDomain' ]);
        add_filter('option_siteurl', [ $this, 'replaceDomain' ]);
        add_filter('option_home', [ $this, 'replaceDomain' ]);
        add_filter('plugins_url', [ $this, 'replaceDomain' ]);
        add_filter('wp_get_attachment_url', [ $this, 'replaceDomain' ]);
        add_filter('get_the_guid', [ $this, 'replaceDomain' ]);

        // Specific domain replacement filters
        add_filter('upload_dir', [ $this, 'fixUploadDir' ]);
        add_filter('the_content', [ $this, 'fixContentUrls' ], 20);
        add_filter('allowed_http_origins', [ $this, 'addAllowedOrigins' ]);

        // Other filters
        add_filter('plugin_action_links_' . plugin_basename(MULTPLE_DOMAIN_PLUGIN), [ $this, 'actionLinks' ]);
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

    /**
     * Parses the given URL to return only its domain.
     *
     * The server port may be included in the returning value.
     *
     * @param string $url The URL to parse.
     * @param bool $ignoreDefaultPort If `true` is passed to this value, a
     *              default HTTP or HTTPS port will be ignored even if it's
     *              present in the URL.
     * @return string The domain.
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
     * @return bool Indicates if the port is a default one.
     * @since  0.2
     */
    private function isDefaultPort($port)
    {
        $port = (int) $port;
        return $port === 80 || $port === 443;
    }

    /**
     * Returns the fields for a domain setting.
     *
     * @param  int $count The field count. It's used within the field name,
     *              since it's an array.
     * @param  string $host The host field value.
     * @param  string $base The base URL field value.
     * @param  string $lang The language field value.
     * @return string The rendered group of fields.
     * @since  0.3
     */
    private function getDomainFields($count, $host = null, $base = null, $lang = null)
    {
        $fields = '<p class="multiple-domain-domain">'
            . '<input type="text" name="multiple-domain-domains[' . $count . '][host]" value="' . ($host ?: '') . '" '
            . 'class="regular-text code" placeholder="example.com" title="'
            . __('Domain', 'multiple-domain') . '"> '
            . '<input type="text" name="multiple-domain-domains[' . $count . '][base]" value="' . ($base ?: '') . '" '
            . 'class="regular-text code" placeholder="/base/path" title="'
            . __('Base path restriction', 'multiple-domain') . '"> '
            . $this->getLangField($count, $lang) . ' '
            . '<button type="button" class="button multiple-domain-remove"><span class="required">'
            . __('Remove', 'multiple-domain') . '</span></button>'
            . '</p>';
        return $fields;
    }

    /**
     * Gets the language field for domain settings.
     *
     * @param  int $count The field count. It's used within the field name,
     *              since it's an array.
     * @param  string $lang The selected language.
     * @return string The rendered field.
     * @since  1.0.0
     */
    private function getLangField($count, $lang = null)
    {
        /*
         * Backward compability with a locale defined in previous versions.
         *
         * The HTML `lang` attribute uses a dash (`en-US`) to separate language
         * and region, but WP languages have an underscore (`en_US`).
         */
        if (!empty($lang)) {
            $lang = str_replace('-', '_', $lang);
        }

        $locales = $this->getLocales();

        $field = '<select name="multiple-domain-domains[' . $count . '][lang]">'
            . '<option value="">None</option>'
            . '<option value="" disabled="disabled">--</option>';
        foreach ($locales as $code => $name) {
            $field .= '<option value="' . $code . '">' . $name . '</option>';
        }
        $field .= '</select>';

        return $field;
    }

    /**
     * Get the list of locales.
     *
     * The keys of the returned array are locale codes and the values are
     * their names.
     *
     * A cached version will be returned if available.
     *
     * @return array The locales list.
     */
    private function getLocales()
    {
        $locales = wp_cache_get('locales', 'multiple-domain');

        if (empty($locales)) {
            $locales = $this->getLocalesFromFile();
            wp_cache_set('locales', $locales, 'multiple-domain');
        }

        return $locales;
    }

    /**
     * Get the list of locales from the source file.
     *
     * The keys of the returned array are locale codes and the values are
     * their names.
     *
     * @return array The locales list.
     */
    private function getLocalesFromFile()
    {
        $locales = [];

        $handle = fopen(dirname(MULTPLE_DOMAIN_PLUGIN) . '/locales.csv', 'r');
        while (($row = fgetcsv($handle)) !== false) {
            $locales[$row[0]] = $row[1];
        }
        fclose($handle);
        asort($locales);

        return $locales;
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
    private function outputHrefLangHeader($url, $lang = 'x-default')
    {
        $lang = str_replace('_', '-', $lang);
        printf('<link rel="alternate" href="%s" hreflang="%s"/>', $url, $lang);
    }

    /**
     * Checks whether a domain replacement should be made.
     *
     * @return bool
     * @since  0.8.5
     */
    private function shouldReplaceDomain()
    {
        if (is_admin()) {
            return false;
        }

        /*
         * `is_admin` returns `false` for `wp-login.php` page. We need to do a
         * manual check, then.
         */
        $path = str_replace([ '\\', '/' ], DIRECTORY_SEPARATOR, ABSPATH);
        $includedFiles = get_included_files();
        $isLogin = in_array($path . 'wp-login.php', $includedFiles)
            || in_array($path . 'wp-register.php', $includedFiles);

        return !$isLogin;
    }
}
