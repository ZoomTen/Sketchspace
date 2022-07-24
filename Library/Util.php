<?php declare(strict_types = 1);

namespace Sketchspace\Library;

/**
 * Various functions to assist in other functions.
 *
 * This does not depend on anything else.
 */
class Util
{
    /**
     * Alias to quickly sanitize strings, helps prevent cross-site scripting.
     *
     * Example: "<hello>>hello" -> "&gt;hello"
     * @param $string String to sanitize
     * @return string Sanitized string
     */
    public static function sanitize(string $string): string
    {
        return htmlspecialchars(strip_tags($string));
    }

    /**
     * Because PHP does not reliably return $_GET as expected,
     * this function is needed to extract HTTP GET parameters from
     * the URL.
     *
     * @return array GET params as an array
     */
    public static function getParams(): array
    {
        /* A hack to read GET params manually
        */
        preg_match_all('/[?&]([^=]+)=([^&]+)/', $_SERVER['REQUEST_URI'], $co, PREG_SET_ORDER);
        $get = [];
        foreach ($co as $param) {
            $param_name = self::sanitize($param[1]);
            $param_value = self::sanitize($param[2]);
            $get[$param_name] = $param_value;
        }
        return $get;
    }

    /**
     * Shortcut to print out a JSON response
     * @param array $response PHP array
     */
    public static function jsonResponse(array $response)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    /**
     * Extract HTTP headers
     * @return array
     */
    public static function getHttpHeaders(): array
    {
        $formatted_headers = [];
        foreach ($_SERVER as $header => $value){
            if (str_starts_with($header, 'HTTP_')) {
                $header = substr($header, 5);

                $header_words = explode('_', $header);
                for ($i = 0; $i < count($header_words); $i++) {
                    $header_words[$i] = ucfirst(strtolower($header_words[$i]));
                }

                $header = implode('-', $header_words);

                $formatted_headers[$header] = $value;
            }
        }
        return $formatted_headers;
    }
    
    /**
     * Generate a random alphanumeric string
     * 
     * @param int $length
     * @return string|mixed
     */
    public static function generateRandomString(int $length) {
        $key = '';
        $keys = array_merge(range(0, 9), range('a', 'z'));
        
        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }
        
        return $key;
    }
    
    /**
     * Determines if a key is a present in an array and is not empty.
     * 
     * @param string $key
     * @param array $array
     * @param string $trim Whether or not to trim the contents of the key
     * @return boolean
     */
    public static function presentInArray(string $key, array $array, bool $trim=false): bool {
        if ($trim) {
            return array_key_exists($key, $array) && !empty(trim($array[$key]));
        }
        return array_key_exists($key, $array) && !empty($array[$key]);
    }
}
