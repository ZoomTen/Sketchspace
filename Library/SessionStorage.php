<?php declare(strict_types = 1);

namespace Sketchspace\Library;

use Exception;
use Sketchspace\Library\Util;

/**
 * File-based server state storage, for hashes and idempotency and the like.
 */
class SessionStorage {
    private const SESSION_FILE = ".sessions.json";

    private static array $keys;

    /**
     * Handle storage and expire old storage keys
     * @throws Exception
     */
    private static function init(): void
    {
        // load array
        if (
            !file_exists(self::SESSION_FILE) ||
            !filesize(self::SESSION_FILE)
        ) {
            self::$keys = [];
            return;
        }
        self::$keys = json_decode(
            file_get_contents(self::SESSION_FILE),
        true
        );

        // check expiry date of storage tokens
        foreach (self::$keys as $category => $hash) {
            if (is_array($hash)) {
                foreach ($hash as $key => $data) {
                    if (time() > $data['expires']) {
                        unset(self::$keys[$category][$key]);
                    }
                }
            }
        }

        file_put_contents(self::SESSION_FILE, json_encode(self::$keys), LOCK_EX);
    }

    // Create token, default to 1 hour

    /**
     * @throws Exception
     */
    public static function useKey(string $key, string $category = 'token', int $ttl = 60*60): bool
    {
        self::init();

        if (!isset(self::$keys[$category])) {
            self::$keys[$category] = [];
        }

        if (!isset(self::$keys[$category][$key])){
            self::$keys[$category][$key] = ['expires' => time() + $ttl];

            file_put_contents(self::SESSION_FILE, json_encode(self::$keys), LOCK_EX);
            return true;
        }
        return false;
    }

    public static function useKeyFromHttp(int $ttl = 60*60): bool
    {
        $headers = Util::getHttpHeaders();

        // Detect optional idempotency token
        $idempotency =
            key_exists('Idempotency-Token', $headers)
                ? $headers['Idempotency-Token']         // key specified
                : '';                                   // key unspecified

        if (!empty($idempotency)) {
            return self::useKey('idempotency', $idempotency, $ttl);
        }

        return true;
    }
}
