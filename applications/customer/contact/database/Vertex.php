<?PHP

namespace applications\customer\contact\database;

use SoapFault;
use SoapClient;

use IAM\Sso;
use IAM\Request as IAMRequest;

use Knight\armor\Curl;
use Knight\armor\Language;

use Entity\Field;
use Entity\Validation;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\Statement;
use ArangoDB\entity\common\Arango;
use ArangoDB\entity\Vertex as EVertex;

use configurations\Google;
use configurations\Vies;

use applications\sso\user\database\Vertex as User;
use applications\sso\user\database\edges\UserToContact;
use applications\customer\contact\database\edges\ContactToContact;

use extensions\Point;

class Vertex extends EVertex
{
	const COLLECTION = 'Contact';

	const LOCATION = 'location';

	protected $point; // Point

	public function vies() : self
    {
		$contact_values = $this->getAllFieldsValues();
		$contact_values = array_intersect_key($contact_values, Vies::VIES_FIELDS);
		$contact_values = array_map('strtoupper', $contact_values);
		$contact_values_metamorphosis = array_merge($contact_values, Vies::VIES_FIELDS);
		$contact_values_metamorphosis = array_combine($contact_values_metamorphosis, $contact_values);

		try {
			$client = new SoapClient(Vies::VIES_SERVER);
			$clinet_response = $client->checkVat($contact_values_metamorphosis);
		} catch (SoapFault $exception) {
			return $this;
		}
		
		if (true !== $clinet_response->valid) return $this;

		$vies = $this->getField('contact_vies');
		$vies->setProtected(false)->setValue(true);
		$vies->setProtected(true);

		$this->getField('contact_name')->setValue($clinet_response->name);

		$contact_address = $this->getField('contact_address');
		$contact_address_value = $contact_address->getValue();
		if (0 !== strlen($contact_address_value)) return $this;

		$contact_address_value = preg_replace('/[\r\n]/', chr(32), $clinet_response->address);
		$contact_address->setValue($contact_address_value);

		return $this;
	}

	public function google() : self
    {
        $curl = new Curl();
		$curl_address = Google::GOOGLE_API . Google::GOOGLE_API_KEY;

		$contact_position = $this->getField('contact_position');
		$contact_position->setProtected(false)->setRequired(true);

		$address = [];
		foreach (Google::GOOGLE_API_ADDRESS as $name) {
			$field = $this->getField($name);
			$field_value = $field->getValue();
			if (is_string($field_value)) {
				$field_value = trim($field_value);
				if (0 === strlen($field_value)) continue;
				array_push($address, $field_value);
			}
		}

		$address = implode(chr(44), $address);
        $address = preg_replace('/[\r\n]/', chr(32), $address);
        $address = mb_convert_encoding($address, 'UTF-8');
		$address = urlencode($address);
		$address = sprintf(Google::GOOGLE_API . Google::GOOGLE_API_KEY, $address);

        $curl_response = $curl->request($address);
		if (false === property_exists($curl_response, 'results')
			|| empty($curl_response->results)) return $this;

		$google = $this->getField('contact_google');
		$google->setProtected(false)->setValue(true);
		$google->setProtected(true);

		$curl_response = reset($curl_response->results);
		array_walk($curl_response->address_components, function ($item) {
			$item->types = reset($item->types);
		});

		$curl_response_address = (object)array_column($curl_response->address_components, 'long_name', 'types');

		if (property_exists($curl_response_address, 'route')) $this->getField('contact_address')->setValue($curl_response_address->route);
		if (property_exists($curl_response_address, 'street_number')) $this->getField('contact_address_number')->setValue($curl_response_address->street_number);
		if (property_exists($curl_response_address, 'postal_code')) $this->getField('contact_zip')->setValue($curl_response_address->postal_code);

		$curl_response_address_keys = array_keys((array)$curl_response_address);
		$curl_response_address_keys = preg_grep('/(administrative_area_level|locality)/', $curl_response_address_keys);
		$curl_response_address_keys = array_fill_keys($curl_response_address_keys, null);
		$curl_response_address_city = array_intersect_key((array)$curl_response_address, $curl_response_address_keys);
		$curl_response_address_city = reset($curl_response_address_city);

		$contact_city = $this->getField('contact_city');
		if (is_string($curl_response_address_city))
			$contact_city->setValue($curl_response_address_city);

		$contact_province = $this->getField('contact_province');
		$contact_province_value = $curl_response_address->administrative_area_level_2
			?? $curl_response_address->administrative_area_level_3
			?? false;
		if (is_string($contact_province_value))
			$contact_province->setValue($contact_province_value);

		$curl_response_address = (object)array_column($curl_response->address_components, 'short_name', 'types');
		if (property_exists($curl_response_address, 'country'))
			$this->getField('contact_country')->setValue(mb_strtolower($curl_response_address->country));

		$point = $this->getPoint();
		if (null === $point) return $this;

		$point->getField('type')->setValue('Point');
		$contact_position_value = array();
		$contact_position_value[$point->getField('coordinates')->getName()] = [
			$curl_response->geometry->location->lat,
			$curl_response->geometry->location->lng
		];
		$contact_position->setValue($contact_position_value);

		return $this;
	}

	public static function getCheckMyHierarchy(string $contact_key_value) : Statement
	{
		IAMRequest::setOverload('iam/user/action/hierarchy');

		$hierarchy = User::getHierarchy(User::INCLUDEME, User::QUERYMODE);

		Language::dictionary(__file__);
		$exception_message = __namespace__ . '\\' . 'exception' . '\\';
		
		$check_select = ArangoDB::start(...$hierarchy);
		$check = $check_select->begin();
		$check = $check->useEdge(UserToContact::getName())->vertex();
		$check->getField(Arango::KEY)->setProtected(false)->setValue($contact_key_value);
		$check_select_select = $check_select->select();
		$check_select_select->getLimit()->set(1);
		$check_select_select_return = 'RETURN 1';
		$check_select_select->getReturn()->setPlain($check_select_select_return);
		$check_select_select_statement = $check_select_select->getStatement();
		$check_select_select_statement_exception_message = $exception_message . 'hierarchy';
		$check_select_select_statement_exception_message = Language::translate($check_select_select_statement_exception_message);
		$check_select_select_statement->setExceptionMessage($check_select_select_statement_exception_message);
		$check_select_select_statement->setExpect(1)->setHideResponse(true);

		return $check_select_select_statement;
	}

	public static function getRelatedPreventLoop(string $contact_key_value) : Statement
	{
		Language::dictionary(__file__);
		$exception_message = __namespace__ . '\\' . 'exception' . '\\';

		$check = new static();
		$check->getField(Arango::KEY)->setProtected(false)->setValue($contact_key_value);
		$check_select = ArangoDB::start($check);
		$check->useEdge(ContactToContact::getName())->vertex()->useEdge(ContactToContact::getName())->vertex($check);
		$check->useEdge(ContactToContact::getName())->vertex($check);
		$check_select_select = $check_select->select();
		$check_select_select->getLimit()->set(0);
		$check_select_select_return = 'RETURN 1';
		$check_select_select->getReturn()->setPlain($check_select_select_return);
		$check_select_select_statement = $check_select_select->getStatement();
		$check_select_select_statement_exception_message = $exception_message . 'loop';
		$check_select_select_statement_exception_message = Language::translate($check_select_select_statement_exception_message);
		$check_select_select_statement->setExceptionMessage($check_select_select_statement_exception_message);
		$check_select_select_statement->setExpect(0)->setHideResponse(true);

		return $check_select_select_statement;
	}

	public function getPoint() :? Point
	{
		return $this->point;
	}

	protected static function viesNameDogma(string $name) : string
	{
		$name_parsed = mb_strtolower($name);
		$name_parsed_clean = strpos($name_parsed, chr(33));
		if (false !== $name_parsed_clean) $name_parsed = substr($name_parsed, 0, $name_parsed_clean);

		$name_parsed_key = array_keys(Vies::VIES_DOGMA);
		$name_parsed = preg_replace(Vies::VIES_DOGMA, $name_parsed_key, $name_parsed);
		$name_parsed = trim($name_parsed);

		return $name_parsed;
	}

	protected function initialize()
	{
		$this->setPoint(new Point());

		$contact_country_validator = Validation::factory('Enum');
		$contact_country_validator->addAssociative('it', array('icon' => 'flag-icon flag-icon-it'));
		$contact_country_validator->addAssociative('be', array('icon' => 'flag-icon flag-icon-be'));
		$contact_country_validator->addAssociative('nl', array('icon' => 'flag-icon flag-icon-nl'));
		$contact_country_validator->addAssociative('es', array('icon' => 'flag-icon flag-icon-es'));
		$contact_country_validator->addAssociative('fi', array('icon' => 'flag-icon flag-icon-fi'));
		$contact_country_validator->addAssociative('si', array('icon' => 'flag-icon flag-icon-si'));
		$contact_country_validator->addAssociative('ch', array('icon' => 'flag-icon flag-icon-ch'));
		$contact_country_validator->addAssociative('at', array('icon' => 'flag-icon flag-icon-at'));
		$contact_country_validator->addAssociative('se', array('icon' => 'flag-icon flag-icon-se'));
		$contact_country_validator->addAssociative('pl', array('icon' => 'flag-icon flag-icon-pl'));
		$contact_country_validator->addAssociative('il', array('icon' => 'flag-icon flag-icon-il'));
		$contact_country_validator->addAssociative('tr', array('icon' => 'flag-icon flag-icon-tr'));
		$contact_country_validator->addAssociative('fr', array('icon' => 'flag-icon flag-icon-fr'));
		$contact_country_validator->addAssociative('ee', array('icon' => 'flag-icon flag-icon-ee'));
		$contact_country_validator->addAssociative('ro', array('icon' => 'flag-icon flag-icon-ro'));
		$contact_country_validator->addAssociative('sk', array('icon' => 'flag-icon flag-icon-sk'));
		$contact_country_validator->addAssociative('lv', array('icon' => 'flag-icon flag-icon-lv'));
		$contact_country_validator->addAssociative('in', array('icon' => 'flag-icon flag-icon-in'));
		$contact_country_validator->addAssociative('ir', array('icon' => 'flag-icon flag-icon-ir'));
		$contact_country_validator->addAssociative('jo', array('icon' => 'flag-icon flag-icon-jo'));
		$contact_country_validator->addAssociative('pt', array('icon' => 'flag-icon flag-icon-pt'));
		$contact_country_validator->addAssociative('lt', array('icon' => 'flag-icon flag-icon-lt'));
		$contact_country_validator->addAssociative('az', array('icon' => 'flag-icon flag-icon-az'));
		$contact_country_validator->addAssociative('us', array('icon' => 'flag-icon flag-icon-us'));
		$contact_country_validator->addAssociative('li', array('icon' => 'flag-icon flag-icon-li'));
		$contact_country_validator->addAssociative('jp', array('icon' => 'flag-icon flag-icon-jp'));
		$contact_country_validator->addAssociative('gi', array('icon' => 'flag-icon flag-icon-gi'));
		$contact_country_validator->addAssociative('ph', array('icon' => 'flag-icon flag-icon-ph'));
		$contact_country_validator->addAssociative('cn', array('icon' => 'flag-icon flag-icon-cn'));
		$contact_country_validator->addAssociative('dz', array('icon' => 'flag-icon flag-icon-dz'));
		$contact_country_validator->addAssociative('kz', array('icon' => 'flag-icon flag-icon-kz'));
		$contact_country_validator->addAssociative('id', array('icon' => 'flag-icon flag-icon-id'));
		$contact_country_validator->addAssociative('im', array('icon' => 'flag-icon flag-icon-im'));
		$contact_country_validator->addAssociative('sg', array('icon' => 'flag-icon flag-icon-sg'));
		$contact_country_validator->addAssociative('kw', array('icon' => 'flag-icon flag-icon-kw'));
		$contact_country_validator->addAssociative('hr', array('icon' => 'flag-icon flag-icon-hr'));
		$contact_country_validator->addAssociative('gb', array('icon' => 'flag-icon flag-icon-gb'));
		$contact_country_validator->addAssociative('ma', array('icon' => 'flag-icon flag-icon-ma'));
		$contact_country_validator->addAssociative('fo', array('icon' => 'flag-icon flag-icon-fo'));
		$contact_country_validator->addAssociative('cz', array('icon' => 'flag-icon flag-icon-cz'));
		$contact_country_validator->addAssociative('ie', array('icon' => 'flag-icon flag-icon-ie'));
		$contact_country_validator->addAssociative('qa', array('icon' => 'flag-icon flag-icon-qa'));
		$contact_country_validator->addAssociative('my', array('icon' => 'flag-icon flag-icon-my'));
		$contact_country_validator->addAssociative('ae', array('icon' => 'flag-icon flag-icon-ae'));
		$contact_country_validator->addAssociative('am', array('icon' => 'flag-icon flag-icon-am'));
		$contact_country_validator->addAssociative('rs', array('icon' => 'flag-icon flag-icon-rs'));
		$contact_country_validator->addAssociative('sm', array('icon' => 'flag-icon flag-icon-sm'));
		$contact_country_validator->addAssociative('no', array('icon' => 'flag-icon flag-icon-no'));
		$contact_country_validator->addAssociative('mk', array('icon' => 'flag-icon flag-icon-mk'));
		$contact_country_validator->addAssociative('hk', array('icon' => 'flag-icon flag-icon-hk'));
		$contact_country_validator->addAssociative('lk', array('icon' => 'flag-icon flag-icon-lk'));
		$contact_country_validator->addAssociative('vn', array('icon' => 'flag-icon flag-icon-vn'));
		$contact_country_validator->addAssociative('br', array('icon' => 'flag-icon flag-icon-br'));
		$contact_country_validator->addAssociative('va', array('icon' => 'flag-icon flag-icon-va'));
		$contact_country_validator->addAssociative('by', array('icon' => 'flag-icon flag-icon-by'));
		$contact_country_validator->addAssociative('jm', array('icon' => 'flag-icon flag-icon-jm'));
		$contact_country_validator->addAssociative('ar', array('icon' => 'flag-icon flag-icon-ar'));
		$contact_country_validator->addAssociative('ge', array('icon' => 'flag-icon flag-icon-ge'));
		$contact_country_validator->addAssociative('ad', array('icon' => 'flag-icon flag-icon-ad'));
		$contact_country_validator->addAssociative('py', array('icon' => 'flag-icon flag-icon-py'));
		$contact_country_validator->addAssociative('pr', array('icon' => 'flag-icon flag-icon-pr'));
		$contact_country_validator->addAssociative('kr', array('icon' => 'flag-icon flag-icon-kr'));
		$contact_country_validator->addAssociative('mx', array('icon' => 'flag-icon flag-icon-mx'));
		$contact_country_validator->addAssociative('cl', array('icon' => 'flag-icon flag-icon-cl'));
		$contact_country_validator->addAssociative('md', array('icon' => 'flag-icon flag-icon-md'));
		$contact_country_validator->addAssociative('au', array('icon' => 'flag-icon flag-icon-au'));
		$contact_country_validator->addAssociative('mt', array('icon' => 'flag-icon flag-icon-mt'));
		$contact_country_validator->addAssociative('de', array('icon' => 'flag-icon flag-icon-de'));
		$contact_country_validator->addAssociative('th', array('icon' => 'flag-icon flag-icon-th'));
		$contact_country_validator->addAssociative('cy', array('icon' => 'flag-icon flag-icon-cy'));
		$contact_country_validator->addAssociative('za', array('icon' => 'flag-icon flag-icon-za'));
		$contact_country_validator->addAssociative('mm', array('icon' => 'flag-icon flag-icon-mm'));
		$contact_country_validator->addAssociative('sa', array('icon' => 'flag-icon flag-icon-sa'));
		$contact_country_validator->addAssociative('ba', array('icon' => 'flag-icon flag-icon-ba'));
		$contact_country_validator->addAssociative('np', array('icon' => 'flag-icon flag-icon-np'));
		$contact_country_validator->addAssociative('ua', array('icon' => 'flag-icon flag-icon-ua'));
		$contact_country_validator->addAssociative('sz', array('icon' => 'flag-icon flag-icon-sz'));
		$contact_country_validator->addAssociative('dk', array('icon' => 'flag-icon flag-icon-dk'));
		$contact_country_validator->addAssociative('lu', array('icon' => 'flag-icon flag-icon-lu'));
		$contact_country_validator->addAssociative('al', array('icon' => 'flag-icon flag-icon-al'));
		$contact_country_validator->addAssociative('tw', array('icon' => 'flag-icon flag-icon-tw'));
		$contact_country_validator->addAssociative('bg', array('icon' => 'flag-icon flag-icon-bg'));
		$contact_country_validator->addAssociative('mc', array('icon' => 'flag-icon flag-icon-mc'));
		$contact_country_validator->addAssociative('pa', array('icon' => 'flag-icon flag-icon-pa'));
		$contact_country_validator->addAssociative('tn', array('icon' => 'flag-icon flag-icon-tn'));
		$contact_country_validator->addAssociative('ca', array('icon' => 'flag-icon flag-icon-ca'));
		$contact_country_validator->addAssociative('eg', array('icon' => 'flag-icon flag-icon-eg'));
		$contact_country_validator->addAssociative('nz', array('icon' => 'flag-icon flag-icon-nz'));
		$contact_country_validator->addAssociative('om', array('icon' => 'flag-icon flag-icon-om'));
		$contact_country_validator->addAssociative('ly', array('icon' => 'flag-icon flag-icon-ly'));
		$contact_country_validator->addAssociative('is', array('icon' => 'flag-icon flag-icon-is'));
		$contact_country_validator->addAssociative('gr', array('icon' => 'flag-icon flag-icon-gr'));
		$contact_country_validator->addAssociative('hu', array('icon' => 'flag-icon flag-icon-hu'));
		$contact_country_validator->addAssociative('ru', array('icon' => 'flag-icon flag-icon-ru'));
		$contact_country_validator->addAssociative('me', array('icon' => 'flag-icon flag-icon-me'));
		$contact_country_validator->addAssociative('xk', array('icon' => 'flag-icon flag-icon-xk'));
		$contact_country_validator_keys = $contact_country_validator->getKeys();
		$contact_country_validator_keys = '/^(?!(' . implode('|', $contact_country_validator_keys) . '))\w+$/i';

		$contact_name = $this->addField('contact_name');
		$contact_name_pattern = Validation::factory('ShowString');
		$contact_name_pattern->setMin(1);
		$contact_name_pattern->setMax(255);
		$contact_name_pattern->setClosureMagic(function (Field $field) {
			$field_safemode = $field->getSafeMode();
			$field_readmode = $field->getReadMode();
			if (true === $field_readmode
				|| $field_safemode !== true) return true;

			$field_value = $field->getValue();
			$field_value = static::viesNameDogma($field_value);
			$field_value = mb_strtoupper($field_value);
			$field->setValue($field_value, Field::OVERRIDE);

			return true;
		});
		$contact_name->setPatterns($contact_name_pattern);
		$contact_name->getRow()->setName('identity');
		$contact_name->setRequired();

		$contact_vat = $this->addField('contact_vat');
		$contact_vat_pattern = Validation::factory('Regex');
		$contact_vat_pattern->setRegex($contact_country_validator_keys);
		$contact_vat_pattern->setMin(4);
		$contact_vat_pattern->setMax(24);
		$contact_vat_pattern->setClosureMagic(function (Field $field) {
			$field_safemode = $field->getSafeMode();
			$field_readmode = $field->getReadMode();
			if (true === $field_readmode
				|| $field_safemode !== true) return true;

			$field_value = $field->getValue();
			$field_value = trim($field_value);
			$field->setValue($field_value, Field::OVERRIDE);

			return true;
		});
		$contact_vat->setPatterns($contact_vat_pattern);
		$contact_vat->getRow()->setName('identity');

		$contact_address = $this->addField('contact_address');
		$contact_address_pattern = Validation::factory('ShowString');
		$contact_address_pattern->setMin(1);
		$contact_address_pattern->setMax(128);
		$contact_address->setPatterns($contact_address_pattern);
		$contact_address->getRow()->setName('address');
		$contact_address->addUniqueness(static::LOCATION);
		$contact_address->setRequired();

		$contact_address_number_type = $this->addField('contact_address_number_type');
		$contact_address_number_type_pattern = Validation::factory('Enum');
		$contact_address_number_type_pattern->addAssociative('civic');
		$contact_address_number_type_pattern->addAssociative('km');
		$contact_address_number_type->setPatterns($contact_address_number_type_pattern);
		$contact_address_number_type->getRow()->setName('address');
		$contact_address_number_type->addUniqueness(static::LOCATION);
		$contact_address_number_type->setRequired();
		
		$contact_address_number = $this->addField('contact_address_number');
		$contact_address_number_pattern = Validation::factory('ShowString');
		$contact_address_number_pattern->setMin(1);
		$contact_address_number->setPatterns($contact_address_number_pattern);
		$contact_address_number->getRow()->setName('address');
		$contact_address_number->addUniqueness(static::LOCATION);

		$contact_city = $this->addField('contact_city');
		$contact_city_pattern = Validation::factory('ShowString');
		$contact_city_pattern->setMin(1);
		$contact_city_pattern->setMax(64);
		$contact_city->setPatterns($contact_city_pattern);
		$contact_city->getRow()->setName('peculiarity');
		$contact_city->addUniqueness(static::LOCATION);
		$contact_city->setRequired();

		$contact_province = $this->addField('contact_province');
		$contact_province_pattern = Validation::factory('ShowString');
		$contact_province_pattern->setMin(1);
		$contact_province_pattern->setMax(64);
		$contact_province->setPatterns($contact_province_pattern);
		$contact_province->getRow()->setName('peculiarity');
		$contact_province->addUniqueness(static::LOCATION);

		$contact_zip = $this->addField('contact_zip');
		$contact_zip_pattern = Validation::factory('ShowString');
		$contact_zip_pattern->setMin(1);
		$contact_zip_pattern->setMax(24);
		$contact_zip->setPatterns($contact_zip_pattern);
		$contact_zip->getRow()->setName('peculiarity');
		$contact_zip->addUniqueness(static::LOCATION);
		$contact_zip->setRequired();

		$contact_country = $this->addField('contact_country');
		$contact_country->setPatterns($contact_country_validator);
		$contact_country->getRow()->setName('peculiarity');
		$contact_country->addUniqueness(static::LOCATION);
		$contact_country->setRequired();

		$contact_position = $this->addField('contact_position');
		$contact_position_pattern_point = $this->getPoint();
        $contact_position_pattern = Validation::factory('Matrioska', $contact_position_pattern_point);
		$contact_position->setPatterns($contact_position_pattern);
		$contact_position->setProtected();
		$contact_position->setRequired();

		$contact_satisfaction = $this->addField('contact_satisfaction');
		$contact_satisfaction_pattern = Validation::factory('Enum');
		$contact_satisfaction_pattern->addAssociative('yes');
		$contact_satisfaction_pattern->addAssociative('no');
		$contact_satisfaction->setPatterns($contact_satisfaction_pattern);
		$contact_satisfaction->setRequired();

		$contact_domain = $this->addField('contact_domain');
		$contact_domain_pattern = Validation::factory('Chip');
		$contact_domain->setPatterns($contact_domain_pattern);
		
		$contact_vies = $this->addField('contact_vies');
		$contact_vies_pattern = Validation::factory('ShowBool', false);
		$contact_vies->setPatterns($contact_vies_pattern);
		$contact_vies->setProtected();

		$contact_google = $this->addField('contact_google');
		$contact_google_pattern = Validation::factory('ShowBool', false);
		$contact_google->setPatterns($contact_google_pattern);
		$contact_google->setProtected();
	}

	protected function after() : void
	{
		$contact_created = $this->addField('contact_created');
		$contact_created_validator = Validation::factory('DateTime', null, 'd-m-Y H:i:s', 'Y-m-d H:i:s.u');
		$contact_created->setPatterns($contact_created_validator);
		$contact_created->setProtected();

		$contact_updated = $this->addField('contact_updated');
		$contact_updated_validator = Validation::factory('DateTime', null, 'd-m-Y H:i:s', 'Y-m-d H:i:s.u');
		$contact_updated->setPatterns($contact_updated_validator);
		$contact_updated->setProtected();
	}

	protected function setPoint(Point $point) : void
	{
		$this->point = $point;
	}
}
