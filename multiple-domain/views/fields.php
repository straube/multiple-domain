<?php

/**
 * Making sure the required vars are set.
 */
assert(isset($count) && isset($protocol) && isset($host) && isset($base) && isset($langField));

?><p class="multiple-domain-domain">
    <select
        name="multiple-domain-domains[<?php echo $count; ?>][protocol]"
        title="<?php _e('Protocol', 'multiple-domain'); ?>"
    >
        <option
            value="auto"
            <?php if (empty($protocol) || $protocol === 'auto') : ?>
                selected
            <?php endif; ?>
        >Auto</option>
        <option
            value="http"
            <?php if ($protocol === 'http') : ?>
                selected
            <?php endif; ?>
        >http://</option>
        <option
            value="https"
            <?php if ($protocol === 'https') : ?>
                selected
            <?php endif; ?>
        >https://</option>
    </select>
    <input
        type="text"
        name="multiple-domain-domains[<?php echo $count; ?>][host]"
        value="<?php echo $host ?: ''; ?>"
        class="regular-text code"
        placeholder="example.com"
        title="<?php _e('Domain', 'multiple-domain'); ?>"
    >
    <input
        type="text"
        name="multiple-domain-domains[<?php echo $count; ?>][base]"
        value="<?php echo $base ?: ''; ?>"
        class="regular-text code"
        placeholder="/base/path" title="<?php _e('Base path restriction', 'multiple-domain'); ?>"
    >
    <?php echo $langField; ?>
    <button type="button" class="button multiple-domain-remove">
        <span class="required"><?php _e('Remove', 'multiple-domain'); ?></span>
    </button>
</p>
