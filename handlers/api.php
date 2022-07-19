<?PHP

namespace handlers;

use IAM\Sso;

use Knight\armor\Output;
use Knight\armor\Language;
use Knight\armor\Navigator;

Navigator::noCache();

header("Content-type: application/json");
header('Access-Control-Allow-Origin: *');

$uri = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$uri = explode(chr(47), trim($uri, chr(47)));
$uri = array_filter($uri, 'strlen');
$uri = array_values($uri);
$uri_route = array_slice($uri, 1, Navigator::getDepth());

if (reset($uri_route) === Sso::AUTHORIZATION) Sso::auth();
if (count($uri_route) !== Navigator::getDepth()) Output::print(false);

array_splice($uri_route, Navigator::getDepth() - 1, 0, 'actions');

$action_path = implode(DIRECTORY_SEPARATOR, $uri_route);
$action_path = BASE_ROOT . 'applications' . DIRECTORY_SEPARATOR . $action_path;
if (file_exists($action_path . chr(46) . 'php')) include $action_path . chr(46) . 'php';

Language::dictionary(__file__);
$notice = __namespace__ . '\\' . 'notice';
$notice = Language::translate($notice);
Output::concatenate('notice', $notice);
Output::print(false);
