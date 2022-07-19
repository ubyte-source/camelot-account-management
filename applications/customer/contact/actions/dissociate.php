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

use applications\sso\user\database\Vertex as User;
use applications\customer\contact\database\Vertex as Contact;
use applications\customer\contact\database\edges\ContactToContact;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/customer/contact/action/update')) Output::print(false);

$contact_field_key_value = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$contact_field_key_value = basename($contact_field_key_value);

$contact = new Contact();
$contact_fields = $contact->getFields();
foreach ($contact_fields as $field) $field->setProtected(true);

$contact->getField(Arango::KEY)->setProtected(false)->setRequired(true)->setValue($contact_field_key_value);

if (!!$errors = $contact->checkRequired()->getAllFieldsWarning()) {
    Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

IAMRequest::setOverload('iam/user/action/hierarchy');
$hierarchy = Contact::getCheckMyHierarchy($contact_field_key_value);

$contact_query = ArangoDB::start($contact);
$contact->useEdge(ContactToContact::getName())->setForceDirection(Edge::INBOUND);
$user_query_remove = $contact_query->remove();
$user_query_remove->pushStatementsPreliminary($hierarchy);
$user_query_remove->setActionOnlyEdges(true);
$user_query_remove_response = $user_query_remove->run();
if (null === $user_query_remove_response) Output::print(false);

Output::print(true);
