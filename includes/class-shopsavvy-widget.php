<?php
/**
 * ShopSavvy sidebar widget.
 *
 * Displays a compact price comparison in any widget area.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Price_Widget extends WP_Widget {

    /**
     * Register the widget.
     */
    public function __construct() {
        parent::__construct(
            'shopsavvy_price_widget',
            __( 'ShopSavvy Price Comparison', 'shopsavvy' ),
            array(
                'description'                 => __( 'Display price comparisons from thousands of retailers.', 'shopsavvy' ),
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * Front-end output.
     *
     * @param array<string, string> $args     Widget area arguments.
     * @param array<string, mixed>  $instance Widget settings.
     */
    public function widget( $args, $instance ): void {
        $title      = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $title      = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $identifier = ! empty( $instance['identifier'] ) ? sanitize_text_field( $instance['identifier'] ) : '';
        $max        = ! empty( $instance['max_results'] ) ? absint( $instance['max_results'] ) : 3;

        if ( empty( $identifier ) ) {
            return;
        }

        echo $args['before_widget'];

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $result = ShopSavvy_Client::get_offers( $identifier, $max );

        if ( ! $result['success'] ) {
            if ( current_user_can( 'edit_theme_options' ) ) {
                echo '<p class="shopsavvy-error">' . esc_html( $result['error'] ) . '</p>';
            }
            echo $args['after_widget'];
            return;
        }

        // Use card style for widgets since it's most compact.
        echo ShopSavvy_Shortcode::render_offers( $result['data'], 'card', $identifier );

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @param array<string, mixed> $instance Current settings.
     * @return string Default return value.
     */
    public function form( $instance ): string {
        $title      = $instance['title'] ?? '';
        $identifier = $instance['identifier'] ?? '';
        $max        = $instance['max_results'] ?? 3;

        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'shopsavvy' ); ?>
            </label>
            <input
                class="widefat"
                id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                type="text"
                value="<?php echo esc_attr( $title ); ?>"
            />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'identifier' ) ); ?>">
                <?php esc_html_e( 'Product Identifier (UPC, ASIN, URL, etc.):', 'shopsavvy' ); ?>
            </label>
            <input
                class="widefat"
                id="<?php echo esc_attr( $this->get_field_id( 'identifier' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'identifier' ) ); ?>"
                type="text"
                value="<?php echo esc_attr( $identifier ); ?>"
            />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'max_results' ) ); ?>">
                <?php esc_html_e( 'Max Results:', 'shopsavvy' ); ?>
            </label>
            <input
                class="small-text"
                id="<?php echo esc_attr( $this->get_field_id( 'max_results' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'max_results' ) ); ?>"
                type="number"
                min="1"
                max="20"
                value="<?php echo esc_attr( $max ); ?>"
            />
        </p>
        <?php

        return 'noform';
    }

    /**
     * Sanitize and save widget settings.
     *
     * @param array<string, mixed> $new_instance New settings.
     * @param array<string, mixed> $old_instance Previous settings.
     * @return array<string, mixed> Sanitized settings.
     */
    public function update( $new_instance, $old_instance ): array {
        return array(
            'title'      => sanitize_text_field( $new_instance['title'] ?? '' ),
            'identifier' => sanitize_text_field( $new_instance['identifier'] ?? '' ),
            'max_results' => absint( $new_instance['max_results'] ?? 3 ),
        );
    }
}
