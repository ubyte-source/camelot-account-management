<?PHP

namespace configurations;

use Knight\Lock;

final class Vies
{
	use Lock;

	const VIES_SERVER = 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';
	const VIES_FIELDS = [
		'contact_country' => 'countryCode',
		'contact_vat' => 'vatNumber'
	];
	const VIES_DOGMA = [
		's.s.' => '/\b(s\W+?s\W+?)\b/',
		's.n.c.' => '/\b(s\W+?n\W+?c\W+?)\b/',
		's.a.s.' => '/\b(s\W+?a\W+?s\W+?)\b/',
		's.p.a.' => '/\b(s\W+?p\W+?a\W+?)\b/',
		's.r.l.' => '/\b(s\W+?r\W+?l\W+?)\b/',
		's.s.r.l.' => '/\b(s\W+?s\W+?r\W+?l\W+?)\b/',
		's.r.l.s.' => '/\b(s\W+?r\W+?l\W+?s\W+?)\b/',
		's.a.p.a.' => '/\b(s\W+?a\W+?p\W+?a\W+?)\b/'
	];
}
