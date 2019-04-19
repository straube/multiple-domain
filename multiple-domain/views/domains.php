<?php

/**
 * Making sure the required vars are set.
 */
assert(isset($fields) && isset($fieldsToAdd));

?><?php echo $fields; ?>
<p>
    <button type="button" class="button multiple-domain-add">
        <?php _e('Add domain', 'multiple-domain'); ?>
    </button>
</p>
<p class="description">
    <?php _e(
        'A domain may contain the port number. '
            . 'If a base URL restriction is set for a domain, '
            . 'all requests that don\'t start with the base URL will be redirected to the base URL. '
            . '<b>Example</b>: the domain and base URL are <code>example.com</code> and <code>/base/path</code>, '
            . 'when requesting <code>example.com/other/path</code> it will be redirected to '
            . '<code>example.com/base/path</code>. '
            . 'Additionaly, it\'s possible to set a language for each domain, which will be used to add '
            . '<code>&lt;link&gt;</code> tags with a <code>hreflang</code> attribute to the document head.',
        'multiple-domain'
    ); ?>
</p>
<script type="text/javascript">
    var multipleDomainFields = <?php echo json_encode($fieldsToAdd); ?>;
</script>
