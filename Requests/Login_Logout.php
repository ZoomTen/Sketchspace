<?php declare(strict_types = 1);

use Sketchspace\Exception\MissingParametersException;
use Steampixel\Route;
use CodeShack\Template;

use Sketchspace\Enum\ResponseCode;
use Sketchspace\Library\Authentication;
use Sketchspace\Exception\InvalidParameterException;

/**
 * Login
 * This will only log the user in if this is accessed
 * through a POST request, provided the following
 * parameters are valid:
 *
 * u = username
 * p = plain text password
 */
Route::add('/login', function()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $messages = [];

        // check empties
        do {
            try {
                Authentication::signInUser($_POST['u'], $_POST['p']);

                header("Location: /", true, ResponseCode::FOUND);
                return;
            } catch (InvalidParameterException | MissingParametersException $e) {
                http_response_code(ResponseCode::UNAUTHORIZED);
                $messages = [
                    ['error', $e->getMessage()]
                ];
            }
        } while (false);

        Template::view('Views/_layout.html',[
            'messages' => $messages,
            'hide_login_form' => false
        ]);
    } else {
        header("Location: /", true, ResponseCode::FOUND);
        return;
    }
}, ['get', 'post']);

/**
 * Logout
 * This simply logs out the current user via HTTP.
 */
Route::add('/logout', function()
{
    Authentication::logOut();
    header("Location: /", true, ResponseCode::FOUND);
    return;
}, 'get');
