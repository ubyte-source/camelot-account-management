<?PHP

namespace applications\sso\user\database;

use IAM\Sso;
use IAM\Request as IAMRequest;

use Entity\Validation;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\operations\common\Choose;
use ArangoDB\entity\Vertex as EVertex;

use applications\sso\user\database\setting\Vertex as Setting;
use applications\sso\user\database\edges\UserToSetting;

class Vertex extends EVertex
{
	const COLLECTION = 'User';

	const INCLUDEME = true; // (bool)
	const QUERYMODE = true; // (bool)

	protected function after() : void
	{
		$user_created = $this->addField('user_created');
		$user_created_validator = Validation::factory('DateTime', null, 'd-m-Y H:i:s', 'Y-m-d H:i:s.u');
		$user_created->setPatterns($user_created_validator);
		$user_created->setProtected();

		$user_updated = $this->addField('user_updated');
		$user_updated_validator = Validation::factory('DateTime', null, 'd-m-Y H:i:s', 'Y-m-d H:i:s.u');
		$user_updated->setPatterns($user_updated_validator);
		$user_updated->setProtected();
	}
	
	public static function getSettings(string ...$filters) : array
	{
		$user = new static();
		$user->getField(Sso::IDENTITY)->setProtected(false)->setValue(Sso::getWhoamiKey());
		$user_query = ArangoDB::start($user);

		$setting = $user->useEdge(UserToSetting::getName())->vertex();
		$setting_fields = $setting->getFields();
		foreach ($setting_fields as $field) {
			if (!in_array($field->getName(), Setting::IDENTIFIER)) continue;
			$field->setProtected(false)->setValue(array_shift($filters));
		}

		$user_query_select = $user_query->select();
		$user_query_select_return = 'RETURN' . chr(32) . $user_query_select->getPointer(Choose::VERTEX);
		$user_query_select->getReturn()->setPlain($user_query_select_return);
		$user_query_select->getLimit()->set(1);
		$user_query_select_response = $user_query_select->run();
		$user_query_select_response = reset($user_query_select_response);

		if (false === $user_query_select_response) return array();

		$setting = new Setting();
		$setting->setReadMode(true);
		$setting->setFromAssociative($user_query_select_response);
		$setting_value_name = $setting->getField('value')->getName();
		$setting_value = $setting->getAllFieldsValues(false, false);
		return $setting_value[$setting_value_name];
	}

	public static function getHierarchy(bool $me = false, bool $query = false) : array
	{
		IAMRequest::setOverload('iam/user/action/hierarchy');
		$hierarchy = Sso::getHierarchy();

		if ($me === static::INCLUDEME) array_push($hierarchy, Sso::getWhoamiKey());
		return $query !== static::QUERYMODE ? $hierarchy : array_map(function (string $key) {
			$user = new static();
			$user->getField(Sso::IDENTITY)->setProtected(false)->setValue($key);
			return $user;
		}, $hierarchy);
	}
}
