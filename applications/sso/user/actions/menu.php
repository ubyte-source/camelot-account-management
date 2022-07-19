<?PHP

namespace applications\sso\user\actions;

use stdClass;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Request;
use Knight\armor\Navigator;

use Menu\Base as Menu;

$filter = IAMConfiguration::getApplicationBasename();
$policy_separator = IAMConfiguration::getPolicySeparator();

$navigator = Request::post('navigator');
if (null !== $navigator) {
    $application = explode($policy_separator, $navigator);
    $application = array_filter($application);
    $application = reset($application);
    if (false !== $application) $filter .=  $policy_separator . $application;

    $header = APPLICATIONS . $application;
    $header = Menu::getItemFromPath($header);
    if (null !== $header) {
        $header_output = $header->output();
        Output::concatenate('header', $header_output);
    }
}

$response = [];
$policies = Sso::getPolicies($filter . $policy_separator . chr(37));
foreach ($policies as $policy_route) {
    $exploded = explode($policy_separator, $policy_route, Navigator::getDepth() + 3);
    $exploded_depth = Navigator::getDepth();
    if (false === array_key_exists($exploded_depth, $exploded)
        || $exploded[$exploded_depth] !== 'view') continue;

    $policy = array_pop($exploded);

    if ($filter !== IAMConfiguration::getApplicationBasename()) {
        $exploded_path = array_slice($exploded, 1, $exploded_depth - 1);
        $exploded_path_directory = implode(DIRECTORY_SEPARATOR, $exploded_path);
        $exploded_path_directory = APPLICATIONS . $exploded_path_directory;

        $module = end($exploded_path);
        if (false === array_key_exists($module, $response)) {
            $item = Menu::getItemFromPath($exploded_path_directory);
            if (null === $item) continue;

            $application = reset($exploded_path);

            $name = $item->getField('name');
            $name_value = $name->getValue();
            $name_value = chr(47) . $application . chr(47) . $name_value;
            $name->setValue($name_value);

            $response[$module] = $item;
        }

        $response[$module]->pushPolicies($policy);
    } else {
        $exploded_path = array_slice($exploded, 1, 1);
        $exploded_path_directory = implode(DIRECTORY_SEPARATOR, $exploded_path);
        $exploded_path_directory = APPLICATIONS . $exploded_path_directory;

        $application = end($exploded_path);
        if (false === array_key_exists($application, $response)) {
            $item = Menu::getItemFromPath($exploded_path_directory, true);
            if (null !== $item) $response[$application] = $item;
        }

        $module = $exploded_depth - 1;
        if (!array_key_exists($module, $exploded)
            || !array_key_exists($application, $response)) continue;

        $module = $response[$application]->getItem($exploded[$module]);
        if (null !== $module) $module->pushPolicies($policy);
    }
}

array_walk($response, function (Menu &$menu) {
    $menu_href = $menu->getHref();
    if (null !== $menu_href) {
        $menu->getField('href')->setValue($menu_href);
        $menu = $menu->output();
    }
});

$response = array_values($response);
$response = array_filter($response);

Output::concatenate(Output::APIDATA, $response);
Output::print(true);
