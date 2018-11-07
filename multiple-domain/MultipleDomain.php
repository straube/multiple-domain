<?php

/**
 * Mutiple Domain WordPress plugin.
 *
 * @author  Gustavo Straube <https://github.com/straube>
 * @author  Vivek Athalye <https://github.com/vnathalye>
 * @author  Clay Allsopp <https://github.com/clayallsopp>
 * @author  Alexander Nosov <https://github.com/cyberaleks>
 * @author  João Faria <https://github.com/jffaria>
 * @version 0.8.1
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
    const VERSION = '0.8.1';

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
     * Adds actions and filters required by the plugin.
     *
     * @return void
     */
    public function setup()
    {
        $this->initAttributes();
        $this->hookActions();
        $this->hookFilters();
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

        // Store value of 'REQUEST_URI' for further checks.
        // It's only used for checking.
        $request_uri = ( ! empty( $_SERVER['REQUEST_URI'] ) ? filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL ) : '' ); // WPCS: input var ok.

        if ( ! empty( $base ) && ! empty( $request_uri ) && strpos( $request_uri, $base ) !== 0 ) {
            wp_redirect( home_url( $base ) );
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
        printf( '<p>%s</p>',
            esc_html__( 'You can use multiple domains in your WordPress defining them below. 
            It\'s possible to limit the access for each domain to a base URL.', 'multiple-domain' )
        );
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

        // Array to whitelist html elements to be allowed in output.
        $allowed_html = array(
            'p'        => array(
                'class' => array(),
            ),
            'input'    => array(
                'type'        => 'text',
                'name'        => array(),
                'value'       => array(),
                'class'       => array(),
                'placeholder' => array(),
                'title'       => array(),
            ),
            'button'   => array(
                'type'  => 'button',
                'class' => array(),
            ),
            'span'     => array(
                'class' => array(),
            ),
            'select'   => array(
                'name' => array(),
                'id'   => array(),
            ),
            'optgroup' => array(
                'label' => array(),
            ),
            'option'   => array(
                'value'          => array(),
                'lang'           => array(),
                'data-installed' => array(),
                'selected'       => array()
            )
        );

        echo wp_kses( $fields, $allowed_html );
        echo '<p><button type="button" class="button multiple-domain-add">';
        esc_html_e('Add domain', 'multiple-domain');
        echo '</button></p>';
        echo '<p class="description">';
        esc_html_e( 'A domain may contain the port number. If a base URL restriction is set for a domain,
            all requests that don\'t start with the base URL will be redirected to the base URL. ', 'multiple-domain' );
        printf(
            '<b>%1$s</b>: %2$s <code>%3$s</code> %4$s <code>%5$s</code>,',
            esc_html__( 'Example', 'multiple-domain' ),
            esc_html__( 'the domain and base URL are', 'multiple-domain' ),
            esc_html( 'example.com' ),
            esc_html__( 'and', 'multiple-domain' ),
            esc_html( '/base/path' )
            );
        printf(
            ' %1$s <code>%2$s</code> %3$s <code>%2$s</code>.',
            esc_html__( 'when requesting', 'multiple-domain' ),
            esc_html( 'example.com/other/path' ),
            esc_html__( 'it will be redirected to', 'multiple-domain' )
        );
        printf(
            ' %1$s <code>&lt;link&gt;</code> %2$s <code>hreflang</code> %3$s',
            esc_html__( 'Additionaly, it\'s possible to set a language for each domain, which will be used to add', 'multiple-domain' ),
            esc_html__( 'tags with a', 'multiple-domain' ),
            esc_html__(  'attribute to the document head.', 'multiple-domain' )
            );
        echo '</p><script type="text/javascript">var multipleDomainFields = ' . wp_json_encode( $fieldsToAdd ) . ';</script>';
    }

    /**
     * Renders the fields for plugin options.
     *
     * @return void
     * @since  1.0.0
     */
    public function settingsFieldsForOptions()
    {

        echo '<label><input type="checkbox" name="multiple-domain-ignore-default-ports" value="1" ' . checked( $this->shouldIgnoreDefaultPorts(), true, false ) . '> ';
        esc_html_e('Ignore default ports', 'multiple-domain');
        echo '</label><p class="description">';
        printf( '%1$s (<code>80</code>) %2$s (<code>443</code>) %3$s',
            esc_html__( 'When enabled, removes the port from URL when redirecting and '
                        . 'it\'s a default HTTP', 'multiple-domain' ),
        esc_html__( 'or HTTPS', 'multiple-domain' ),
        esc_html__( 'port.', 'multiple-domain')
        );
        echo '</p>';
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
        $settingsPath = plugins_url('settings.js', __FILE__);
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
            $content = preg_replace($regex, '$1' . $this->domain, $content);
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

        $uri            = add_query_arg([], $wp->request);
        $protocol_value = ( ! empty( $_SERVER['HTTPS'] ) ? filter_var( $_SERVER['HTTPS'], FILTER_SANITIZE_STRING ) : '' ); // WPCS: input var ok.
        $protocol       = ( empty( $protocol_value ) || 'off' === $protocol_value ? 'http://' : 'https://' );

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
     * Initialize the class attributes.
     *
     * @return void
     * @since  0.8
     */
    private function initAttributes()
    {
        $ignoreDefaultPort = $this->shouldIgnoreDefaultPorts();
        $http_host         =  ( ! empty( $_SERVER['HTTP_HOST'] ) ? filter_var( $_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL ) : '' ); // WPCS: input var ok.
        $headerHost        = ( ! empty( $_SERVER['HTTP_X_HOST'] ) ? filter_var( $_SERVER['HTTP_X_HOST'], FILTER_SANITIZE_URL ) : $http_host ); // WPCS: input var ok.

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
        add_filter('content_url', [ $this, 'replaceDomain' ]);
        add_filter('option_siteurl', [ $this, 'replaceDomain' ]);
        add_filter('option_home', [ $this, 'replaceDomain' ]);
        add_filter('plugins_url', [ $this, 'replaceDomain' ]);
        add_filter('wp_get_attachment_url', [ $this, 'replaceDomain' ]);
        add_filter('upload_dir', [ $this, 'fixUploadDir' ]);
        add_filter('the_content', [ $this, 'fixContentUrls' ], 20);
        add_filter('allowed_http_origins', [ $this, 'addAllowedOrigins' ]);
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
        $parts  = wp_parse_url($url);
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
            . '<input type="text" name="multiple-domain-domains[' . esc_attr( $count ) . '][host]" value="' . ( $host ? esc_attr( $host ) : '') . '" '
            . 'class="regular-text code" placeholder="example.com" title="'
            . __('Domain', 'multiple-domain') . '"> '
            . '<input type="text" name="multiple-domain-domains[' . esc_attr( $count ) . '][base]" value="' . ( $base ? esc_attr( $base ) : '') . '" '
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

        require_once ABSPATH . 'wp-admin/includes/translation-install.php';
        $translations = wp_get_available_translations();
        if (defined('ARRAY_FILTER_USE_KEY')) {
            $translations = array_filter($translations, [ $this, 'isNotWordpressVariation' ], ARRAY_FILTER_USE_KEY);
        }

        return wp_dropdown_languages([
            'name' => 'multiple-domain-domains[' . $count . '][lang]',
            'selected' => $lang,
            'echo' => false,
            'translations' => $translations,
        ]);
    }

    /**
     * Checks whether the given language is not just an WordPress variation
     * (formal or informal).
     *
     * @param  string $language The language to check.
     * @return bool The verification result.
     */
    private function isNotWordpressVariation($language)
    {
        return strpos($language, 'formal') === false;
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
        printf('<link rel="alternate" href="%s" hreflang="%s"/>', esc_url( $url ), esc_attr( $lang ) );
    }
}
