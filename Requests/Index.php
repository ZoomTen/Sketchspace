<?php declare(strict_types = 1);

use Sketchspace\Library\Authentication;
use Steampixel\Route;
use CodeShack\Template;

/**
 * Main web page route
 */
Route::add('/', function()
{
    Template::view('Views/index.html', [
        'logged_in_user' => Authentication::getCurrentUser(),
        'hide_login_form' => false
    ]);
}, 'get');
