<?PHP

namespace applications\sso\user\forms;

use IAM\Sso;
use IAM\Request;
use IAM\Gateway;

use Knight\armor\Output;

use Entity\map\Remote;

use ArangoDB\entity\common\Arango;

use applications\sso\user\database\Vertex;

class Book extends Vertex
{
    protected function after() : void
	{
        $user = new Remote($this, 'iam', 'iam/user');
        $user->setStructure(function () {
            $parameters = $this->getParameters();
            return Gateway::getStructure($parameters[0], $parameters[1] . chr(63) . 'language' . chr(61) . Sso::getWhoamiLanguage());
        });

        Request::setOverload(
            'iam/user/action/read',
            'iam/user/action/read/all'
        );

        $user->getData()->setKey($this->getField(Arango::KEY)->getName());
		$user->getData()->setWorker(function ($post) : array {
			$parameters = $this->getRemote()->getParameters();
            $request = Gateway::callAPI($parameters[0], 'iam/user/read', $post);
			return $request->{Output::APIDATA};
		});
		$this->addRemote($user);
    }
}
