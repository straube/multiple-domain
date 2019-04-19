<?php

/**
 * Making sure the required vars are set.
 */
assert(isset($ignoreDefaultPorts) && isset($addCanonical));

?><label>
    <input
        type="checkbox"
        name="multiple-domain-ignore-default-ports"
        value="1"
        <?php if ($ignoreDefaultPorts) : ?>
            checked
        <?php endif; ?>
    >
    <?php _e('Ignore default ports', 'multiple-domain'); ?>
</label>
<p class="description">
    <?php _e('When enabled, removes the port from URL when redirecting and it\'s a '
        . 'default HTTP (<code>80</code>) or HTTPS (<code>443</code>) port.', 'multiple-domain'); ?>
</p>
<br />
<label>
    <input
        type="checkbox"
        name="multiple-domain-add-canonical"
        value="1"
        <?php if ($addCanonical) : ?>
            checked
        <?php endif; ?>
    >
    <?php _e('Add canonical links', 'multiple-domain'); ?>
</label>
<p class="description">
    <?php _e(
        'When enabled, adds canonical link tags to pages. '
            . 'The domain for canonical links will be the original domain where WordPress is installed. '
            . 'You may want to keep this option unchecked if you have a SEO plugin (e.g. Yoast) installed.',
        'multiple-domain'
    ); ?>
</p>
