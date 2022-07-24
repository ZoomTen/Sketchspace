<?php declare(strict_types = 1);

use CodeShack\Template;
use Sketchspace\Library\Authentication;
use Steampixel\Route;

/**
 * Submissions list
 */
Route::add('/submissions/(latest|all)', function($name)
{
    print_r($name);
    Template::view('Views/latest_submissions.html', [
        'logged_in_user' => Authentication::getCurrentUser(),
        'hide_login_form' => false
    ]);
}, 'get');
