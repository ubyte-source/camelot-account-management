<?PHP

namespace extensions;

use Entity\Map as Entity;
use Entity\Validation;

final class Point extends Entity
{
	protected function initialize() : void
	{
		$type = $this->addField('type');
		$type_pattern = Validation::factory('Enum');
		$type_pattern->addAssociative('Polygon');
		$type_pattern->addAssociative('Point');
		$type->setPatterns($type_pattern);

        $coordinates = $this->addField('coordinates');
        $coordinates_pattern = Validation::factory('ShowArray', array());
		$coordinates->setPatterns($coordinates_pattern);
		$coordinates->setRequired();
	}
}
