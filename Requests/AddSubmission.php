<?php declare(strict_types = 1);

use Sketchspace\Library\Database;
use Sketchspace\Library\Authentication;
use Sketchspace\Library\ThrottleManagement;
use Sketchspace\Library\Util;
use Sketchspace\Library\Image;
use Sketchspace\Exception\TooManyRequestsException;
use Sketchspace\Object\Submission;
use Sketchspace\Object\Category;
use Sketchspace\Object\Subcategory;
use Sketchspace\Enum\Queries;
use Sketchspace\Enum\ResponseCode;
use Steampixel\Route;
use CodeShack\Template;
use Sketchspace\Exception\SubmissionException;

/**
 * Add submission page
 * Lets the user upload a submission.
 */
Route::add('/submit', function()
{
    if (empty(Authentication::getCurrentUser())) {
        http_response_code(ResponseCode::UNAUTHORIZED);
        $messages = [
            ['error', 'You must be authenticated to submit something.']
        ];
        Template::view('Views/_layout.html',[
            'logged_in_user' => Authentication::getCurrentUser(),
            'hide_login_form' => false,
            'messages' => $messages
        ]);
        return;
    }

    $successful = false;
    $messages = [];

    $file = null;
    $title = null;
    $subcategory = null;
    $description = null;
    $keywords = null;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        do {
            // first, check if the user has made many requests
            try {
                ThrottleManagement::useResource(
                    [ 'Submit', $_SERVER['REMOTE_ADDR'] ], // resource
                    1,                                      // one request
                    60*60,                                  // 1 hr interval
                    120                                      // 75 maximum
                );
            } catch (TooManyRequestsException $e) {
                http_response_code(ResponseCode::TOO_MANY_REQUESTS);
                array_push($messages, ['error', $e->getMessage()]);
                break;
            }

            // check empties

            if (empty(trim($_POST['t']))) {
                array_push($messages, ['error', 'Title required...']);
                break;
            }
            $title = $_POST['t'];
            
            if (!Util::presentInArray('cat', $_POST, true)) {
                array_push($messages, ['error', 'Set a category...']);
                break;
            }
            $subcategory = filter_var(trim($_POST['cat']), FILTER_VALIDATE_INT);

            $description = trim($_POST['d']);

            $keywords = trim($_POST['kw']);

            // file should be last in order to be able to fill in the other slots

            if ($_FILES['f']['error'] > 0) {
                array_push($messages, ['error',
                    [
                        'File size exceeds server limits!',
                        'File size exceeds limits!',
                        'File was partially uploaded...',
                        'File required...',
                        'Can\'t upload file...', // no tmp dir
                        'Can\'t upload file...', // not writeable
                        'Can\'t upload file...' // extension preventing
                    ]
                    [$_FILES['f']['error'] - 1]
                ]);
                break;
            }
            $file = $_FILES['f'];
            
            // anti-CSRF
            if (empty($_POST['anticsrf'])) {
                array_push($messages, ['error', 'No token']);
                break;
            }
            if ($_POST['anticsrf'] != $_SESSION['acsrf']) {
                array_push($messages, ['error', 'Token mismatch']);
                break;
            }

            // check MIME type
            $finfo = new finfo();
            $image_mime = $finfo->file($file['tmp_name'], FILEINFO_MIME_TYPE);

            if (!in_array($image_mime, SKETCHSPACE_SUPPORTED_MIMETYPES)) {
                array_push($messages, ['error', 'Supported filetypes: png, gif, jpeg, bmp']);
                break;
            }
            
            // upload the image
            $random = Util::generateRandomString(6);
            
            // ensure file extensions based on MIME type
            $ext = "unk";
            switch ($image_mime) {
                case 'image/png':
                    $ext = "png";
                    break;
                case 'image/gif':
                    $ext = "gif";
                    break;
                case 'image/jpeg':
                    $ext = "jpg";
                    break;
                case 'image/bmp':
                case 'image/x-ms-bmp':
                    $ext = "bmp";
                    break;
            }
            
            $name = basename($file['name'], '.'.$ext);
            
            $orgnl_filename = SKETCHSPACE_UPLOAD_DIR.'/'.$name.'-'.$random.'.'.$ext;
            $thumb_filename = SKETCHSPACE_THUMB_DIR.'/'.$name.'-'.$random.'.jpg';
            
            move_uploaded_file($file['tmp_name'], $orgnl_filename);
            Image::createThumbnail($orgnl_filename, $thumb_filename, 100);
            
            try {
                $submission = Submission::newSubmission($title, $orgnl_filename, $thumb_filename, $description);
                $submission->commitToDatabase();
                $submission->associateWithUser(Authentication::getCurrentUser());
                $submission->attachToSubcategory(Subcategory::fromId($subcategory));
            } catch (SubmissionException $e) {
                array_push($messages, ['error', 'Error: ' . $e->getMessage()]);
                break;
            }
            array_push($messages, ['success', 'Upload successful!']);
            $successful = true;
        } while (false);
    }

    if ($successful) {
        header("Location: /submission/".$submission->getId(), true, ResponseCode::FOUND);
    } else {
        $_SESSION['acsrf'] = bin2hex(random_bytes(32));

        $category_list = [];

        // get all categories
        $q = Database::$db->prepare('
            select parent_category_id from '.Database::$prefix.Queries::R_SUBCATEGORY_PARENT.'
            group by parent_category_id
        ');
        $q->execute();

        foreach ($q->fetchAll() as $c) {
            $cname = Category::fromId($c['parent_category_id'])->name;

            $category_list[$cname] = [];

            $q = Database::$db->prepare('
                select subcategory_id from '.Database::$prefix.Queries::R_SUBCATEGORY_PARENT.'
                where parent_category_id = :parent_category_id
                order by subcategory_id asc
            ');

            $q->execute(['parent_category_id' => $c['parent_category_id']]);

            foreach ($q->fetchAll() as $sc) {
                $fetched_sc = Subcategory::fromId($sc['subcategory_id']);
                array_push($category_list[$cname], $fetched_sc);
            }
        }

        Template::view('Views/add_submission.html',[
            'messages' => $messages,
            'logged_in_user' => Authentication::getCurrentUser(),
            'hide_login_form' => false,
            'categories' => $category_list,
            'form_save' => [
                'f'  => $file,
                't' => $title,
                'cat'  => $subcategory,
                'd'  => $description,
                'kw'  => $keywords,
            ],
            'acsrf' => $_SESSION['acsrf']
        ]);
    }
}, ['get', 'post']);
