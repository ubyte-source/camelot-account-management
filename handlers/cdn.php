<?PHP

namespace handlers;

use Knight\armor\Navigator;

$full_path = BASE_ROOT . substr($_SERVER[Navigator::REQUEST_URI], 5);
$full_path_extension = pathinfo($full_path, PATHINFO_EXTENSION);

switch ($full_path_extension) {
    case 'css':
        header("Content-type: text/css");
        break;
    case 'js':
        header("Content-type: text/javascript");
        break;
    default:
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $full_path_content_mimetype = finfo_file($finfo, $full_path);
        header('Content-type: ' . $full_path_content_mimetype);
        finfo_close($finfo);
}

include $full_path;
