<?PHP

namespace applications\customer\contact\actions;

use IAM\Sso;
use IAM\Request as IAMRequest;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Language;
use Knight\armor\Request;
use Knight\armor\Navigator;

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
if (Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/update')) Output::print(false);

$contact_field_key_value = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$contact_field_key_value = basename($contact_field_key_value);

$upsert = new Upsert();

if ($upsert->checkFieldExists('owner')) {
    $upsert_owner = $upsert->getField('owner');
    $upsert_owner->setProtected(false)->setRequired(true);
    IAMRequest::setOverload('iam/user/action/hierarchy');
    $hierarchy = User::getHierarchy(User::INCLUDEME);
    if (false === in_array($upsert_owner->getValue(), $hierarchy, false)) $upsert_owner->setDefault();
}

$upsert->setFromAssociative((array)Request::post());
$upsert->vies()->google();

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

if ($upsert->checkFieldExists('owner'))
    $contact->getField('owner')->setProtected(false)->setRequired(true);

$contact->setFromAssociative($upsert->getAllFieldsValues(false, false));
$contact->getField(Arango::KEY)->setProtected(false)->setRequired(true)->setValue($contact_field_key_value);
$contact_query = ArangoDB::start($contact);
if ($upsert->checkFieldExists('owner'))
    $contact->useEdge(ContactToUser::getName())->vertex()->getField(Sso::IDENTITY)->setSafeModeDetached(false)->setValue($upsert->getField('owner')->getValue());

$management = [];
$management_fields = array_intersect(Vertex::MANAGEMENT, $upsert->getAllFieldsProtectedName());
foreach ($management_fields as $name) {
	$contact_document_name = 'OLD' . chr(46) . $name;
	$contact->getField($name)->setSafeModeDetached(false)->setRequired(true)->setValue($contact_document_name);
    array_push($management, $contact_document_name);
}

$contact_query_upsert = $contact_query->upsert();
$contact_query_upsert->setReplace(true);
$contact_query_upsert->setActionOnlyEdges(false);
$contact_query_upsert->pushStatementSkipValues(...$management);
$contact_query_upsert->pushStatementsPreliminary(Contact::getCheckMyHierarchy($contact_field_key_value));
$contact_query_upsert_return = 'RETURN' . chr(32) . Handling::RNEW;
$contact_query_upsert->getReturn()->setPlain($contact_query_upsert_return);
$contact_query_upsert->setEntityEnableReturns($contact);

$remove = new Contact();
$remove->getField(Arango::KEY)->setProtected(false)->setValue($contact_field_key_value);
$remove_query = ArangoDB::start($remove);
$remove->useEdge(ContactToContact::getName())->setForceDirection(Edge::INBOUND);
$remove_query_remove = $remove_query->remove();
$remove_query_remove->setActionOnlyEdges(true);
$contact_query_upsert->pushTransactionsPreliminary($remove_query_remove->getTransaction());

if (!$upsert->getField('related')->isDefault()) {
    $related = $upsert->getField('related')->getValue();
    $contact_query_upsert->pushStatementsPreliminary(Contact::getCheckMyHierarchy($related));
    $contact_query_upsert->pushStatementsFinal(Contact::getRelatedPreventLoop($related));

    $towards = $contact->useEdge(ContactToContact::getName())->setForceDirection(Edge::INBOUND)->vertex();
    $towards->getField(Arango::KEY)->setProtected(false)->setValue($related);
    $contact_query_upsert->pushEntitySkips($towards);
}

if ($upsert->checkFieldExists('share')) {
    $remove = new Contact();
    $remove->getField(Arango::KEY)->setProtected(false)->setValue($contact_field_key_value);
    $remove_query = ArangoDB::start($remove);
    $remove->useEdge(ContactToUser::getName());
    $remove_query_remove = $remove_query->remove();
    $remove_query_remove->setActionOnlyEdges(true);
    $contact_query_upsert->pushTransactionsPreliminary($remove_query_remove->getTransaction());

    $upsert_share = $upsert->getField('share');
    $upsert_share_value = $upsert_share->getValue();
    if (null !== $upsert_share_value && false === $upsert_share->isDefault()) {
        $upsert_share_value = array_intersect($upsert_share_value, Sso::getHierarchy());
        foreach ($upsert_share_value as $item)
            $contact->useEdge(ContactToUser::getName())->vertex()->getField(Sso::IDENTITY)->setSafeModeDetached(false)->setValue($item);
    }
}

$contact_query_upsert_response = $contact_query_upsert->run();
if (null === $contact_query_upsert_response) Output::print(false);

Output::concatenate(Output::APIDATA, reset($contact_query_upsert_response));
Output::print(true);
