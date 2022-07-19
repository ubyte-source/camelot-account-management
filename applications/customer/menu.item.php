<?PHP

namespace applications\customer;

use Menu\Base as Menu;

return Menu::create(function (Menu $item) {
    $item->getField('icon')->setValue('business');
});
