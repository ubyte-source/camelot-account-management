<?PHP

namespace configurations;

use Knight\Lock;

use Knight\armor\Language as Define;

final class Language
{
	use Lock;

	const PARAMETERS = [
		// default platform speech
		Define::CONFIGURATION_DEFAULT_SPEECH => 'en'
	];
}
