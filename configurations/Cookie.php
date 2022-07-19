<?PHP

namespace configurations;

use Knight\Lock;

use Knight\armor\Cookie as Define;

final class Cookie
{
	use Lock;

	const PARAMETERS = [
		// cookie secure
		Define::CONFIGURATION_SECURE => Define::ACTIVE,
		// cookie https only
        Define::CONFIGURATION_HTTP_ONLY => Define::ACTIVE
    ];
}
