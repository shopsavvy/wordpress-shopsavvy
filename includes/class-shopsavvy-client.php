<?php
/**
 * ShopSavvy API client.
 *
 * Handles all communication with the ShopSavvy Data API using
 * wp_remote_get / wp_remote_post and the site's configured API key.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Client {

    /**
     * Make a GET request to the ShopSavvy API.
     *
     * @param string               $endpoint API endpoint path (e.g. "/v1/offers").
     * @param array<string, mixed> $params   Query parameters.
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public static function get( string $endpoint, array $params = array() ): array {
        $api_key = get_option( 'shopsavvy_api_key', '' );

        if ( empty( $api_key ) ) {
            return array(
                'success' => false,
                'error'   => __( 'ShopSavvy API key is not configured. Go to Settings > ShopSavvy to add your key.', 'shopsavvy' ),
            );
        }

        // Check cache first.
        $cache_key = md5( $endpoint . wp_json_encode( $params ) );
        $cached    = ShopSavvy_Cache::get( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $url = SHOPSAVVY_API_BASE . $endpoint;

        if ( ! empty( $params ) ) {
            $url = add_query_arg( $params, $url );
        }

        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'User-Agent'    => 'ShopSavvy-WordPress/' . SHOPSAVVY_VERSION,
                'Accept'        => 'application/json',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );
        $data        = json_decode( $body, true );

        if ( $status_code < 200 || $status_code >= 300 ) {
            $error_message = isset( $data['error'] ) ? $data['error'] : sprintf(
                /* translators: %d: HTTP status code */
                __( 'API request failed with status %d.', 'shopsavvy' ),
                $status_code
            );

            return array(
                'success' => false,
                'error'   => $error_message,
            );
        }

        if ( null === $data ) {
            return array(
                'success' => false,
                'error'   => __( 'Invalid JSON response from ShopSavvy API.', 'shopsavvy' ),
            );
        }

        $result = array(
            'success' => true,
            'data'    => $data,
        );

        // Store in cache.
        ShopSavvy_Cache::set( $cache_key, $result );

        return $result;
    }

    /**
     * Look up offers for a product by identifier.
     *
     * The identifier can be a UPC/EAN barcode, ASIN, URL, model number, or MPN.
     *
     * @param string $identifier Product identifier.
     * @param int    $max        Maximum number of results.
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public static function get_offers( string $identifier, int $max = 5 ): array {
        return self::get( '/v1/offers', array(
            'id'  => $identifier,
            'max' => $max,
        ) );
    }

    /**
     * Get product details by identifier.
     *
     * @param string $identifier Product identifier.
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public static function get_details( string $identifier ): array {
        return self::get( '/v1/details', array(
            'id' => $identifier,
        ) );
    }

    /**
     * Search for products by query string.
     *
     * @param string $query  Search query.
     * @param int    $max    Maximum number of results.
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public static function search( string $query, int $max = 10 ): array {
        return self::get( '/v1/search', array(
            'q'   => $query,
            'max' => $max,
        ) );
    }

    /**
     * Get price history for a product.
     *
     * @param string $identifier Product identifier.
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public static function get_history( string $identifier ): array {
        return self::get( '/v1/history', array(
            'id' => $identifier,
        ) );
    }

    /**
     * Validate an API key by making a lightweight request.
     *
     * @param string $api_key API key to validate.
     * @return array{valid: bool, error?: string, credits_remaining?: int, credits_total?: int}
     */
    public static function validate_key( string $api_key ): array {
        $response = wp_remote_get( SHOPSAVVY_API_BASE . '/v1/account', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'User-Agent'    => 'ShopSavvy-WordPress/' . SHOPSAVVY_VERSION,
                'Accept'        => 'application/json',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'valid' => false,
                'error' => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if ( 401 === $status_code || 403 === $status_code ) {
            return array(
                'valid' => false,
                'error' => __( 'Invalid API key.', 'shopsavvy' ),
            );
        }

        if ( $status_code < 200 || $status_code >= 300 ) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    /* translators: %d: HTTP status code */
                    __( 'Validation request failed with status %d.', 'shopsavvy' ),
                    $status_code
                ),
            );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return array(
            'valid'             => true,
            'credits_remaining' => $body['credits_remaining'] ?? null,
            'credits_total'     => $body['credits_total'] ?? null,
        );
    }
}
