<?PHP

namespace applications\customer\contact\forms;

use Entity\Field;
use Entity\Validation;

use ArangoDB\entity\Vertex;

class Claim extends Vertex
{
	protected function initialize() : void
	{        
        $device_serial = $this->addField('device_serial');
		$device_serial_validation = Validation::factory('Number', 0);
		$device_serial->setPatterns($device_serial_validation);
		$device_serial->setRequired(true);

        $device_passphrase_value = $this->addField('device_passphrase_value');
		$device_passphrase_value_validation = Validation::factory('ShowString');
		$device_passphrase_value->setPatterns($device_passphrase_value_validation);
		$device_passphrase_value->setRequired(true);		
	}   
}