<?PHP

namespace handlers;

use Knight\armor\Output;
use Knight\armor\Navigator;
use Knight\armor\Language;
use Knight\armor\Request;

use Entity\Map as Entity;

const FOLDER = 'forms';

header("Content-type: application/json");
header('Access-Control-Allow-Origin: *');

Language::setSpeech(Request::get('language'));

$navigator = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$navigator = explode(chr(47), $navigator);
$navigator = array_filter($navigator);
$navigator = array_slice($navigator, 1);

$navigator_entity_name = array_pop($navigator);
if (null === $navigator_entity_name) Output::print(false);
if (2 > count($navigator)) array_push($navigator, $navigator_entity_name);

$navigator_entity_name = ucfirst($navigator_entity_name);
array_splice($navigator, Navigator::getDepth() - 1, 0, FOLDER);
array_push($navigator, $navigator_entity_name);

$navigator_entity = implode('\\', $navigator);
$navigator_entity = 'applications' . '\\' . $navigator_entity;
$navigator_entity = Entity::factory($navigator_entity);
$navigator_entity = $navigator_entity->human();

Output::concatenate(Output::APIDATA, $navigator_entity);
Output::print(true);
