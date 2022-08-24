<?PHP

namespace applications\sso\user\actions;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\output\Data;
use Knight\armor\Request;
use Knight\armor\Navigator;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\operations\common\Choose;
use ArangoDB\entity\common\Arango;

use applications\sso\user\forms\Book;
use applications\sso\user\database\Vertex as User;
use applications\sso\user\database\edges\UserToContact;
use applications\customer\contact\database\edges\ContactToUser;

use extensions\widgets\infinite\Setting;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/sso/user/action/read')) Output::print(false);

$contact_field_key_value = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$contact_field_key_value = basename($contact_field_key_value);

$hierarchy_query = User::getHierarchy(User::INCLUDEME, User::QUERYMODE);
$hierarchy_query = ArangoDB::start(...$hierarchy_query);
$hierarchy_query_to_contact = $hierarchy_query->begin();
$hierarchy_query_to_contact = $hierarchy_query_to_contact->useEdge(UserToContact::getName());

$contact = $hierarchy_query_to_contact->vertex();
$contact_fields = $contact->getFields();
foreach ($contact_fields as $field)
    $field->setRequired(false);

$contact->getField(Arango::KEY)->setProtected(false)->setRequired(true)->setValue($contact_field_key_value);
$contact->useEdge(ContactToUser::getName())->getField('book')->setProtected(false)->setRequired(true)->setValue(true);

if (!!$errors = $contact->checkRequired()->getAllFieldsWarning()) {
    Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

$hierarchy_query_select = $hierarchy_query->select();

$or = Request::get('force-use-or');
$or = filter_var($or, FILTER_VALIDATE_BOOLEAN);
if (true === $or && false === Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/read/or')) $hierarchy_query_select->pushEntitiesUsingOr($contact);

if (!!$count_offset = Request::get('offset')) $hierarchy_query_select->getLimit()->setOffset($count_offset);
if (!!$count = Request::get('count')) $hierarchy_query_select->getLimit()->set($count);

$hierarchy_query_select_return = 'RETURN' . chr(32) . $hierarchy_query_select->getPointer(Choose::VERTEX) . chr(46) . Arango::KEY;
$hierarchy_query_select->getReturn()->setPlain($hierarchy_query_select_return);
$hierarchy_query_select_response = $hierarchy_query_select->run();
if (null === $hierarchy_query_select_response) Output::print(false);

$book = new Book();
$book->setSafeMode(false)->setReadMode(true);

$hierarchy_query_select_response_count = count($hierarchy_query_select_response);
$hierarchy_query_select_response = array_map(function (string $key) use ($book) {
    $clone = clone $book;
    $clone->getField(Arango::KEY)->setValue($key);
    return $clone->getAllFieldsValues(false, false);
}, $hierarchy_query_select_response);

$remotes_only = Data::only($book->getField(Arango::KEY)->getName());
$remotes = $book->getRemotes(...$remotes_only);
foreach ($remotes as $remote)
    $remote->getData()->get($hierarchy_query_select_response, 0, (array)Request::post());

if (false === empty($remotes_only)) {
    $remotes_only_filled = array_fill_keys($remotes_only, null);
    array_walk($hierarchy_query_select_response, function (array &$item) use ($remotes_only_filled) {
        $item = array_intersect_key($item, $remotes_only_filled);
    });
}

$hierarchy_query_select_response = array_filter($hierarchy_query_select_response);
$hierarchy_query_select_response = array_values($hierarchy_query_select_response);

Output::setEncodeOptionOverride(JSON_UNESCAPED_SLASHES);
Output::concatenate(Setting::COMPLETE, $hierarchy_query_select_response_count === count($hierarchy_query_select_response));
Output::concatenate(Output::APIDATA, array_filter($hierarchy_query_select_response));
Output::print(true);
