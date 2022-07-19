<?PHP

namespace configurations;

use Knight\Lock;

use Knight\armor\Cipher as Define;

final class Cipher
{
	use Lock;

	const PARAMETERS = [
		// cipher method encrypt
		Define::CONFIGURATION_METHOD => 'aes-256-cbc'
	];
}
