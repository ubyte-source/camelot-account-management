<?PHP

namespace configurations;

use Knight\Lock;

use ArangoDBClient\ConnectionOptions;
use ArangoDBClient\UpdatePolicy;

defined('CONFIGURATION_ARANGODB_CONNECTION_TYPE') or define('CONFIGURATION_ARANGODB_CONNECTION_TYPE', 'Basic');

final class ArangoDB
{
	use Lock;

	const PARAMETERS = [ 
		// database name
		ConnectionOptions::OPTION_DATABASE => ENVIRONMENT_ARANGODB_DATABASE,
		// server endpoint to connect
		ConnectionOptions::OPTION_ENDPOINT => ENVIRONMENT_ARANGODB_ENDPOINT,
		// authorization type to use (currently supported: 'Basic')
		ConnectionOptions::OPTION_AUTH_TYPE => CONFIGURATION_ARANGODB_CONNECTION_TYPE,
		// user for basic authorization
		ConnectionOptions::OPTION_AUTH_USER => ENVIRONMENT_ARANGODB_USERNAME,
		// password for basic authorization
		ConnectionOptions::OPTION_AUTH_PASSWD => ENVIRONMENT_ARANGODB_PASSWORD,
		// connection persistence on server. can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
		ConnectionOptions::OPTION_CONNECTION => 'Keep-Alive',
		// connect timeout in seconds
		ConnectionOptions::OPTION_TIMEOUT => 4,
		// whether or not to reconnect when a keep-alive connection has timed out on server
		ConnectionOptions::OPTION_RECONNECT => true,
		// optionally create new collections when inserting documents
		ConnectionOptions::OPTION_CREATE => true,
		// optionally create new collections when inserting documents
		ConnectionOptions::OPTION_UPDATE_POLICY => UpdatePolicy::LAST,
	];
}
