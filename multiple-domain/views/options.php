<label>
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
    <?php _e('When enabled, removes the port from URL when redirecting and it\'s a default HTTP (<code>80</code>) or HTTPS (<code>443</code>) port.', 'multiple-domain'); ?>
</p>
