<?PHP

namespace extensions;

use Entity\Map as Table;
use Entity\Validation;

class Multilanguage extends Table
{
	const LANGUAGE = 'language';

	protected function initialize() : void
	{		
		$language = $this->addField(static::LANGUAGE);
		$language_validator = Validation::factory('Enum');
		$language_validator->addAssociative('it', array('icon' => 'flag-icon flag-icon-it'));
		$language_validator->addAssociative('en', array('icon' => 'flag-icon flag-icon-gb'));
		$language_validator->addAssociative('de', array('icon' => 'flag-icon flag-icon-de'));
		$language_validator->addAssociative('fr', array('icon' => 'flag-icon flag-icon-fr'));
		$language_validator->addAssociative('es', array('icon' => 'flag-icon flag-icon-es'));
		$language->setPatterns($language_validator);
		$language->setRequired();
	}
}
