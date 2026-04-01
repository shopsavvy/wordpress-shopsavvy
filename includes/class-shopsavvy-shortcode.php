<?php
/**
 * ShopSavvy shortcode handler.
 *
 * Provides [shopsavvy_price] shortcode for embedding price comparisons
 * in posts, pages, and widget areas.
 *
 * Usage:
 *   [shopsavvy_price identifier="B08N5WRWNW"]
 *   [shopsavvy_price identifier="B08N5WRWNW" max="5" style="table"]
 *   [shopsavvy_price identifier="B08N5WRWNW" style="inline"]
 *   [shopsavvy_price identifier="B08N5WRWNW" style="card"]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Shortcode {

    /**
     * Hook into WordPress.
     */
    public static function init(): void {
        add_shortcode( 'shopsavvy_price', array( __CLASS__, 'render' ) );
    }

    /**
     * Render the shortcode.
     *
     * @param array<string, string>|string $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public static function render( array|string $atts ): string {
        $atts = shortcode_atts( array(
            'identifier' => '',
            'max'        => 5,
            'style'      => get_option( 'shopsavvy_default_style', 'table' ),
        ), $atts, 'shopsavvy_price' );

        $identifier = sanitize_text_field( $atts['identifier'] );
        $max        = absint( $atts['max'] );
        $style      = sanitize_text_field( $atts['style'] );

        if ( empty( $identifier ) ) {
            if ( current_user_can( 'edit_posts' ) ) {
                return '<p class="shopsavvy-error">' . esc_html__( 'ShopSavvy: No product identifier provided.', 'shopsavvy' ) . '</p>';
            }
            return '';
        }

        $result = ShopSavvy_Client::get_offers( $identifier, $max );

        if ( ! $result['success'] ) {
            if ( current_user_can( 'edit_posts' ) ) {
                return '<p class="shopsavvy-error">' . esc_html( $result['error'] ) . '</p>';
            }
            return '';
        }

        return self::render_offers( $result['data'], $style, $identifier );
    }

    /**
     * Render offer data in the requested style.
     *
     * @param array<string, mixed> $data       API response data.
     * @param string               $style      Display style: table, card, or inline.
     * @param string               $identifier Original product identifier.
     * @return string Rendered HTML.
     */
    public static function render_offers( array $data, string $style, string $identifier ): string {
        $offers  = $data['offers'] ?? array();
        $product = $data['product'] ?? array();

        if ( empty( $offers ) ) {
            return '<p class="shopsavvy-empty">' . esc_html__( 'No offers found for this product.', 'shopsavvy' ) . '</p>';
        }

        switch ( $style ) {
            case 'inline':
                return self::render_inline( $offers, $product );
            case 'card':
                return self::render_cards( $offers, $product );
            case 'table':
            default:
                return self::render_table( $offers, $product );
        }
    }

    /**
     * Render offers as a data table.
     *
     * @param array<int, array<string, mixed>> $offers  Offers array.
     * @param array<string, mixed>             $product Product info.
     * @return string HTML table.
     */
    private static function render_table( array $offers, array $product ): string {
        $product_name = $product['name'] ?? '';

        $html  = '<div class="shopsavvy-price-comparison shopsavvy-style-table">';

        if ( ! empty( $product_name ) ) {
            $html .= '<h3 class="shopsavvy-product-name">' . esc_html( $product_name ) . '</h3>';
        }

        $html .= '<table class="shopsavvy-table">';
        $html .= '<thead><tr>';
        $html .= '<th>' . esc_html__( 'Retailer', 'shopsavvy' ) . '</th>';
        $html .= '<th>' . esc_html__( 'Price', 'shopsavvy' ) . '</th>';
        $html .= '<th>' . esc_html__( 'Condition', 'shopsavvy' ) . '</th>';
        $html .= '<th></th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        foreach ( $offers as $offer ) {
            $retailer  = $offer['retailer_name'] ?? __( 'Unknown', 'shopsavvy' );
            $price     = $offer['price'] ?? null;
            $currency  = $offer['currency'] ?? 'USD';
            $condition = $offer['condition'] ?? '';
            $url       = $offer['url'] ?? '#';

            $html .= '<tr>';
            $html .= '<td class="shopsavvy-retailer">' . esc_html( $retailer ) . '</td>';
            $html .= '<td class="shopsavvy-price">' . esc_html( self::format_price( $price, $currency ) ) . '</td>';
            $html .= '<td class="shopsavvy-condition">' . esc_html( ucfirst( $condition ) ) . '</td>';
            $html .= '<td class="shopsavvy-action"><a href="' . esc_url( $url ) . '" target="_blank" rel="nofollow noopener">' . esc_html__( 'View', 'shopsavvy' ) . '</a></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= self::render_attribution();
        $html .= '</div>';

        return $html;
    }

    /**
     * Render offers as cards.
     *
     * @param array<int, array<string, mixed>> $offers  Offers array.
     * @param array<string, mixed>             $product Product info.
     * @return string HTML cards.
     */
    private static function render_cards( array $offers, array $product ): string {
        $product_name = $product['name'] ?? '';

        $html = '<div class="shopsavvy-price-comparison shopsavvy-style-card">';

        if ( ! empty( $product_name ) ) {
            $html .= '<h3 class="shopsavvy-product-name">' . esc_html( $product_name ) . '</h3>';
        }

        $html .= '<div class="shopsavvy-cards">';

        foreach ( $offers as $index => $offer ) {
            $retailer  = $offer['retailer_name'] ?? __( 'Unknown', 'shopsavvy' );
            $price     = $offer['price'] ?? null;
            $currency  = $offer['currency'] ?? 'USD';
            $condition = $offer['condition'] ?? '';
            $url       = $offer['url'] ?? '#';

            $best_class = 0 === $index ? ' shopsavvy-card-best' : '';

            $html .= '<div class="shopsavvy-card' . $best_class . '">';
            if ( 0 === $index ) {
                $html .= '<span class="shopsavvy-badge">' . esc_html__( 'Best Price', 'shopsavvy' ) . '</span>';
            }
            $html .= '<div class="shopsavvy-card-price">' . esc_html( self::format_price( $price, $currency ) ) . '</div>';
            $html .= '<div class="shopsavvy-card-retailer">' . esc_html( $retailer ) . '</div>';
            if ( ! empty( $condition ) ) {
                $html .= '<div class="shopsavvy-card-condition">' . esc_html( ucfirst( $condition ) ) . '</div>';
            }
            $html .= '<a class="shopsavvy-card-link" href="' . esc_url( $url ) . '" target="_blank" rel="nofollow noopener">' . esc_html__( 'View Deal', 'shopsavvy' ) . '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= self::render_attribution();
        $html .= '</div>';

        return $html;
    }

    /**
     * Render the lowest-price offer inline.
     *
     * @param array<int, array<string, mixed>> $offers  Offers array.
     * @param array<string, mixed>             $product Product info.
     * @return string Inline HTML.
     */
    private static function render_inline( array $offers, array $product ): string {
        $best     = $offers[0] ?? null;

        if ( null === $best ) {
            return '';
        }

        $price    = $best['price'] ?? null;
        $currency = $best['currency'] ?? 'USD';
        $retailer = $best['retailer_name'] ?? __( 'a retailer', 'shopsavvy' );
        $url      = $best['url'] ?? '#';

        return sprintf(
            '<span class="shopsavvy-price-comparison shopsavvy-style-inline">' .
            /* translators: 1: formatted price, 2: retailer link start, 3: retailer name, 4: retailer link end */
            __( 'From %1$s at %2$s%3$s%4$s', 'shopsavvy' ) .
            '</span>',
            '<strong>' . esc_html( self::format_price( $price, $currency ) ) . '</strong>',
            '<a href="' . esc_url( $url ) . '" target="_blank" rel="nofollow noopener">',
            esc_html( $retailer ),
            '</a>'
        );
    }

    /**
     * Format a price for display.
     *
     * @param float|int|string|null $price    Price value.
     * @param string                $currency ISO 4217 currency code.
     * @return string Formatted price string.
     */
    private static function format_price( float|int|string|null $price, string $currency = 'USD' ): string {
        if ( null === $price || '' === $price ) {
            return __( 'N/A', 'shopsavvy' );
        }

        $price = (float) $price;

        // Use NumberFormatter if the intl extension is available.
        if ( class_exists( 'NumberFormatter' ) ) {
            $locale    = get_locale();
            $formatter = new NumberFormatter( $locale, NumberFormatter::CURRENCY );
            $formatted = $formatter->formatCurrency( $price, $currency );

            if ( false !== $formatted ) {
                return $formatted;
            }
        }

        // Fallback to basic formatting.
        $symbols = array(
            'USD' => '$',
            'EUR' => "\u{20AC}",
            'GBP' => "\u{00A3}",
            'JPY' => "\u{00A5}",
            'CAD' => 'C$',
            'AUD' => 'A$',
        );

        $symbol = $symbols[ $currency ] ?? $currency . ' ';

        return $symbol . number_format( $price, 2 );
    }

    /**
     * Render the ShopSavvy attribution footer.
     *
     * @return string HTML attribution.
     */
    private static function render_attribution(): string {
        return '<p class="shopsavvy-attribution">' .
            sprintf(
                /* translators: %s: ShopSavvy link */
                esc_html__( 'Prices powered by %s', 'shopsavvy' ),
                '<a href="https://shopsavvy.com" target="_blank" rel="noopener">ShopSavvy</a>'
            ) .
            '</p>';
    }
}
