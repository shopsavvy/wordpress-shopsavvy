<?php
/**
 * ShopSavvy REST API endpoint.
 *
 * Registers wp-json/shopsavvy/v1/search with nonce verification,
 * IP-based rate limiting (10 req/min), and ShopSavvy API passthrough.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_REST {

    /**
     * Rate limit: maximum requests per window.
     */
    private const RATE_LIMIT_MAX = 10;

    /**
     * Rate limit window in seconds.
     */
    private const RATE_LIMIT_WINDOW = 60;

    /**
     * Hook into WordPress.
     */
    public static function init(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    /**
     * Register REST routes.
     */
    public static function register_routes(): void {
        register_rest_route( 'shopsavvy/v1', '/search', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'handle_search' ),
            'permission_callback' => array( __CLASS__, 'check_permissions' ),
            'args'                => array(
                'q'    => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => __( 'Search query.', 'shopsavvy' ),
                ),
                'id'   => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => __( 'Product identifier (UPC, ASIN, URL, model number, MPN).', 'shopsavvy' ),
                ),
                'type' => array(
                    'type'              => 'string',
                    'default'           => 'offers',
                    'enum'              => array( 'offers', 'details', 'search', 'history' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => __( 'Request type.', 'shopsavvy' ),
                ),
                'max'  => array(
                    'type'              => 'integer',
                    'default'           => 5,
                    'minimum'           => 1,
                    'maximum'           => 50,
                    'sanitize_callback' => 'absint',
                    'description'       => __( 'Maximum results to return.', 'shopsavvy' ),
                ),
            ),
        ) );
    }

    /**
     * Permission callback.
     *
     * Checks that the REST endpoint is enabled and that the request
     * passes rate limiting and nonce verification (when available).
     *
     * @param WP_REST_Request $request Current request.
     * @return bool|WP_Error True if permitted, WP_Error otherwise.
     */
    public static function check_permissions( WP_REST_Request $request ): bool|WP_Error {
        // Check if public REST is enabled.
        if ( '1' !== get_option( 'shopsavvy_rest_enabled', '0' ) ) {
            return new WP_Error(
                'shopsavvy_rest_disabled',
                __( 'The ShopSavvy REST endpoint is not enabled.', 'shopsavvy' ),
                array( 'status' => 403 )
            );
        }

        // Verify nonce if provided (logged-in users via wp_create_nonce).
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error(
                'shopsavvy_invalid_nonce',
                __( 'Invalid nonce.', 'shopsavvy' ),
                array( 'status' => 403 )
            );
        }

        // Rate limiting.
        $ip = self::get_client_ip();
        if ( self::is_rate_limited( $ip ) ) {
            return new WP_Error(
                'shopsavvy_rate_limited',
                __( 'Rate limit exceeded. Please try again later.', 'shopsavvy' ),
                array( 'status' => 429 )
            );
        }

        return true;
    }

    /**
     * Handle the search request.
     *
     * @param WP_REST_Request $request Current request.
     * @return WP_REST_Response|WP_Error Response or error.
     */
    public static function handle_search( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $type = $request->get_param( 'type' );
        $id   = $request->get_param( 'id' );
        $q    = $request->get_param( 'q' );
        $max  = $request->get_param( 'max' );

        // Validate that we have either an identifier or a search query.
        if ( 'search' === $type ) {
            if ( empty( $q ) ) {
                return new WP_Error(
                    'shopsavvy_missing_query',
                    __( 'A search query (q) is required for search requests.', 'shopsavvy' ),
                    array( 'status' => 400 )
                );
            }
        } else {
            if ( empty( $id ) ) {
                return new WP_Error(
                    'shopsavvy_missing_id',
                    __( 'A product identifier (id) is required.', 'shopsavvy' ),
                    array( 'status' => 400 )
                );
            }
        }

        // Dispatch to the appropriate client method.
        $result = match ( $type ) {
            'details' => ShopSavvy_Client::get_details( $id ),
            'search'  => ShopSavvy_Client::search( $q, $max ),
            'history' => ShopSavvy_Client::get_history( $id ),
            default   => ShopSavvy_Client::get_offers( $id, $max ),
        };

        if ( ! $result['success'] ) {
            return new WP_Error(
                'shopsavvy_api_error',
                $result['error'],
                array( 'status' => 502 )
            );
        }

        return new WP_REST_Response( $result['data'], 200 );
    }

    /**
     * Check if an IP is rate limited and record the request.
     *
     * @param string $ip Client IP address.
     * @return bool True if rate limited.
     */
    private static function is_rate_limited( string $ip ): bool {
        $key     = 'shopsavvy_rl_' . md5( $ip );
        $current = get_transient( $key );

        if ( false === $current ) {
            set_transient( $key, 1, self::RATE_LIMIT_WINDOW );
            return false;
        }

        $count = (int) $current;

        if ( $count >= self::RATE_LIMIT_MAX ) {
            return true;
        }

        set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
        return false;
    }

    /**
     * Get the client IP address, respecting common proxy headers.
     *
     * @return string IP address.
     */
    private static function get_client_ip(): string {
        $headers = array(
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                // X-Forwarded-For may contain multiple IPs; use the first.
                $ip = strtok( $_SERVER[ $header ], ',' );
                $ip = trim( $ip );

                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }
}
