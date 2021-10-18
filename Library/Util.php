<?php declare(strict_types = 1);

namespace Sketchspace\Library;

/**
 * Various functions to assist in other functions.
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
}
