<?PHP

namespace applications\customer\server\actions;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Request;
use Knight\armor\Navigator;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\Statement;
use ArangoDB\operations\common\Choose;
use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;

use applications\sso\user\database\Vertex as User;
use applications\sso\user\database\edges\UserToContact;
use applications\customer\contact\database\Vertex as Contact;
use applications\customer\contact\database\edges\ContactToContact;

const CARDINAL_POLYGON = '[[[north, west], [north, east], [south, east], [south, west], [north, west]]]';
const CARDINAL = [
    'north',
    'east',
    'south',
    'west'
];

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/read')) Output::print(false);

$contact_field_key_value = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$contact_field_key_value = basename($contact_field_key_value);

$post = Request::post();
$post = array_filter((array)$post, function ($item) {
    return !is_string($item) && !is_numeric($item) || strlen((string)$item);
});

$hierarchy_query = User::getHierarchy(User::INCLUDEME, User::QUERYMODE);
$hierarchy_query = ArangoDB::start(...$hierarchy_query);
$hierarchy_query_to_contact = $hierarchy_query->begin();
$hierarchy_query_to_contact = $hierarchy_query_to_contact->useEdge(UserToContact::getName());

if ($contact_field_key_value !== pathinfo(__file__, PATHINFO_FILENAME)) {
    $contact = $hierarchy_query_to_contact->vertex();
    $contact->getField(Arango::KEY)->setSafeModeDetached(false)->setValue($contact_field_key_value);
    $hierarchy_query_to_contact = $contact->useEdge(ContactToContact::getName())->setForceDirection(Edge::OUTBOUND);
    $hierarchy_query_to_contact->branch()->vertex()->useEdge(ContactToContact::getName())->setForceDirection(Edge::OUTBOUND)->setTo($hierarchy_query_to_contact->vertex());
}

$contact = $hierarchy_query_to_contact->vertex();
$contact->setSafeMode(false);
$contact_fields = $contact->getFields();
foreach ($contact_fields as $field) {
    $field_name = $field->getName();
    if (false === array_key_exists($field_name, $post)
        || $field->getProtected()) continue;

    $contact->getField($field_name)->setValue($post[$field_name]);
}

if ($contact_field_key_value === pathinfo(__file__, PATHINFO_FILENAME) && !Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/read/all')) {
    $hierarchy_query_empty = $hierarchy_query_to_contact->getFrom();
    $hierarchy_query_empty->reset();
    $hierarchy_query_empty->getField(Arango::ID)->setProtected(true);
    $hierarchy_query = ArangoDB::start($hierarchy_query_empty);
}

$hierarchy_query_select = $hierarchy_query->select();

$or = Request::get('force-use-or');
$or = filter_var($or, FILTER_VALIDATE_BOOLEAN);
if (true === $or && false === Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/read/or')) $hierarchy_query_select->pushEntitiesUsingOr($contact);

if (!!$count_offset = Request::get('offset')) $hierarchy_query_select->getLimit()->setOffset($count_offset);
if (!!$count = Request::get('count')) $hierarchy_query_select->getLimit()->set($count);

$hierarchy_query_select_vertex = $hierarchy_query_select->getPointer(Choose::VERTEX);
$hierarchy_query_select_return_statement = new Statement();

if (!!$polygon = Request::post('polygon')) {
    $polygon = array_map(function (string $value) {
        return (float)$value;
    }, $polygon);
    $hierarchy_query_select_return_statement_polygon = array_fill_keys(CARDINAL, 0);
    $hierarchy_query_select_return_statement_polygon = array_replace($hierarchy_query_select_return_statement_polygon, $polygon);
    $hierarchy_query_select_return_statement_polygon_bound = $hierarchy_query_select_return_statement->bind($hierarchy_query_select_return_statement_polygon, true);
    $hierarchy_query_select_return_statement_polygon = str_replace(CARDINAL, array_values($hierarchy_query_select_return_statement_polygon_bound), CARDINAL_POLYGON);

    $contact_position = $contact->getField('contact_position')->getName();
    $contact_position = $hierarchy_query_select_vertex . chr(46) . $contact_position;

    $hierarchy_query_select_return_statement->append('FILTER');
    $hierarchy_query_select_return_statement->append('GEO_CONTAINS' . chr(40), false);
    $hierarchy_query_select_return_statement->append('GEO_POLYGON' . chr(40) . $hierarchy_query_select_return_statement_polygon . chr(41) . chr(44));
    $hierarchy_query_select_return_statement->append($contact_position, false);
    $hierarchy_query_select_return_statement->append(chr(41));
}

$contact_unique = $contact->getAllFieldsUniqueGroups();
foreach ($contact_unique as $group) if (1 === count($group)) {
    $name = reset($group);
    $keys = Request::post($name);
    if (null === $keys
        || false === is_array($keys)
        || 0 === count($keys)) continue;

    $keys = array_values($keys);
    $keys_bound = $hierarchy_query_select_return_statement->bound(...$keys);
    $keys_bound = implode(chr(44) . chr(32), $keys_bound);

    $hierarchy_query_select_return_statement->append('FILTER');
    $hierarchy_query_select_return_statement->append('POSITION' . chr(40) . '[' . $keys_bound . ']' . chr(44) . chr(32) . $hierarchy_query_select_vertex . chr(46) . $name . chr(41));
    $hierarchy_query_select->getLimit()->set(count($keys));
}

$hierarchy_query_select_return_statement_key = $contact->getField(Arango::KEY)->getName();
$hierarchy_query_select_return_statement->append('FILTER' . chr(32) . $hierarchy_query_select_vertex . chr(46) . $hierarchy_query_select_return_statement_key . chr(32) . '!= $0');
$hierarchy_query_select_return_statement->append('COLLECT');
$hierarchy_query_select_return_statement->append('contact');
$hierarchy_query_select_return_statement->append('=');
$hierarchy_query_select_return_statement->append($hierarchy_query_select_vertex);
$hierarchy_query_select_return_statement->append('RETURN');
$hierarchy_query_select_return_statement->append('DISTINCT');
$hierarchy_query_select_return_statement->append('contact');
$hierarchy_query_select->getReturn()->setFromStatement($hierarchy_query_select_return_statement, $contact_field_key_value);
$hierarchy_query_select_response = $hierarchy_query_select->run();
if (null === $hierarchy_query_select_response) Output::print(false);

if (!!$only = Request::get('only')) if (is_string($only)) {
    $only_fields = explode(chr(44), $only);
    $only_fields = array_fill_keys($only_fields, null);
    array_walk($hierarchy_query_select_response, function (array &$value) use ($only_fields) {
        $value = array_intersect_key($value, $only_fields);
    });
}

$contact = new Contact();
$contact->setSafeMode(false)->setReadMode(true);

array_walk($hierarchy_query_select_response, function (&$value) use ($contact, $only) {
    $clone = clone $contact;
    $clone->setFromAssociative($value);
    $value = $clone->getAllFieldsValues(!!$only, false);
});

Output::setEncodeOptionOverride(JSON_UNESCAPED_SLASHES);
Output::concatenate(Output::APIDATA, array_filter($hierarchy_query_select_response));
Output::print(true);
