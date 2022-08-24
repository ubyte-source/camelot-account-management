<?PHP

namespace applications\sso\user\actions;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Request;
use Knight\armor\Language;

use Entity\Map as Entity;
use Entity\Field;

use ArangoDB\Initiator as ArangoDB;

use applications\sso\user\database\Vertex as User;
use applications\sso\user\database\edges\UserToSetting;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/sso/user/action/save/widget/setting')) Output::print(false);

$user = new User();
$user->getField(Sso::IDENTITY)->setProtected(false)->setValue(Sso::getWhoamiKey());
$user_query = ArangoDB::start($user);
$user_query_select = $user_query->select();
$user_query_select->getLimit()->set(1);
$user_query_select_return = 'RETURN 1';
$user_query_select->getReturn()->setPlain($user_query_select_return);
$user_query_select_statement = $user_query_select->getStatement();
$user_query_select_statement->setExpect(1)->setHideResponse(true);

$setting = $user->useEdge(UserToSetting::getName())->vertex();
$setting->setFromAssociative((array)Request::post());

if (!!$errors = $setting->checkRequired()->getAllFieldsWarning()) {
    Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

$widget = $setting->getField('widget')->getValue();
$widget = strtolower($widget);
$widget = ucfirst($widget);

$application = $setting->getField('application')->getValue();

$module = $setting->getField('module')->getValue();
$called = 'applications' . '\\' . $application . '\\' . $module;
$called_abstraction = $called . '\\' . 'forms' . '\\' . $widget;

$target = Entity::factory($called_abstraction);

$setting_value = $setting->getField('value');
$setting_value_valid = $target->human();
$setting_value_valid = array_column($setting_value_valid->fields, 'name');
$setting_value_valid = array_filter($setting_value->getValue(), function (Entity $entity) use ($setting_value_valid) {
	return in_array($entity->getField('name')->getValue(), $setting_value_valid);
});
$setting_value_valid = array_values($setting_value_valid);
$setting_value->setValue($setting_value_valid, Field::OVERRIDE);


$query_upsert = $user_query->upsert();
$query_upsert->pushStatementsFinal($user_query_select_statement);
$query_upsert->setActionOnlyEdges(false);

if (null === $query_upsert->run()) Output::print(false);

Output::print(true);
