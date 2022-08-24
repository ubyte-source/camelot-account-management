<?PHP

namespace applications\sso\user\actions;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Request;
use Knight\armor\Language;
use Knight\armor\Navigator;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\entity\common\Arango;

use applications\sso\user\forms\Associate;
use applications\sso\user\database\Vertex as User;
use applications\sso\user\database\edges\UserToContact;
use applications\customer\contact\database\edges\ContactToUser;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/sso/user/action/associate')) Output::print(false);

$contact_field_key_value = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$contact_field_key_value = basename($contact_field_key_value);

$contact_query_hierarchy = User::getHierarchy(User::INCLUDEME, User::QUERYMODE);
$contact_query = ArangoDB::start(...$contact_query_hierarchy);
$contact = $contact_query->begin();
$contact = $contact->useEdge(UserToContact::getName())->vertex();
$contact_fields = $contact->getFields();
foreach ($contact_fields as $field)
    $field->setProtected(true);

$contact->getField(Arango::KEY)->setProtected(false)->setRequired(true)->setValue($contact_field_key_value);
$contact_errors = $contact->checkRequired()->getAllFieldsWarning();

$associate = new Associate();
$associate->setFromAssociative((array)Request::post());
$associate_errors = $associate->checkRequired(true)->getAllFieldsWarning();

if (!!$errors = array_merge($contact_errors, $associate_errors)) {
    Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

$contact_query_select = $contact_query->select();
$contact_query_select->getLimit()->set(1);
$contact_query_select_statement = $contact_query_select->getStatement();

$contact_query = ArangoDB::start($contact);
$contact_query_upsert = $contact_query->upsert();
$contact_query_upsert->pushStatementsPreliminary($contact_query_select_statement);
$contact_query_upsert->pushEntitySkips($contact);

$contact_query_book = $associate->getField('book')->getValue();
foreach ($contact_query_book as $id) {
    $edge = $contact->useEdge(ContactToUser::getName());
    $edge->getField('book')->setProtected(false)->setValue(true);
    $user = $edge->vertex();
    $user_fields = $user->getFields();
    foreach ($user_fields as $field)
        $field->setRequired(false);

    $user->getField(Arango::KEY)->setProtected(false)->setRequired(true)->setValue($id);
    if (!!$user->checkRequired(true)->getAllFieldsWarning())
        Output::print(false);
}

$contact_query_upsert_response = $contact_query_upsert->run();
if (null === $contact_query_upsert_response) Output::print(false);

Output::print(true);
