<?php declare(strict_types = 1);

use CodeShack\Template;
use Sketchspace\Library\Authentication;
use Steampixel\Route;
use Sketchspace\Library\Database;
use Sketchspace\Library\Util;
use Sketchspace\Object\Submission;

/**
 * Submissions list
 */
Route::add('/submissions/latest', function()
{
    $get = Util::getParams();
    
    $after = (!Util::presentInArray('after', $get))
        ? PHP_INT_MAX
        : filter_var($get['after'], FILTER_SANITIZE_NUMBER_INT);
    
    $limit = (!Util::presentInArray('limit', $get))
        ? 20
        : filter_var($get['limit'], FILTER_SANITIZE_NUMBER_INT);
    
    $main_query = '
       select * from '.Database::getTable('Submission').'
       where add_timestamp < :after
       order by add_timestamp desc
       limit :limit
    '; // to be used in a subquery
    
    $q = Database::$db->prepare($main_query);
    
    $q->bindParam('after', $after, PDO::PARAM_INT);
    $q->bindParam('limit', $limit, PDO::PARAM_INT);
    $q->execute();
    
    $submissions = [];
    
    // timestamp of first and last entries in the page
    $first_timestamp = null;
    $last_timestamp = null;
    
    // timestamps for cursor pagination
    $prev_page_timestamp = null;
    $next_page_timestamp = null;
    
    while ($sub = Submission::fromStatement($q)) {
        if (empty($first_timestamp)) {
            $first_timestamp = $sub->getRawTimestamp();
        }
        array_push($submissions, $sub);
        $last_timestamp = $sub->getRawTimestamp();
    }
    
    // determine if next button should be clickable
    $q = Database::$db->prepare('
        select count(*) from ('.$main_query.') as _
    ');
    $q->bindParam('after', $last_timestamp, PDO::PARAM_INT);
    $q->bindParam('limit', $limit, PDO::PARAM_INT);
    $q->execute();
    
    $next_page_timestamp = $last_timestamp;
    if ($q->fetchColumn(0) < 1) {
        $next_page_timestamp = null; // can't go to next page
    }
    
    // determine link of previous button
    $q = Database::$db->prepare('
        select add_timestamp from (
            select * from '.Database::getTable('Submission').'
            where add_timestamp > :before
            order by add_timestamp asc
            limit :limit
        ) as _
        order by add_timestamp desc
        limit 1
    ');
    
    $q->bindParam('before', $first_timestamp, PDO::PARAM_INT);
    $q->bindParam('limit', $limit, PDO::PARAM_INT);
    $q->execute();
    
    while ($got_query = $q->fetchColumn(0)) {
        $prev_page_timestamp = $got_query + 1;
    }
    
    Template::view('Views/latest_submissions.html', [
        'logged_in_user' => Authentication::getCurrentUser(),
        'hide_login_form' => false,
        'submissions' => $submissions,
        'first_timestamp' => $prev_page_timestamp,
        'last_timestamp' => $next_page_timestamp,
        'limit' => $limit
    ]);
}, 'get');
