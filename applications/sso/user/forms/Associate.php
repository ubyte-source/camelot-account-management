<?PHP

namespace applications\sso\user\forms;

use IAM\Sso;

use Entity\Field;
use Entity\Validation;

use ArangoDB\entity\Vertex;

use ArangoDB\entity\common\Arango;

class Associate extends Vertex
{
    const USER = '#firstname# #lastname#';
    const USER_READ = '/api/sso/user/gateway/iam/iam/user/read';
    const USER_READ_RESPONSE = 'data';
    const USER_READ_RESPONSE_FIELDS = [
        'lastname',
        'firstname',
        'email'
    ];

	protected function initialize() : void
	{        
        $book = $this->addField('book');
        $book_pattern = Validation::factory('Chip');
        $book_pattern_search = $book_pattern->getSearch();
        $book_pattern_search->setURL(static::USER_READ);
        $book_pattern_search->setUnique(Sso::IDENTITY);
        $book_pattern_search->setResponse(static::USER_READ_RESPONSE);
        $book_pattern_search->pushFields(...static::USER_READ_RESPONSE_FIELDS);
        $book_pattern_search->setLabel(static::USER);
        $book->setPatterns($book_pattern);
        $book->setRequired(true);
	}   
}