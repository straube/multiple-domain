<?php

/**
 * Making sure the required vars are set.
 */
assert(isset($count) && isset($locales) && isset($lang));

?><select name="multiple-domain-domains[<?php echo $count; ?>][lang]">
    <option value=""><?php _e('None', 'multiple-domain'); ?></option>
    <option value="" disabled="disabled">--</option>
    <?php foreach ($locales as $code => $name) : ?>
        <option
            value="<?php echo esc_attr($code); ?>"
            <?php if ($lang === $code) : ?>
                selected
            <?php endif; ?>
        ><?php echo $name; ?></option>
    <?php endforeach; ?>
</select>
