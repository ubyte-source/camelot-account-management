<?PHP

namespace configurations;

use Knight\Lock;

use Knight\armor\Curl as Define;
use Knight\armor\Navigator;

final class Curl
{
	use Lock;

	const PARAMETERS = [
		// curl options
		Define::CONFIGURATION_CURL_OPTIONS => [
			CURLOPT_USERAGENT => Navigator::HTTP_USER_AGENT,
			CURLOPT_TIMEOUT => 4,
			CURLOPT_CONNECTTIMEOUT => 8,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 0,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_FORBID_REUSE => 1
		]
	];
}
