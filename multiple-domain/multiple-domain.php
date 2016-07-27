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


class MultipleDomain
{

    private $host = null;

    private $domains = [];

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

    public function setup()
    {
        add_action('init', [ $this, 'redirect' ]);
        add_action('admin_init', [ $this, 'settings' ]);
        add_filter('option_home', [ $this, 'filterHome' ]);
    }

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

    public function settings()
    {
        add_settings_section('multiple-domain', 'Multiple Domain', [ $this, 'settingsHeading' ], 'general');
        add_settings_field('multiple-domain-domains', 'Domains', [ $this, 'settingsField' ], 'general', 'multiple-domain');
        register_setting('general', 'multiple-domain-domains', [ $this, 'sanitizeSettings' ]);
    }

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

    public function settingsHeading()
    {
        echo '<p>' . __('You can use multiple domains in your WordPress defining them below. It\'s possible to limit the access for each domain to a base URL.', 'multiple-domain') . '</p>';
    }

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

$domains = new MultipleDomain();
$domains->setup();
