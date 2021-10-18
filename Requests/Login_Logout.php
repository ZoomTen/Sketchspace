<?php declare(strict_types = 1);

use Sketchspace\Exception\MissingParametersException;
use Steampixel\Route;
use CodeShack\Template;

use Sketchspace\Library\ResponseCode;
use Sketchspace\Library\Authentication;
use Sketchspace\Exception\InvalidParameterException;

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

Route::add('/logout', function()
{
    Authentication::logOut();
    header("Location: /", true, ResponseCode::FOUND);
    return;
}, 'get');
