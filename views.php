<?php declare(strict_types = 1);

use CodeShack\Template;
use Sketchspace\Library\Authentication;
use Steampixel\Route;
use Sketchspace\Enum\ResponseCode;

require_once 'Requests/Index.php';
require_once 'Requests/UserPage.php';
require_once 'Requests/Register.php';
require_once 'Requests/Login_Logout.php';
require_once 'Requests/Submission.php';

Route::pathNotFound(function()
{
    http_response_code(ResponseCode::NOT_FOUND);
    Template::view('Views/_layout.html',[
        'logged_in_user' => Authentication::getCurrentUser(),
        'hide_login_form' => false,
        'messages' => [['error', 'Invalid page...']]
    ]);
});

Route::methodNotAllowed(function()
{
    http_response_code(ResponseCode::METHOD_NOT_ALLOWED);
    Template::view('Views/_layout.html',[
        'logged_in_user' => Authentication::getCurrentUser(),
        'hide_login_form' => false,
        'messages' => [['error', 'Invalid access method']]
    ]);
});

Route::run('/');
