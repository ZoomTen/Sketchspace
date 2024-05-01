<?php declare(strict_types = 1);

namespace Sketchspace\Library;

use Sketchspace\Library\Util;
use Sketchspace\Exception\TooManyRequestsException;

/**
 * Inspired by Delight's implementation in PHP-Auth.
 *
 * Portions (c) delight-im
 */
class ThrottleManagement {
    private const SESSION_FILE = __DIR__ . "/../cache/.throttle.json";

    private static array $buckets;

    /**
     * Handle storage and expire old storage keys
     */
    private static function init(): void
    {
        // load array
        if (
            !file_exists(self::SESSION_FILE) ||
            !filesize(self::SESSION_FILE)
        ) {
            self::$buckets = [];
            return;
        }
        self::$buckets = json_decode(
            file_get_contents(self::SESSION_FILE),
        true
        );

        // check expiry date of throttle buckets
        foreach (self::$buckets as $key => $data) {
            if (isset($data['expires'])) {
                if (time() > $data['expires']) {
                    unset(self::$buckets[$key]);
                }
            }
        }

        file_put_contents(self::SESSION_FILE, json_encode(self::$buckets), LOCK_EX);
    }

    /**
     * A throttler to limit the use of a resource.
     *
     * *Note, resources don't mean ACTUAL resources in this function. Just identifiers.
     *
     * Apparently it uses something called the "inverse leaky bucket" algorithm.
     * Basically the premise is that you have several buckets for each
     * resource request.
     *
     * When the user makes a request, this function is called, letting
     * the bucket determine if the user has made too many requests.
     *
     * A bucket has a certain capacity of "tokens", which is taken from
     * every time a request is made. The cost of the request is usually
     * 1 "token", though this can be configured. It will throw an exception
     * if the user tries to take more than what is allotted.
     *
     * If I may use a more apt but slightly less tasteful analogy,
     * it's basically spoon theory.
     *
     * @param array $what_resource [description]
     * @param int $how_many [description]
     * @param int $interval [description]
     * @param integer $burstiness [description]
     * @param integer $cost [description]
     * @param boolean $simulated [description]
     * @return int                   [description]
     * @throws TooManyRequestsException if the user has exhausted the resource quota.
     * @author delight-im
     */
    public static function useResource(array $what_resource, int $how_many, int $interval, int $burstiness = 1, int $cost = 1, bool $simulated = false): int
    {
        self::init();

        /**
         * Ensures all of these operations
         * appear to be made as soon as requested
         */
        $now = time();

        /**
         * Generate a resource identifier.
         * Basically, the $what_resource values put together,
         * hashed and then base64'd.
         * It takes slightly less space than a regular md5 string.
         * Base85? i dunno, might probably trip some parsing errors.
         */
        $key = base64_encode(
            hash('md5', implode(' ', $what_resource), true)
        );

        // Determine how much should be in the resource bucket at a time
        $capacity = $burstiness * $how_many;

        // How much to fill the bucket with every second
        $fill_rate = (float) ($how_many / $interval);

        // Create the bucket if we don't have it
        if (!isset(self::$buckets[$key])) {
            self::$buckets[$key] = [];
        }

        /**
         * Get how many requests a user can take
         * if there's nothing here, set it to our maximum
         */
        $available =
            isset(self::$buckets[$key]['available'])
                ? (int) self::$buckets[$key]['available']
                : (int) $capacity;

        // When is the last time this bucket has been refilled?
        $last_refilled =
            isset(self::$buckets[$key]['lastRefreshed'])
                ? (int) self::$buckets[$key]['lastRefreshed']
                : $now;

        // Add tokens according to the calculated fill rate
        $seconds_from_last_refill = (int) max(0, $last_refilled-$now);
        $tokens_to_add = (int) ($seconds_from_last_refill * $fill_rate);

        // Update our token capacity
        $available = (int) min($capacity, $available + $tokens_to_add);
        $last_refilled = $now;

        $can_use = $available >= $cost;

        if (!$simulated) {
            if ($can_use) {
                /**
                 * We can use this resource, so take some away
                 * depending on the cost. This should never go under 0
                 */
                $available = max(0, $available - $cost);

                /**
                 * The bucket should be deleted after a certain amount of time
                 * has passed
                 */
                $expires_at = $now + floor($capacity / $fill_rate * 2);

                // fill in the actual stuff
                self::$buckets[$key]['available'] = $available;
                self::$buckets[$key]['lastRefreshed'] = $last_refilled;
                self::$buckets[$key]['expires'] = $expires_at;

                // We now try to write it into the bucket db
                file_put_contents(self::SESSION_FILE, json_encode(self::$buckets), LOCK_EX);
            }
        }

        // Return how many tokens the user has left
        if ($can_use) {
            return $available;
        }

        // Error out otherwise
        $time_needed = ceil(
            ($cost - $available) / $fill_rate
        );
        throw new TooManyRequestsException(
            'Please try again in ' . $time_needed . ' seconds'
        );

    }
}
