<?PHP

namespace applications\customer\contact\database\edges;

use ArangoDB\entity\Edge;

use applications\sso\user\database\edges\UserToContact;

class ContactToUser extends UserToContact
{
	const TARGET = 'applications\\sso\\user\\database';
	const DIRECTION = Edge::INBOUND;
}
