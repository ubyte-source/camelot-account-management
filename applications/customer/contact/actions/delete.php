<?PHP

namespace applications\customer\contact\actions;

use IAM\Sso;
use IAM\Request as IAMRequest;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Language;
use Knight\armor\Navigator;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\Handling;

use applications\customer\contact\database\Vertex as Contact;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/delete')) Output::print(false);

$follow = new Contact();
ArangoDB::start($follow);
$follow = $follow->getAllUsableEdgesName(true);

$contact_field_key_value = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$contact_field_key_value = basename($contact_field_key_value);

$contact = new Contact();
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

$contact_query = ArangoDB::start($contact);
foreach ($follow as $name) {
    $contact->useEdge($name)->setForceDirection(Edge::INBOUND);
    $contact->useEdge($name)->setForceDirection(Edge::OUTBOUND);
}

$contact_query_delete = $contact_query->remove();
$contact_query_delete->setActionOnlyEdges(false);
$contact_query_delete->pushStatementsPreliminary(Contact::getCheckMyHierarchy($contact_field_key_value));
$contact_query_delete_return = 'RETURN' . chr(32) . Handling::ROLD;
$contact_query_delete->getReturn()->setPlain($contact_query_delete_return);
$contact_query_delete->setEntityEnableReturns($contact);
$contact_query_delete_response = $contact_query_delete->run();
if (null === $contact_query_delete_response) Output::print(false);

Output::concatenate(Output::APIDATA, reset($contact_query_delete_response));
Output::print(true);
