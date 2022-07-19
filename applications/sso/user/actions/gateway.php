<?PHP

namespace applications\sso\user\actions;

use IAM\Gateway;

use Knight\armor\Output;
use Knight\armor\Request;
use Knight\armor\Navigator;

const STRUCTURE = 'structure';

Navigator::noCache();

Output::setEncodeOptionOverride(JSON_UNESCAPED_SLASHES);

$uri = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$uri = trim($uri, chr(47));
$uri = explode(chr(47), $uri);
$uri = array_filter($uri, 'strlen');
$uri = array_values($uri);
$uri = array_slice($uri, 1 + Navigator::getDepth());

$uri_application = array_shift($uri);
$uri = implode(chr(47), $uri);
$uri = $uri . chr(63) . http_build_query((array)Request::get());

$uri_response = STRUCTURE === Request::get('type')
    ? Gateway::getStructure($uri_application, $uri)
    : Gateway::callAPI($uri_application, $uri, (array)Request::post());

$uri_response = Output::json($uri_response);

exit($uri_response);
