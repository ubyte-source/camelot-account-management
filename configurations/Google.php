<?PHP

namespace configurations;

use Knight\Lock;

final class Google
{
	use Lock;
    
	const GOOGLE_API = 'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=';
	const GOOGLE_API_KEY = ENVIRONMENT_GOOGLE_APIKEY_MAPS;
	const GOOGLE_API_ADDRESS = [
		'contact_country',
		'contact_address',
		'contact_address_number_type',
		'contact_address_number',
		'contact_city',
		'contact_province',
		'contact_zip'
	];
}
