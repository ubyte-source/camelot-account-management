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

use applications\customer\contact\database\Vertex as Contact;
use applications\customer\contact\database\edges\ContactToUser;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/sso/user/action/dissociate')) Output::print(false);

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
$contact_query_book = (array)Request::post('book') ?? array();
if (empty($contact_query_book)) Output::print(false);
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

$user_query_remove = $contact_query->remove();
$user_query_remove->pushStatementsPreliminary(Contact::getCheckMyHierarchy($contact_field_key_value));
$user_query_remove->setActionOnlyEdges(true);
$user_query_remove_response = $user_query_remove->run();
if (null === $user_query_remove_response) Output::print(false);

Output::print(true);
