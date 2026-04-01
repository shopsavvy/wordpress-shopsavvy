<?php
/**
 * Server-side render callback for the ShopSavvy Price Comparison block.
 *
 * This file is referenced by block.json's "render" field and is called
 * automatically by WordPress when rendering the block on the front end.
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Block inner content (empty for dynamic blocks).
 * @var WP_Block             $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$identifier  = ! empty( $attributes['identifier'] ) ? sanitize_text_field( $attributes['identifier'] ) : '';
$max_results = ! empty( $attributes['maxResults'] ) ? absint( $attributes['maxResults'] ) : 5;
$style       = ! empty( $attributes['style'] ) ? sanitize_text_field( $attributes['style'] ) : get_option( 'shopsavvy_default_style', 'table' );

if ( empty( $identifier ) ) {
    if ( current_user_can( 'edit_posts' ) ) {
        printf(
            '<p class="shopsavvy-error">%s</p>',
            esc_html__( 'ShopSavvy: No product identifier set for this block.', 'shopsavvy' )
        );
    }
    return;
}

$result = ShopSavvy_Client::get_offers( $identifier, $max_results );

if ( ! $result['success'] ) {
    if ( current_user_can( 'edit_posts' ) ) {
        printf( '<p class="shopsavvy-error">%s</p>', esc_html( $result['error'] ) );
    }
    return;
}

$wrapper_attributes = get_block_wrapper_attributes();

printf( '<div %s>', $wrapper_attributes );
echo ShopSavvy_Shortcode::render_offers( $result['data'], $style, $identifier );
echo '</div>';
