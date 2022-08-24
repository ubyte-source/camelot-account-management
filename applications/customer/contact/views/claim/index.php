<?PHP

namespace applications\customer\contact\views\claim;

use configurations\Navigator as Configuration;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Composer;
use Knight\armor\Language;
use Knight\armor\Navigator;

use applications\sso\user\database\Vertex as User;
use applications\customer\contact\forms\Claim;

$navigator = Navigator::get();

$policies_application_basename = IAMConfiguration::getApplicationBasename();
$policies = Sso::getPolicies(
    $policies_application_basename . '/' . '%',
    'iam/user/view/upsert',
    'iam/user/action/update/me'
);

$claim = new Claim();
$claim = $claim->human();

$whoami = Sso::getWhoami();

Language::dictionary(__file__);
$translate = Language::getTextsNamespaceName(__namespace__);
?>

<!-- JS & CSS Layout Files -->

<link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>button/<?= Composer::getLockVersion('widget/button'); ?>/base.css">
<script src="<?= Configuration::WIDGETS; ?>button/<?= Composer::getLockVersion('widget/button'); ?>/base.js"></script>

<link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>nav/<?= Composer::getLockVersion('widget/nav'); ?>/base.css">
<script src="<?= Configuration::WIDGETS; ?>nav/<?= Composer::getLockVersion('widget/nav'); ?>/base.js"></script>

<link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>header/<?= Composer::getLockVersion('widget/header'); ?>/base.css">
<script src="<?= Configuration::WIDGETS; ?>header/<?= Composer::getLockVersion('widget/header'); ?>/base.js"></script>

<link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>form/<?= Composer::getLockVersion('widget/form'); ?>/base.css">
<script src="<?= Configuration::WIDGETS; ?>form/<?= Composer::getLockVersion('widget/form'); ?>/base.js"></script>

<link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>modal/<?= Composer::getLockVersion('widget/modal'); ?>/base.css">
<script src="<?= Configuration::WIDGETS; ?>modal/<?= Composer::getLockVersion('widget/modal'); ?>/base.js"></script>

<script type="text/javascript">
    window.page.setNavigator(<?= Output::json($navigator) ?>);
    window.page.setTranslate(<?= Output::json($translate) ?>);
    window.page.setUserPolicies(<?= Output::json($policies); ?>);
    window.page.application = '<?= IAMConfiguration::getApplicationBasename(); ?>';
    window.page.user = <?= Output::json($whoami); ?>;
    window.page.user.setting = <?= Output::json($setting) ?>;
    window.page.tables = {
        claim: <?= Output::json($claim); ?>,
    };
</script>

<!-- JS & CSS Plugins Files -->

<script src="<?= Configuration::WIDGETS; ?>form/<?= Composer::getLockVersion('widget/form'); ?>/plugins/row/action.js"></script>
<script src="<?= Configuration::WIDGETS; ?>form/<?= Composer::getLockVersion('widget/form'); ?>/plugins/matrioska.js"></script>

<link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>form/<?= Composer::getLockVersion('widget/form'); ?>/plugins/tooltip/tooltip.css">
<script src="<?= Configuration::WIDGETS; ?>form/<?= Composer::getLockVersion('widget/form'); ?>/plugins/tooltip/tooltip.js"></script>

<link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>form/<?= Composer::getLockVersion('widget/form'); ?>/plugins/textarea/textarea.css">
<script src="<?= Configuration::WIDGETS; ?>form/<?= Composer::getLockVersion('widget/form'); ?>/plugins/textarea/textarea.js"></script>

<!-- CSS View -->

<link rel="stylesheet" type="text/css" href="/cdn/applications/customer/contact/views/claim/1.0.0/css/base.css">

<!-- JS View -->

<script src="/cdn/applications/customer/contact/views/claim/1.0.0/js/base.js"></script>
