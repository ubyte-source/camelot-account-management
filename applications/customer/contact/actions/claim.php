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

use applications\customer\contact\database\Vertex as Contact;
use applications\customer\contact\database\edges\ContactToUser;
use applications\customer\contact\forms\Claim;

Language::dictionary(__file__);

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/claim')) {
    $notice = __namespace__ . '\\' . 'policy';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

$claim = new Claim();
$claim->setFromAssociative((array)Request::post());

if (!!$errors = $claim->checkRequired(true)->getAllFieldsWarning()) {
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

$serial = $claim->getField('device_serial');
$serial = $serial->getValue();

$passphrase = 'machine/device/passphrase' . chr(47) . $serial;
IAMRequest::setOverload('engine/machine/device/action/passphrase');
Gateway::callAPI('engine', $passphrase, (array)$claim->getAllFieldsValues(true, false));

$account_api = 'machine/device/detail' . chr(47) . $serial;
IAMRequest::setOverload(
    'engine/machine/device/action/detail',
    'engine/machine/device/action/read/all'
);
$account = Gateway::callAPI('engine', $account_api);
$account = $account->{Output::APIDATA};

$contact = new Contact();
$contact_fields = $contact->getFields();
foreach ($contact_fields as $field)
    $field->setRequired(false);

$contact->getField(Contact::KEY)->setProtected(false)->setRequired(true);
$contact->setFromAssociative((array)$account->account);
$contact_query = ArangoDB::start($contact);

$user = $contact->useEdge(ContactToUser::getName())->vertex();
$user_fields = $user->getFields();
foreach ($user_fields as $field)
    $field->setRequired(false);

$user->getField(Contact::KEY)->setProtected(false)->setRequired(true);
$user->setFromAssociative((array)Sso::getWhoami());

if (!!$contact->checkRequired(true)->getAllFieldsWarning() || !!$user->checkRequired(true)->getAllFieldsWarning()) {
    $notice = __namespace__ . '\\' . 'passphrase';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::print(false);
}

$contact_query_upsert = $contact_query->upsert();
$contact_query_upsert->pushEntitySkips($contact);
$contact_query_upsert->setActionOnlyEdges(false);
$contact_query_upsert_response = $contact_query_upsert->run();
if (null !== $contact_query_upsert_response) Output::print(true);

$notice = __namespace__ . '\\' . 'alredy';
$notice = Language::translate($notice);
Output::concatenate('notice', $notice);
Output::print(false);
