<?PHP

namespace applications\sso\user\database\edges;

use ArangoDB\entity\Edge;

class UserToSetting extends Edge
{
	const TARGET = 'applications\\sso\\user\\database\\setting';
	const COLLECTION = 'UserToSetting';
	const DIRECTION = Edge::OUTBOUND;
}
