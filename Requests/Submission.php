<?php declare(strict_types = 1);

use Sketchspace\Library\Database;
use Sketchspace\Library\Authentication;
use Sketchspace\Object\Submission;
use Steampixel\Route;
use CodeShack\Template;

Route::add('/submission/([0-9]+)', function($submission_id)
{
    $q = Database::$db->prepare('
        select * from '.Database::getTable('Submission').'
        where id=?
    ');
    $q->execute([$submission_id]);

    $submission = Submission::fromStatement($q);

    if ($submission === false) {
        $messages = [
            ['error', 'Cannot find the specified submission.']
        ];

        Template::view('Views/_layout.html',[
            'messages' => $messages
        ]);
    } else {
        Template::view('Views/submission.html', [
            'logged_in_user' => Authentication::getCurrentUser(),
            'hide_login_form' => false,
            'date_added' => $submission->getAddedTimestamp()
        ]);
    }
}, 'get');
