<?PHP

namespace applications\customer\contact;

use Menu\Base as Menu;

return Menu::create(function (Menu $item) {
    $item->getField('icon')->setValue('group');
    $item->setViewsFavorite('read');
});
