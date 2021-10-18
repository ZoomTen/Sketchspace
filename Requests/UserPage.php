<?php declare(strict_types = 1);

use Sketchspace\Library\Authentication;
use Steampixel\Route;
use CodeShack\Template;

use Sketchspace\Library\Util;
use Sketchspace\Library\Database;
use Sketchspace\Object\User;

Route::add('/users', function ()
{
    $users_list = [];

    $q = Database::$db->query('
        select * from '.Database::getTable('User').'
    ');
    while ($u = User::fromStatement($q)) {
        array_push($users_list, $u);
    }

    Template::view('Views/user_list.html',[
        'logged_in_user' => Authentication::getCurrentUser(),
        'user_list' => $users_list
    ]);
}, 'get');

Route::add('/user/([0-9A-z]+)', function($username)
{
    $q = Database::$db->prepare('
        select * from '.Database::getTable('User').'
        where username=?
    ');
    $q->execute([$username]);
    $u = User::fromStatement($q);

    if ($u === false) {
        $messages = [
            [
                'error',
                'Cannot find user "'.Util::sanitize($username).'"'
            ]
        ];

        Template::view('Views/_layout.html',[
            'messages' => $messages
        ]);
    } else {
        Template::view('Views/user.html',[
            'logged_in_user' => Authentication::getCurrentUser(),
            'display_user' => $u
        ]);
    }
}, 'get');
