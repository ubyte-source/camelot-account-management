<?PHP

namespace applications\sso\user;

use Menu\Base as Menu;

return Menu::create(function (Menu $item) {
    $item->getField('icon')->setValue('contact_phone');
    $item->setViewsFavorite('read');
});
