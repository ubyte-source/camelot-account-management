<?PHP

namespace applications\customer\contact\actions;

use IAM\Sso;
use IAM\Request as IAMRequest;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Language;
use Knight\armor\Navigator;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\Statement;
use ArangoDB\operations\common\Choose;
use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;

use applications\sso\user\database\Vertex as User;
use applications\sso\user\database\edges\UserToContact;
use applications\customer\contact\database\Vertex as Contact;
use applications\customer\contact\database\edges\ContactToUser;
use applications\customer\contact\database\edges\ContactToContact;
use applications\customer\contact\forms\Upsert;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/detail')) Output::print(false);

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

if (!!$errors = $contact->checkRequired()->getAllFieldsWarning()) {
    Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}


if (!Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/read/all')) $contact_query = ArangoDB::start($contact);

$contact_query_select = $contact_query->select();
$contact_query_select->getLimit()->set(1);
$contact_query_select_vertex = $contact_query_select->getPointer(Choose::VERTEX);
$contact_query_select_traversal_vertex = $contact_query_select->getPointer(Choose::TRAVERSAL_VERTEX);

$father = new Contact();
$father_id = $contact_query_select_vertex . chr(46) . $father->getField(Arango::ID)->getName();
$father->getField(Arango::ID)->setSafeModeDetached(false)->setValue($father_id);
$father_query = ArangoDB::start($father);
$father->useEdge(ContactToContact::getName())->setForceDirection(Edge::INBOUND);
$father_query_select = $father_query->select();
$father_query_select->pushStatementSkipValues($father_id);
$father_query_select->useWith(false);
$father_query_select->getLimit()->set(1);
$father_query_select_return = 'RETURN' . chr(32) . $father_query_select->getPointer(Choose::VERTEX);
$father_query_select->getReturn()->setPlain($father_query_select_return);
$father_query_select_statement = $father_query_select->getStatement();
$father_query_select_statement_query = $father_query_select_statement->getQuery();

$upsert = new Upsert();
$upsert_field = $upsert->getFields();
foreach ($upsert_field as $field)
    $field->setProtected(false); 

$contact_query_select_return_statement = new Statement();

if ($upsert->checkFieldExists('share')) {
    $share = new Contact();
    $share->getField(Arango::KEY)->setProtected(false)->setValue($contact_field_key_value);
    $share_query = ArangoDB::start($share);
    $share->useEdge(ContactToUser::getName())->vertex();
    $share_query_select = $share_query->select();
    $share_query_select->useWith(false);
    $share_query_select_vertex = $share_query_select->getPointer(Choose::VERTEX);
    $share_query_select_vertex_traversal = $share_query_select->getPointer(Choose::TRAVERSAL_VERTEX);
    $share_query_select_return_statement = new Statement();
    $share_query_select_return_statement_return = $share_query_select_vertex . chr(46) . Sso::IDENTITY;
    $share_query_select_return_statement->append('LET');
    $share_query_select_return_statement->append('contact');
    $share_query_select_return_statement->append('=');
    $share_query_select_return_statement->append('FIRST' . chr(40) . $share_query_select_vertex_traversal . chr(41));
    $share_query_select_return_statement->append('FILTER');
    $share_query_select_return_statement->append($share_query_select_return_statement_return);
    $share_query_select_return_statement->append('!=');
    $share_query_select_return_statement->append('contact.owner');
    $share_query_select_return_statement->append('RETURN');
    $share_query_select_return_statement->append($share_query_select_return_statement_return);
    $share_query_select->getReturn()->setFromStatement($share_query_select_return_statement);
    $share_query_select_statement = $share_query_select->getStatement();
    $share_query_select_statement_query = $share_query_select_statement->getQuery();

    $contact_query_select_return_statement_share = $upsert->getField('share')->getName();
    $contact_query_select_return_statement->append('LET');
    $contact_query_select_return_statement->append($contact_query_select_return_statement_share);
    $contact_query_select_return_statement->append('=');
    $contact_query_select_return_statement->append(chr(40) . $share_query_select_statement_query . chr(41));
    $contact_query_select_return_statement->addBindFromStatements($share_query_select_statement);
}

$contact_query_select_return_statement_related = $upsert->getField('related')->getName();
$contact_query_select_return_statement->append('LET');
$contact_query_select_return_statement->append($contact_query_select_return_statement_related);
$contact_query_select_return_statement->append('=');
$contact_query_select_return_statement->append('FIRST' . chr(40) .  $father_query_select_statement_query . chr(41));
$contact_query_select_return_statement->append('RETURN');
$contact_query_select_return_statement->append('MERGE' . chr(40) . $contact_query_select_vertex . chr(44));
if ($upsert->checkFieldExists('share'))
    $contact_query_select_return_statement->append(chr(123) . $contact_query_select_return_statement_share . chr(125) . chr(44));

$contact_query_select_return_statement->append(chr(123) . $contact_query_select_return_statement_related . chr(125) . chr(41));
$contact_query_select->getReturn()->setFromStatement($contact_query_select_return_statement);
$contact_query_select_response = $contact_query_select->run();
if (null === $contact_query_select_response) Output::print(false);

$contact_query_select_response = reset($contact_query_select_response);

$upsert = new Upsert();
$upsert->setSafeMode(false)->setReadMode(true);
$upsert->setFromAssociative($contact_query_select_response);

IAMRequest::setOverload(
    'iam/user/action/read',
    'iam/user/action/read/all'
);

if ($upsert->checkFieldExists('share')) {
    $upsert_share = $upsert->getfield('share');
    $upsert_share_value = $upsert_share->getValue();
    if (false === $upsert_share->isDefault()) {
        $upsert_share_value = $upsert_share->getValue();
        $upsert_share_value = Sso::getUsers(null, null, ...$upsert_share_value);
        $upsert_share_value = array_values($upsert_share_value);
        $upsert_share->setValue($upsert_share_value);
    }
}

if ($upsert->checkFieldExists('owner')) {
    $upsert_owner = $upsert->getfield('owner');
    $upsert_owner_value = $upsert_owner->getValue();
    $upsert_owner_value = Sso::getUsers(null, null, $upsert_owner_value);
    $upsert_owner_value = reset($upsert_owner_value);
    $upsert_owner->setValue($upsert_owner_value);
}

Output::concatenate(Output::APIDATA, $upsert->getAllFieldsValues(false, false));
Output::print(true);
