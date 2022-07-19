<?PHP

namespace applications\customer\contact\actions;

use IAM\Sso;
use IAM\Request as IAMRequest;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Language;
use Knight\armor\Request;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\entity\Edge;
use ArangoDB\entity\Vertex;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\Handling;

use applications\sso\user\database\Vertex as User;
use applications\customer\contact\database\Vertex as Contact;
use applications\customer\contact\database\edges\ContactToUser;
use applications\customer\contact\database\edges\ContactToContact;
use applications\customer\contact\forms\Upsert;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/create')) Output::print(false);

$upsert = new Upsert();
$upsert_owner = $upsert->getField('owner');
$upsert_owner->setProtected(false)->setRequired(true);
$upsert->setFromAssociative((array)Request::post());
$upsert->vies()->google();

IAMRequest::setOverload('iam/user/action/hierarchy');

$hierarchy = User::getHierarchy(User::INCLUDEME);
if (false === in_array($upsert_owner->getValue(), $hierarchy, false)) $upsert_owner->setDefault();

if (!!$errors = $upsert->checkRequired(true)->getAllFieldsWarning()) {
    Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

$contact = new Contact();
$contact_fields = $contact->getFields();
foreach ($contact_fields as $field) $field->setProtected(false);

$contact->getField('owner')->setProtected(false)->setRequired(true);
$contact->setFromAssociative($upsert->getAllFieldsValues(false, false));
$contact_query = ArangoDB::start($contact);

$user = $contact->useEdge(ContactToUser::getName())->vertex();
$user->getField(Sso::IDENTITY)->setSafeModeDetached(false)->setValue($upsert_owner->getValue());
$user_query = ArangoDB::start($user);
$user_query_upsert = $user_query->upsert();
$user_query_upsert->setActionOnlyEdges(false);

$contact_query_insert = $contact_query->insert();
$contact_query_insert->pushEntitySkips($user);
$contact_query_insert->setActionOnlyEdges(false);
$contact_query_insert->pushTransactionsPreliminary($user_query_upsert->getTransaction());
$contact_query_insert_return = 'RETURN' . chr(32) . Handling::RNEW;
$contact_query_insert->getReturn()->setPlain($contact_query_insert_return);
$contact_query_insert->setEntityEnableReturns($contact);

if (!$upsert->getField('related')->isDefault()) {
    $related = $upsert->getField('related')->getValue();
    $contact_query_insert->pushStatementsPreliminary(Contact::getCheckMyHierarchy($related));
    $contact_query_insert->pushStatementsFinal(Contact::getRelatedPreventLoop($related));

    $towards = $contact->useEdge(ContactToContact::getName())->setForceDirection(Edge::INBOUND)->vertex();
    $towards->getField(Arango::KEY)->setProtected(false)->setValue($related);
    $contact_query_insert->pushEntitySkips($towards);
}

$upsert_share = $upsert->getField('share');
$upsert_share_value = $upsert_share->getValue();
if (null !== $upsert_share_value && false === $upsert_share->isDefault()) {
    $upsert_share_value = array_intersect($upsert_share_value, Sso::getHierarchy());
    foreach ($upsert_share_value as $item)
        $contact->useEdge(ContactToUser::getName())->vertex()->getField(Sso::IDENTITY)->setSafeModeDetached(false)->setValue($item);
}

$management_fields = array_intersect(Vertex::MANAGEMENT, $contact->getAllFieldsProtectedName());
foreach ($management_fields as $name) $contact->getField($name)->setProtected(false)->setRequired(true)->setValue(Sso::getWhoamiKey());

$contact_query_insert_response = $contact_query_insert->run();
if (null === $contact_query_insert_response) Output::print(false);

Output::concatenate(Output::APIDATA, reset($contact_query_insert_response));
Output::print(true);
