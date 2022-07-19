<?PHP

namespace applications\sso\user\database\edges;

use ArangoDB\entity\Edge;

class UserToContact extends Edge
{
	const TARGET = 'applications\\customer\\contact\\database';
	const COLLECTION = 'UserToContact';
	const DIRECTION = Edge::OUTBOUND;
}
