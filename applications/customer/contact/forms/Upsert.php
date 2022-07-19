<?PHP

namespace applications\customer\contact\forms;

use IAM\Sso;

use Entity\Field;
use Entity\Validation;

use ArangoDB\entity\common\Arango;

use applications\customer\contact\database\Vertex;

class Upsert extends Vertex
{ 
    const CONTACT = '#contact_name# in #contact_address#';
	const CONTACT_GRAB = '/api/customer/contact/read';
	const CONTACT_GRAB_IDENTITY = Arango::KEY;
    const CONTACT_GRAB_RESPONSE = 'data';
    const CONTACT_GRAB_RESPONSE_FIELDS = [
        'contact_name',
        'contact_address',
        'contact_city',
        'contact_zip',
        'contact_vat'
    ];

    const USER = '#firstname# #lastname# <#email#>';
    const USER_READ = '/api/sso/user/gateway/iam/iam/user/read';
    const USER_READ_RESPONSE = 'data';
    const USER_READ_RESPONSE_FIELDS = [
        'username',
        'firstname',
        'email'
    ];

    protected function initialize()
	{
        parent::initialize();

        $related = $this->addField('related');
        $related_pattern = Validation::factory('Enum');
		$related_pattern_search = $related_pattern->getSearch();
		$related_pattern_search->setUnique(static::CONTACT_GRAB_IDENTITY);
		$related_pattern_search->setURL(static::CONTACT_GRAB);
		$related_pattern_search->setResponse(static::CONTACT_GRAB_RESPONSE);
		$related_pattern_search->setLabel(static::CONTACT);
        $related_pattern_search->pushFields(...static::CONTACT_GRAB_RESPONSE_FIELDS);
        $related->setPatterns($related_pattern);

        if (Sso::youHaveNoPolicies('iam/user/action/read')) {
            $this->removeField('owner');
        } else {
            $owner = $this->getField('owner');
            $owner->setProtected(false)->setRequired(true);
            $owner_pattern = Validation::factory('Enum');
            $owner_pattern_search = $owner_pattern->getSearch();
            $owner_pattern_search->setURL(static::USER_READ);
            $owner_pattern_search->setUnique(Sso::IDENTITY);
            $owner_pattern_search->setResponse(static::USER_READ_RESPONSE);
            $owner_pattern_search->pushFields(...static::USER_READ_RESPONSE_FIELDS);
            $owner_pattern_search->setLabel(static::USER);
            $owner->setPatterns($owner_pattern);
        }

        if (false === Sso::youHaveNoPolicies('iam/user/action/read')) {
            $share = $this->addField('share');
            $share_pattern = Validation::factory('Chip');
            $share_pattern_search = $share_pattern->getSearch();
            $share_pattern_search->setURL(static::USER_READ);
            $share_pattern_search->setUnique(Sso::IDENTITY);
            $share_pattern_search->setResponse(static::USER_READ_RESPONSE);
            $share_pattern_search->pushFields(...static::USER_READ_RESPONSE_FIELDS);
            $share_pattern_search->setLabel(static::USER);
            $share->setPatterns($share_pattern);
        }
    }
}
