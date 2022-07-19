<?PHP

namespace configurations;

use Knight\Lock;

use Knight\armor\Output as Define;

final class Output
{
	use Lock;

	const PARAMETERS = [
		// default output json option
		// Define::CONFIGURATION_JSON_OPTION => JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES
	];
}
