<?php

/*
Plugin Name: Multiple Domain
Plugin URI:  http://creativeduo.com.br
Description: This plugin allows you to have multiple domains in a single 
             WordPress installation and enables custom redirects for each 
             domain.
Version:     0.1
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
     * The current host.
     *
     * @var string
     */
    private $host = null;

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
     * Sets the host and domains based on server info and WordPress settings.
     */
    public function __construct()
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $this->host = $_SERVER['HTTP_HOST'];
        }
        $this->domains = get_option('multiple-domain-domains');
        if (!is_array($this->domains)) {
            $this->domains = [];
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
     * When the current domains has a base URL restriction, redirects the user 
     * if the current request URI doesn't match it.
     *
     * @return void
     */
    public function redirect()
    {
        if (!empty($this->domains[$this->host])) {
            $base = $this->domains[$this->host];
            if (strpos($_SERVER['REQUEST_URI'], $base) !== 0) {
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
        if (array_key_exists($this->host, $this->domains)) {
            $parts = parse_url($home);
            $search = $parts['host'];
            if (!empty($parts['port'])) {
                $search .= ':' . $parts['port'];
            }
            $home = str_replace($search, $this->host, $home);
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
        add_settings_section('multiple-domain', 'Multiple Domain', [ $this, 'settingsHeading' ], 'general');
        add_settings_field('multiple-domain-domains', 'Domains', [ $this, 'settingsField' ], 'general', 'multiple-domain');
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
        foreach ($this->domains as $host => $base) {
            if (!empty($value)) {
                $value .= "\n";
            }
            $value .= $host;
            if (!empty($base)) {
                $value .= ',' . $base;
            }
        }
        echo '<textarea id="multiple-domain-domains" name="multiple-domain-domains" class="large-text code" rows="5">' . $value . '</textarea>'
            . '<p class="description">' . __('Add one domain per line, without protocol. To define a base URL restriction, add it in the same line as the domain after a comma. All requests to a URL under the domain that don\'t start with the base URL, will be redirected to the base URL. Example: <code>example.com,/base/path</code>', 'multiple-domain') . '</p>';
    }
}

// Plugin bootstrap
$domains = new MultipleDomain();
$domains->setup();
