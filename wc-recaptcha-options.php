<h2>WooCommerce reCAPTCHA</h2>

<form action="options.php" method="post">

<?php
settings_fields(
    'wc_recaptcha_plugin_options'   // option group from register_settigns
);
do_settings_sections(
    'wc_recaptcha_plugin'   // page id
);
?>
    <input name="submit" class="button button-primary" type="submit" value=" <?php echo __('Save') ?> ">
</form>
<p>
