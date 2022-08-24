<?PHP

namespace applications\sso\user\database\edges;

use Entity\Validation;

use ArangoDB\entity\Edge;

class UserToContact extends Edge
{
	const TARGET = 'applications\\customer\\contact\\database';
	const COLLECTION = 'UserToContact';
	const DIRECTION = Edge::OUTBOUND;

	protected function initialize() : void
	{
		$book = $this->addField('book');
		$book_validator = Validation::factory('ShowBool');
		$book->setPatterns($book_validator);
		$book->addUniqueness(Edge::TYPE);
		$book->setRequired(true);
		$book->setValue(false);
	}
}
