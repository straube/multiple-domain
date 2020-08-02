<?php

/**
 * Mutiple Domain settings.
 *
 * Integration with WordPress admin.
 *
 * @author  Gustavo Straube <https://github.com/straube>
 * @version 1.0.6
 * @since   0.11.0
 * @package multiple-domain
 */
class MultipleDomainSettings
{

    /**
     * The plugin core instance.
     *
     * @var \MultipleDomain
     */
    private $core;

    /**
     * Create a new instance.
     *
     * Adds actions and filters required by the plugin for the admin.
     *
     * @param \MultipleDomain $core The core plugin class instance.
     */
    public function __construct(MultipleDomain $core)
    {
        $this->core = $core;

        $this->hookActions();
        $this->hookFilters();
    }

    //
    // WordPress API integration
    //

    /**
     * Hook plugin actions to WordPress.
     *
     * @return void
     */
    private function hookActions()
    {
        add_action('admin_init', [ $this, 'settings' ]);
        add_action('admin_enqueue_scripts', [ $this, 'scripts' ]);
    }

    /**
     * Hook plugin filters to WordPress.
     *
     * @return void
     */
    private function hookFilters()
    {
        add_filter('plugin_action_links_' . plugin_basename(MULTIPLE_DOMAIN_PLUGIN), [ $this, 'actionLinks' ]);
    }

    //
    //
    //

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
        register_setting('general', 'multiple-domain-add-canonical', [
            $this,
            'castToBool',
        ]);
    }

    /**
     * Sanitizes the domain settings.
     *
     * It takes the value sent by the user in the settings form and parses it
     * to store in the internal format used by the plugin.
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
                $host = preg_replace('/^https?:\/\//i', '', $row['host']);
                $base = !empty($row['base']) ? $row['base'] : null;
                $lang = !empty($row['lang']) ? $row['lang'] : null;
                $proto = !empty($row['protocol']) ? $row['protocol'] : 'auto';
                $domains[$host] = [
                    'base' => $base,
                    'lang' => $lang,
                    'protocol' => $proto,
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
        echo $this->loadView('heading');
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

        foreach ($this->core->getDomains() as $domain => $values) {
            $base = null;
            $lang = null;
            $protocol = null;

            /*
             * Backward compatibility with earlier versions.
             */
            if (is_string($values)) {
                $base = $values;
            } else {
                $base = !empty($values['base']) ? $values['base'] : null;
                $lang = !empty($values['lang']) ? $values['lang'] : null;
                $protocol = !empty($values['protocol']) ? $values['protocol'] : null;
            }
            $fields .= $this->getDomainFields($counter++, $domain, $base, $lang, $protocol);
        }

        /*
         * Adds a row of empty fields to the settings when no domain is set.
         */
        if ($counter === 0) {
            $fields = $this->getDomainFields($counter);
        }

        $fieldsToAdd = $this->getDomainFields('COUNT');
        echo $this->loadView('domains', compact('fields', 'fieldsToAdd'));
    }

    /**
     * Renders the fields for plugin options.
     *
     * @return void
     */
    public function settingsFieldsForOptions()
    {
        $ignoreDefaultPorts = $this->core->shouldIgnoreDefaultPorts();
        $addCanonical = $this->core->shouldAddCanonical();
        echo $this->loadView('options', compact('ignoreDefaultPorts', 'addCanonical'));
    }

    /**
     * Enqueues the required scripts.
     *
     * @param  string $hook The current admin page.
     * @return void
     */
    public function scripts($hook)
    {
        if ($hook !== 'options-general.php') {
            return;
        }
        $settingsPath = plugins_url('settings.js', MULTIPLE_DOMAIN_PLUGIN);
        wp_enqueue_script('multiple-domain-settings', $settingsPath, [ 'jquery' ], MultipleDomain::VERSION, true);
    }

    /**
     * Add the "Settings" link to the plugin row in the plugins page.
     *
     * @param  array $links The default list of links.
     * @return array The updated list of links.
     */
    public function actionLinks($links)
    {
        $url = admin_url('options-general.php#multiple-domain');
        $link = '<a href="' . $url . '">' . __('Settings', 'multiple-domain') . '</a>';
        array_unshift($links, $link);
        return $links;
    }

    /**
     * Returns the fields for a domain setting.
     *
     * @param  int $count The field count. It's used within the field name,
     *         since it's an array.
     * @param  string $host The host field value.
     * @param  string $base The base URL field value.
     * @param  string $lang The language field value.
     * @param  string $protocol The protocol handling option.
     * @return string The rendered group of fields.
     */
    private function getDomainFields($count, $host = null, $base = null, $lang = null, $protocol = null)
    {
        $langField = $this->getLangField($count, $lang);
        return $this->loadView('fields', compact('count', 'host', 'base', 'protocol', 'langField'));
    }

    /**
     * Gets the language field for domain settings.
     *
     * @param  int $count The field count. It's used within the field name,
     *         since it's an array.
     * @param  string $lang The selected language.
     * @return string The rendered field.
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

        return $this->loadView('lang', compact('count', 'lang', 'locales'));
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

        $handle = fopen(dirname(MULTIPLE_DOMAIN_PLUGIN) . '/locales.csv', 'r');
        while (($row = fgetcsv($handle)) !== false) {
            $locales[$row[0]] = $row[1];
        }
        fclose($handle);
        asort($locales);

        return $locales;
    }

    /**
     * Load a view and return its contents.
     *
     * @param  string $name The view name.
     * @param  array|null $data The data to pass to the view. Each key will be
     *         extracted as a variable into the view file.
     * @return string The view contents.
     */
    private function loadView($name, $data = null)
    {
        $path = sprintf('%s/views/%s.php', dirname(MULTIPLE_DOMAIN_PLUGIN), $name);
        if (!is_file($path)) {
            return false;
        }

        ob_start();
        if (is_array($data)) {
            extract($this->replaceNull($data));
        }
        include $path;
        return ob_get_clean();
    }

    /**
     * Replace all `null` values in an array.
     *
     * @param  array $array The original array.
     * @param  mixed $replacement The value to replace the `null` occurrences.
     * @return array The array with replaced values.
     */
    private function replaceNull($array, $replacement = '')
    {
        return array_map(function ($value) use ($replacement) {
            return $value === null ? $replacement : $value;
        }, $array);
    }
}
