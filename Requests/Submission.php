<?php declare(strict_types = 1);

use CodeShack\Template;
use Sketchspace\Library\Authentication;
use Sketchspace\Object\Submission;
use Steampixel\Route;

/**
 * Submission page
 * Displays information about a single submission.
 */
Route::add('/submission/([0-9]+)', function($submission_id)
{
    $submission = Submission::fromId(filter_var($submission_id, FILTER_VALIDATE_INT));

    if ($submission === false) {
        $messages = [
            ['error', 'Cannot find the specified submission.']
        ];

        Template::view('Views/_layout.html',[
            'logged_in_user' => Authentication::getCurrentUser(),
            'hide_login_form' => false,
            'messages' => $messages
        ]);
    } else {
        Template::view('Views/submission.html', [
            'logged_in_user' => Authentication::getCurrentUser(),
            'hide_login_form' => false,
            'submission' => $submission,
        ]);
    }
}, 'get');