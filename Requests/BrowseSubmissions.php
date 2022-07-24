<?php declare(strict_types = 1);

use CodeShack\Template;
use Sketchspace\Library\Authentication;
use Steampixel\Route;
use Sketchspace\Library\Database;
use Sketchspace\Library\Util;

/**
 * Submissions list
 */
Route::add('/submissions/all', function()
{
    $get = Util::getParams();
    
    $after = (!Util::presentInArray('by', $get))
        ? 0
        : filter_var($get['after'], FILTER_SANITIZE_NUMBER_INT);
    
    $by = (!Util::presentInArray('by', $get))
        ? 'date'
        : filter_var($get['by'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    $limit = (!Util::presentInArray('limit', $get))
        ? 20
        : filter_var($get['limit'], FILTER_SANITIZE_NUMBER_INT);

    $map_order_by = [
        'date'    => 'add_timestamp',
        'subject' => 'subject',  
    ];
    
    if (Util::presentInArray($by, $map_order_by, true)) {
        $table_by_map = $map_order_by[trim($by)];
    } else {
        $table_by_map = 'add_timestamp';
        $after = 0;
    }
    
    $query = 'select * from '.$table_by_map.' where add_timestamp < ? order by '.$table_by_map.' desc';
    
    print_r($after);
    echo "\n";
    print_r($by);
    echo "\n";
    print_r($limit);
    echo "\n";
    print_r($query);
    echo "\n";
//     $q = Database::$db->prepare('
        
//     ');
    Template::view('Views/latest_submissions.html', [
        'logged_in_user' => Authentication::getCurrentUser(),
        'hide_login_form' => false
    ]);
}, 'get');
