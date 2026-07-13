<?php
/**
 * Admin Settings Page
 */

add_action( 'admin_menu', 'ghana_districts_add_admin_menu' );

function ghana_districts_add_admin_menu() {
    add_options_page(
        'Ghana Districts Settings',
        'Ghana Districts',
        'manage_options',
        'ghana-districts',
        'ghana_districts_settings_page'
    );
}

function ghana_districts_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Ghana Districts Settings', 'ghana-districts' ); ?></h1>
        
        <div class="card">
            <h2><?php esc_html_e( 'Shortcode Usage', 'ghana-districts' ); ?></h2>
            <p><?php esc_html_e( 'Add these shortcodes to any page or post:', 'ghana-districts' ); ?></p>
            <code>[ghana_regions]</code> – <?php esc_html_e( 'Region dropdown', 'ghana-districts' ); ?><br>
            <code>[ghana_districts]</code> – <?php esc_html_e( 'District dropdown', 'ghana-districts' ); ?>
        </div>

        <div class="card">
            <h2><?php esc_html_e( 'Form Plugin Compatibility', 'ghana-districts' ); ?></h2>
            <p><?php esc_html_e( 'These shortcodes work with Contact Form 7, WPForms, Gravity Forms, and any plugin that supports WordPress shortcodes.', 'ghana-districts' ); ?></p>
        </div>

        <div class="card">
            <h2><?php esc_html_e( 'Plugin Information', 'ghana-districts' ); ?></h2>
            <p><strong><?php esc_html_e( 'Version:', 'ghana-districts' ); ?></strong> <?php echo esc_html( GHANA_DISTRICTS_VERSION ); ?></p>
            <p><strong><?php esc_html_e( 'Regions:', 'ghana-districts' ); ?></strong> 16</p>
            <p><strong><?php esc_html_e( 'Districts:', 'ghana-districts' ); ?></strong> 260+</p>
            <p><strong><?php esc_html_e( 'Support:', 'ghana-districts' ); ?></strong> ernestamart@gmail.com</p>
        </div>
    </div>
    <?php
}