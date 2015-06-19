<div class="wrap">
    <h2>WooCommerce Auto Category Thumbnails</h2>
    <form method="post" action="options.php">
        <?php wp_nonce_field( 'update-options' ); ?>
        <?php settings_fields( 'wcact_settings' ); ?>
        <?php do_settings_sections( 'wcact_settings' ); ?>
        <?php submit_button( 'Submit', 'primary', 'submit', false ); ?>
        <?php submit_button( 'Reset to defaults', 'secondary', 'wcact-reset', false ); ?>
    </form>
</div>