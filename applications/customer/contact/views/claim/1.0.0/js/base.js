(function (window) {

    'use strict';

    let pathname = window.location.pathname.split(String.fromCharCode(47)), widgets = window.page.getWidgets();
    window.reference = pathname.slice(4);

    window.elements = {};
    window.elements.content = document.createElement('div');
    window.elements.content.id = 'content';

    window.elements.main = document.createElement('div');
    window.elements.main.id = 'main';
    window.page.addHTMLElement(window.elements.main);

    window.elements.wrapper = document.createElement('div');
    window.elements.wrapper.id = 'wrapper';
    window.elements.main.appendChild(window.elements.wrapper);

    window.elements.grid = document.createElement('div');
    window.elements.grid.className = 'pure-u-22-24 pure-u-lg-16-24 resize';
    window.elements.grid.appendChild(window.elements.content);

    window.elements.row = document.createElement('div');
    window.elements.row.className = 'pure-g';
    window.elements.row.appendChild(window.elements.grid);

    window.elements.wrapper.appendChild(window.elements.row);

    widgets.header = new Header();
    widgets.header.setUrl(window.page.iam);

    let profile = widgets.header.getProfile(), burger = profile.getMenu();
    profile.setUsername(window.page.user.email);
    profile.setImage(window.page.user.picture);

    if (window.page.checkPolicy('iam/user/view/upsert') && window.page.checkPolicy('iam/user/action/update/me')) {
        let account_label = window.page.getTranslate('header.buttons.my_account'), account = burger.addItem(account_label, 'account_circle');
        account.href = window.page.iam + 'iam/user/upsert/' + window.page.user._key;
    }

    let logout = window.page.getTranslate('header.buttons.logout');
    burger.addItem(logout, 'exit_to_app', function () {
        let xhr = new WXmlHttpRequest(),
            api = '/api/sso/user/gateway/iam/iam/user/logout'
                + String.fromCharCode(63)
                + 'timestamp'
                + String.fromCharCode(61)
                + Date.now();
        xhr.setRequestUrl(api);
        xhr.setCallbackSuccess(function (response) {
            if (response.hasOwnProperty('data')) document.location.href = response.return_url;
        });
        xhr.request();
    });

    window.page.addHTMLElement(widgets.header.out());

    widgets.nav = new Nav();
    widgets.nav.setTitle(window.page.getTranslate('nav.info'));

    window.elements.main.appendChild(widgets.nav.out());

    window.elements.description = document.createElement('div');
    let node = document.createTextNode(window.page.getTranslate('description.howto'));
    window.elements.description.appendChild(node);
    window.elements.content.appendChild(window.elements.description);

    widgets.modal = new Modal();

    let notice_node = document.createTextNode(window.page.getTranslate('modal.info.notice')), notice_paragraph = document.createElement('p');
    notice_paragraph.appendChild(notice_node);
    widgets.modal.addContent(notice_paragraph);

    let picture = document.createElement('p'),
        image = document.createElement('img');
    image.src = 'https://public.energia-europa.com/image/help-qr-example.png';
    picture.id = "img";
    picture.appendChild(image)
    widgets.modal.addContent(picture);

    widgets.modal.setActionShow(function () {
        widgets.modal.setTitle(window.page.getTranslate('modal.info.title'));
    });
    window.page.elements.push(widgets.modal.out());

    window.elements.anchor = document.createElement('a');
    let text = document.createTextNode(window.page.getTranslate('description.anchor'));
    window.elements.anchor.appendChild(text);
    window.elements.anchor.addEventListener('click', function () {
        widgets.modal.show();
    })
    window.elements.description.appendChild(window.elements.anchor);

    window.elements.actions = document.createElement('div');
    window.elements.actions.className = 'buttons-form';

    let claim = '/api/customer/contact/claim'
        + String.fromCharCode(63)
        + 'timestamp'
        + String.fromCharCode(61)
        + Date.now();
    widgets.form = new Form();
    widgets.form.setRequestUrl(claim);
    widgets.form.setCallbackSuccess(function () {
        let data = widgets.form.get();
        window.location = 'https://engine.energia-europa.com/machine/device/show'
            + String.fromCharCode(47)
            + data.device_serial;
    });

    for (let item = 0; item < window.page.tables.claim.fields.length; item++) {
        widgets.form.addInput(window.page.tables.claim.fields[item]);
        let parameter = Page.getUrlParameter(window.page.tables.claim.fields[item].name);
        if (parameter)
            widgets.form.set(window.page.tables.claim.fields[item].name, parameter);
    }

    window.elements.content.appendChild(widgets.form.out());
    window.elements.content.appendChild(window.elements.actions);
    let submit = new Button();
    submit.setText(window.page.getTranslate('button.procede'));
    submit.getIcon().set('navigate_next');
    submit.onClick(function () {
        let text = window.page.getTranslate('button.loader');
        this.getLoader().apply(text);

        widgets.form.request();
    });

    window.elements.actions.appendChild(submit.out());

})(window);