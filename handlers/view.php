<?PHP

namespace handlers;

use configurations\Navigator as Configuration;

use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Composer;
use Knight\armor\Navigator;

header("Content-type: text/html");

ob_start();

$navigator = Navigator::get();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

        <link href="https://fonts.googleapis.com" rel="dns-prefetch"/>
        <link href="<?= Configuration::WIDGETS; ?>" rel="dns-prefetch"/>

        <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
        
        <meta name="msapplication-TileColor" content="#da532c">
        <meta name="theme-color" content="#ffffff">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="x-ua-compatible" content="IE=edge">

        <title>Account Management</title>

        <!-- CSS Files External-->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

        <!-- CSS Files Layout -->

        <link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>asset/<?= Composer::getLockVersion('widget/asset'); ?>/css/reset.css">
        <link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>asset/<?= Composer::getLockVersion('widget/asset'); ?>/css/grids-min.css">
        <link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>asset/<?= Composer::getLockVersion('widget/asset'); ?>/css/grids-responsive-min.css">

        <!-- CSS Files General -->

        <link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>asset/<?= Composer::getLockVersion('widget/asset'); ?>/css/style.css">

        <!-- JS & CSS Waves -->

        <script src="<?= Configuration::WIDGETS; ?>asset/<?= Composer::getLockVersion('widget/asset'); ?>/js/waves.js"></script>
        <link rel="stylesheet" type="text/css" href="<?= Configuration::WIDGETS; ?>asset/<?= Composer::getLockVersion('widget/asset'); ?>/css/waves.min.css">

        <!-- JS Page -->

        <script src="<?= Configuration::WIDGETS; ?>asset/<?= Composer::getLockVersion('widget/asset'); ?>/js/page.js"></script>
        <script src="<?= Configuration::WIDGETS; ?>xhr/<?= Composer::getLockVersion('widget/xhr'); ?>/base.js"></script>

        <script type="text/javascript">
        window.page = new Page();
        window.page.setNavigator(<?= Output::json($navigator); ?>);
        window.page.host = '<?= Navigator::getUrl(); ?>';
        window.page.application = '<?= IAMConfiguration::getApplicationBasename(); ?>';
        window.page.iam = '<?= IAMConfiguration::getHost(); ?>';
        window.page.redirect = '<?= Navigator::RETURN_URL; ?>';
        </script>
        <?PHP Navigator::view(); ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
              window.page.out();
              Waves.attach('.btn', ['waves-button', 'waves-float', 'waves-light']);
              Waves.attach('.btn-flat', ['waves-button']);
              Waves.init();
        });
        document.addEventListener('click', function (ev) {
              window.page.close(ev);
        });
        </script>
    </head>
    <body></body>
</html>
<?php

$response = ob_get_contents();

ob_end_clean();

$response = preg_replace(['/[\r\n]+/', '/\s{2,}/'], '', $response);
exit($response);

?>
