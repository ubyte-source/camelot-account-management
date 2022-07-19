<?PHP

namespace applications\customer\contact\database\edges;

use ArangoDB\entity\Edge;

class ContactToContact extends Edge
{
	const TARGET = 'applications\\customer\\contact\\database';
	const COLLECTION = 'ContactToContact';
	const DIRECTION = Edge::OUTBOUND;
}
