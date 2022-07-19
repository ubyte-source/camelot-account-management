<?PHP

namespace applications\sso\user\database\setting;

use IAM\Sso;

use Entity\Validation;

use ArangoDB\entity\Vertex as EVertex;

use extensions\widgets\infinite\Setting as Infinite;

class Vertex extends EVertex
{
	const COLLECTION = 'Setting';
	const IDENTIFIER = [
		'application',
		'module',
		'view',
		'widget'
	];

	protected function initialize() : void
	{
        $key = $this->getField(Sso::IDENTITY);
        $key->setProtected(true)->setRequired(true);

		foreach (static::IDENTIFIER as $name) {
			$field = $this->addField($name);
			$field_validator = Validation::factory('ShowString');
			$field->setPatterns($field_validator);
			$field->addUniqueness(Vertex::TYPE);
			$field->setRequired(true);
		}

		$value = $this->addField('value');
		$value_pattern_infinite = new Infinite();
        $value_pattern_infinite = Validation::factory('Matrioska', $value_pattern_infinite);
        $value_pattern_infinite->setMultiple();
		$value->setPatterns($value_pattern_infinite);
		$value->setRequired(true);
	}

	protected function after() : void
	{
		$setting_created = $this->addField('setting_created');
		$setting_created_validator = Validation::factory('DateTime', null, 'd-m-Y H:i:s', 'Y-m-d H:i:s.u');
		$setting_created->setPatterns($setting_created_validator);
		$setting_created->setProtected();

		$setting_updated = $this->addField('setting_updated');
		$setting_updated_validator = Validation::factory('DateTime', null, 'd-m-Y H:i:s', 'Y-m-d H:i:s.u');
		$setting_updated->setPatterns($setting_updated_validator);
		$setting_updated->setProtected();
	}
}
