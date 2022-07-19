<?PHP

namespace applications\sso;

use Menu\Base as Menu;

return Menu::create(function (Menu $item) {
    $item->getField('icon')->setValue('vpn_key');
});
