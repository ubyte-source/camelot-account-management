<?PHP

namespace applications\customer\contact\forms;

use applications\customer\contact\database\Vertex;

class Read extends Vertex
{
    protected function after() : void
	{
		$this->getField('contact_position')->setProtected(true);
		$this->getField('contact_satisfaction')->setProtected(true);
		$this->getField('contact_domain')->setProtected(true);
	}
}
