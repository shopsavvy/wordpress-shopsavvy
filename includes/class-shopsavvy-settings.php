<?php
/**
 * ShopSavvy settings page.
 *
 * Registers Settings > ShopSavvy in the WordPress admin with fields
 * for API key, cache duration, default display style, REST toggle,
 * and API usage meter.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Settings {

    /**
     * Hook into WordPress.
     */
    public static function init(): void {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    /**
     * Add the settings page under Settings menu.
     */
    public static function add_menu_page(): void {
        add_options_page(
            __( 'ShopSavvy Settings', 'shopsavvy' ),
            __( 'ShopSavvy', 'shopsavvy' ),
            'manage_options',
            'shopsavvy-settings',
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Register settings, sections, and fields.
     */
    public static function register_settings(): void {
        // --- API section ---
        add_settings_section(
            'shopsavvy_api_section',
            __( 'API Configuration', 'shopsavvy' ),
            array( __CLASS__, 'render_api_section' ),
            'shopsavvy-settings'
        );

        register_setting( 'shopsavvy_settings', 'shopsavvy_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        add_settings_field(
            'shopsavvy_api_key',
            __( 'API Key', 'shopsavvy' ),
            array( __CLASS__, 'render_api_key_field' ),
            'shopsavvy-settings',
            'shopsavvy_api_section'
        );

        // --- Display section ---
        add_settings_section(
            'shopsavvy_display_section',
            __( 'Display Settings', 'shopsavvy' ),
            null,
            'shopsavvy-settings'
        );

        register_setting( 'shopsavvy_settings', 'shopsavvy_cache_duration', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 3600,
        ) );

        add_settings_field(
            'shopsavvy_cache_duration',
            __( 'Cache Duration', 'shopsavvy' ),
            array( __CLASS__, 'render_cache_duration_field' ),
            'shopsavvy-settings',
            'shopsavvy_display_section'
        );

        register_setting( 'shopsavvy_settings', 'shopsavvy_default_style', array(
            'type'              => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_style' ),
            'default'           => 'table',
        ) );

        add_settings_field(
            'shopsavvy_default_style',
            __( 'Default Display Style', 'shopsavvy' ),
            array( __CLASS__, 'render_default_style_field' ),
            'shopsavvy-settings',
            'shopsavvy_display_section'
        );

        // --- Advanced section ---
        add_settings_section(
            'shopsavvy_advanced_section',
            __( 'Advanced', 'shopsavvy' ),
            null,
            'shopsavvy-settings'
        );

        register_setting( 'shopsavvy_settings', 'shopsavvy_rest_enabled', array(
            'type'              => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
            'default'           => '0',
        ) );

        add_settings_field(
            'shopsavvy_rest_enabled',
            __( 'Public REST Endpoint', 'shopsavvy' ),
            array( __CLASS__, 'render_rest_enabled_field' ),
            'shopsavvy-settings',
            'shopsavvy_advanced_section'
        );
    }

    /**
     * Render the settings page.
     */
    public static function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'ShopSavvy Settings', 'shopsavvy' ); ?></h1>

            <?php self::render_credit_usage(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'shopsavvy_settings' );
                do_settings_sections( 'shopsavvy-settings' );
                submit_button();
                ?>
            </form>

            <hr />
            <h2><?php esc_html_e( 'Cache Management', 'shopsavvy' ); ?></h2>
            <p><?php esc_html_e( 'Clear all cached ShopSavvy API responses.', 'shopsavvy' ); ?></p>
            <form method="post" action="">
                <?php wp_nonce_field( 'shopsavvy_flush_cache', 'shopsavvy_flush_nonce' ); ?>
                <input type="hidden" name="shopsavvy_flush_cache" value="1" />
                <?php submit_button( __( 'Clear Cache', 'shopsavvy' ), 'secondary' ); ?>
            </form>
            <?php self::handle_flush_cache(); ?>
        </div>
        <?php
    }

    /**
     * Render the API section description.
     */
    public static function render_api_section(): void {
        printf(
            '<p>%s <a href="https://shopsavvy.com/data" target="_blank">%s</a></p>',
            esc_html__( 'Enter your ShopSavvy Data API key.', 'shopsavvy' ),
            esc_html__( 'Get an API key', 'shopsavvy' )
        );
    }

    /**
     * Render the API key field.
     */
    public static function render_api_key_field(): void {
        $value = get_option( 'shopsavvy_api_key', '' );
        printf(
            '<input type="password" id="shopsavvy_api_key" name="shopsavvy_api_key" value="%s" class="regular-text" autocomplete="off" />',
            esc_attr( $value )
        );

        if ( ! empty( $value ) ) {
            $validation = ShopSavvy_Client::validate_key( $value );
            if ( $validation['valid'] ) {
                printf( '<span class="dashicons dashicons-yes-alt" style="color:#46b450;margin-left:8px;line-height:1.8;"></span>' );
            } else {
                printf(
                    '<span class="dashicons dashicons-warning" style="color:#dc3232;margin-left:8px;line-height:1.8;" title="%s"></span>',
                    esc_attr( $validation['error'] ?? __( 'Key validation failed.', 'shopsavvy' ) )
                );
            }
        }
    }

    /**
     * Render the cache duration dropdown.
     */
    public static function render_cache_duration_field(): void {
        $value   = (int) get_option( 'shopsavvy_cache_duration', 3600 );
        $options = array(
            300   => __( '5 minutes', 'shopsavvy' ),
            900   => __( '15 minutes', 'shopsavvy' ),
            1800  => __( '30 minutes', 'shopsavvy' ),
            3600  => __( '1 hour', 'shopsavvy' ),
            7200  => __( '2 hours', 'shopsavvy' ),
            21600 => __( '6 hours', 'shopsavvy' ),
            43200 => __( '12 hours', 'shopsavvy' ),
            86400 => __( '24 hours', 'shopsavvy' ),
        );

        echo '<select id="shopsavvy_cache_duration" name="shopsavvy_cache_duration">';
        foreach ( $options as $seconds => $label ) {
            printf(
                '<option value="%d"%s>%s</option>',
                $seconds,
                selected( $value, $seconds, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
        printf(
            '<p class="description">%s</p>',
            esc_html__( 'How long to cache API responses. Longer durations use fewer API credits.', 'shopsavvy' )
        );
    }

    /**
     * Render the default style dropdown.
     */
    public static function render_default_style_field(): void {
        $value   = get_option( 'shopsavvy_default_style', 'table' );
        $options = array(
            'table'  => __( 'Table', 'shopsavvy' ),
            'card'   => __( 'Card', 'shopsavvy' ),
            'inline' => __( 'Inline', 'shopsavvy' ),
        );

        echo '<select id="shopsavvy_default_style" name="shopsavvy_default_style">';
        foreach ( $options as $key => $label ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $key ),
                selected( $value, $key, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
    }

    /**
     * Render the REST enabled toggle.
     */
    public static function render_rest_enabled_field(): void {
        $value = get_option( 'shopsavvy_rest_enabled', '0' );
        printf(
            '<label><input type="checkbox" name="shopsavvy_rest_enabled" value="1" %s /> %s</label>',
            checked( $value, '1', false ),
            esc_html__( 'Enable the wp-json/shopsavvy/v1/search public endpoint', 'shopsavvy' )
        );
        printf(
            '<p class="description">%s</p>',
            esc_html__( 'When enabled, front-end JavaScript and external clients can query ShopSavvy through your site. Rate limited to 10 requests per minute per IP.', 'shopsavvy' )
        );
    }

    /**
     * Render the API usage meter.
     */
    private static function render_credit_usage(): void {
        $api_key = get_option( 'shopsavvy_api_key', '' );

        if ( empty( $api_key ) ) {
            return;
        }

        $validation = ShopSavvy_Client::validate_key( $api_key );

        if ( ! $validation['valid'] || ! isset( $validation['credits_remaining'], $validation['credits_total'] ) ) {
            return;
        }

        $remaining = (int) $validation['credits_remaining'];
        $total     = (int) $validation['credits_total'];
        $used      = $total - $remaining;
        $pct       = $total > 0 ? round( ( $used / $total ) * 100 ) : 0;
        $color     = $pct > 90 ? '#dc3232' : ( $pct > 70 ? '#ffb900' : '#46b450' );

        ?>
        <div class="notice notice-info" style="padding:12px 16px;">
            <strong><?php esc_html_e( 'API Usage', 'shopsavvy' ); ?></strong>
            <div style="background:#ddd;border-radius:4px;height:20px;margin:8px 0;overflow:hidden;">
                <div style="background:<?php echo esc_attr( $color ); ?>;height:100%;width:<?php echo esc_attr( $pct ); ?>%;transition:width .3s;"></div>
            </div>
            <span>
                <?php
                printf(
                    /* translators: 1: amount used, 2: total amount, 3: percentage */
                    esc_html__( '%1$s / %2$s used (%3$s%%)', 'shopsavvy' ),
                    number_format_i18n( $used ),
                    number_format_i18n( $total ),
                    $pct
                );
                ?>
            </span>
        </div>
        <?php
    }

    /**
     * Handle the flush-cache form submission.
     */
    private static function handle_flush_cache(): void {
        if (
            ! isset( $_POST['shopsavvy_flush_cache'] ) ||
            ! isset( $_POST['shopsavvy_flush_nonce'] ) ||
            ! wp_verify_nonce( $_POST['shopsavvy_flush_nonce'], 'shopsavvy_flush_cache' )
        ) {
            return;
        }

        ShopSavvy_Cache::flush_all();

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html__( 'ShopSavvy cache cleared.', 'shopsavvy' )
        );
    }

    /**
     * Sanitize the style option.
     *
     * @param string $value Raw value.
     * @return string Sanitized value.
     */
    public static function sanitize_style( string $value ): string {
        $allowed = array( 'table', 'card', 'inline' );
        return in_array( $value, $allowed, true ) ? $value : 'table';
    }

    /**
     * Sanitize a checkbox value.
     *
     * @param mixed $value Raw value.
     * @return string '1' or '0'.
     */
    public static function sanitize_checkbox( mixed $value ): string {
        return '1' === $value ? '1' : '0';
    }
}
