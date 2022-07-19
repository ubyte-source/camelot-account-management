<?PHP

namespace applications\customer\contact\actions;

use IAM\Sso;
use IAM\Gateway;
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
use applications\customer\contact\forms\Claim;

$application_basename = IAMConfiguration::getApplicationBasename();
//if (Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/claim')) Output::print(false);

const ACCOUNT = 'account';

$serial = '14021';

IAMRequest::setOverload('engine/machine/device/action/passphrase');

$_POST['device_serial'] = '14021';
$_POST['device_passphrase_value'] = '4xNjI4NTI0ODg2MD';

$claim = new Claim();
$claim->setFromAssociative((array)Request::post());

if (!!$errors = $claim->checkRequired(true)->getAllFieldsWarning()) {
    Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

$serial = $claim->getField('device_serial');
$serial = $serial->getValue();

$passphrase = 'machine/device/passphrase' . chr(47) . $serial;
Gateway::callAPI('engine', $passphrase, (array)$claim->getAllFieldsValues(false, false));

exit;

$account_api = 'machine/device/detail' . chr(47) . $serial;
$account = Gateway::callAPI('engine', $account_api);
$account = $account->{Output::APIDATA};

$contact = new Contact();
$contact_fields = $contact->getFields();
foreach ($contact_fields as $field) $field->setRequired(false);

$contact->getField(Contact::KEY)->setProtected(false)->setRequired(true);
$contact->setFromAssociative((array)$account->{ACCOUNT});
$contact_query = ArangoDB::start($contact);

$user = $contact->useEdge(ContactToUser::getName())->vertex();
$user_fields = $user->getFields();
foreach ($user_fields as $field) $field->setRequired(false);

$user->getField(Contact::KEY)->setProtected(false)->setRequired(true);
$user->setFromAssociative((array)Sso::getWhoami());

if (!!$contact->checkRequired(true)->getAllFieldsWarning()
    || !!$user->checkRequired(true)->getAllFieldsWarning())
        Output::print(false);


print_r($user->getAllFieldsValues(false, false));
print_r($contact->getAllFieldsValues(false, false));







exit;
////

$contact_query_upsert = $contact_query->upsert();
$contact_query_upsert->setActionOnlyEdges(true);




if (false === property_exists($device, ACCOUNT)
    || false === property_exists($device->account, ACCOUNT_OWNER)) Output::print(false);


$upsert = new Upsert();
$upsert_owner = $upsert->getField('owner');
$upsert_owner->setProtected(false)->setRequired(true);
$upsert->setFromAssociative((array)Request::post());
$upsert->vies()->google();

IAMRequest::setOverload('engine/machine/device/action/passphrase');

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
