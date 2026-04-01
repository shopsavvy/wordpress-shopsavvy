<?php
/**
 * ShopSavvy transient cache wrapper.
 *
 * Uses WordPress transients to cache API responses and reduce
 * redundant requests to the ShopSavvy API.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Cache {

    /**
     * Transient key prefix.
     */
    private const PREFIX = 'shopsavvy_';

    /**
     * Get a cached value.
     *
     * @param string $key Cache key (will be prefixed automatically).
     * @return mixed|false Cached value or false if not found / expired.
     */
    public static function get( string $key ): mixed {
        return get_transient( self::build_key( $key ) );
    }

    /**
     * Set a cached value.
     *
     * @param string $key        Cache key.
     * @param mixed  $value      Value to cache.
     * @param int    $expiration Expiration in seconds. 0 = use site default.
     */
    public static function set( string $key, mixed $value, int $expiration = 0 ): void {
        if ( 0 === $expiration ) {
            $expiration = (int) get_option( 'shopsavvy_cache_duration', 3600 );
        }
        set_transient( self::build_key( $key ), $value, $expiration );
    }

    /**
     * Delete a specific cached value.
     *
     * @param string $key Cache key.
     */
    public static function delete( string $key ): void {
        delete_transient( self::build_key( $key ) );
    }

    /**
     * Flush all ShopSavvy transients.
     *
     * Uses a direct database query to find and delete all transients
     * that match the plugin prefix.
     */
    public static function flush_all(): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::PREFIX . '%',
                '_transient_timeout_' . self::PREFIX . '%'
            )
        );
    }

    /**
     * Build a full transient key from a short key.
     *
     * WordPress transient names are limited to 172 characters. We hash
     * long keys to stay within that limit.
     *
     * @param string $key Short cache key.
     * @return string Full transient key.
     */
    private static function build_key( string $key ): string {
        $full = self::PREFIX . $key;

        // WordPress transient name limit is 172 chars.
        if ( strlen( $full ) > 172 ) {
            $full = self::PREFIX . md5( $key );
        }

        return $full;
    }
}
