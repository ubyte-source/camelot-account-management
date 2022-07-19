<?PHP

namespace configurations;

use Knight\Lock;

use IAM\Configuration as Define;

final class IAM
{
	use Lock;

	const PARAMETERS = [
		// application basename set on identity and access management
		Define::CONFIGURATION_APPLICATION_BASENAME => 'account',
		// application key set on identity and access management
		Define::CONFIGURATION_APPLICATION_KEY => '76425487',
		// server endpoint to connect identity and access management
		Define::CONFIGURATION_HOST_IAM => 'https://login.energia-europa.com/',
		// cookie name for this site
		Define::CONFIGURATION_COOKIE_NAME => 'alive',
		// policy separator defined on identity and access management
		Define::CONFIGURATION_POLICY_SEPARATOR => '/'
	];
}
