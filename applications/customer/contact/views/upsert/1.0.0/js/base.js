(function (window) {

    'use strict';

    window.Form.Plugin.Dropdown.voidText = function () {
        return window.page.getTranslate('form.dropdown.void');
    }
    window.Infinite.Plugin.Setting.Search.placeholder = function () {
        return window.page.getTranslate('infinite.setting.placeholder');
    }
    window.Infinite.Plugin.Setting.Search.NotFound.text = function () {
        return window.page.getTranslate('infinite.no_result');
    }

    let pathname = window.location.pathname.split(String.fromCharCode(47)), widgets = window.page.getWidgets();
    window.reference = pathname.slice(4);

    window.elements = {};
    window.elements.content = document.createElement('div');
    window.elements.content.id = 'content';
window.elements.content.className = 'widget-infinite-enable-print';

    window.elements.main = document.createElement('div');
    window.elements.main.id = 'main';
window.elements.main.className = 'widget-infinite-enable-print';
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
    widgets.header.setTitle(window.page.getTranslate('header.app_name'));

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

    let menu = '/api/sso/user/menu'
        + String.fromCharCode(63)
        + 'timestamp'
        + String.fromCharCode(61)
        + Date.now()
    widgets.menu = new Menu();
    widgets.menu.setNearElement(window.elements.main);
    widgets.menu.setRequestUrl(menu);
    widgets.menu.setNavigator(window.page.getNavigator().join('/'));
    widgets.menu.request(function (response) {
        if (response.hasOwnProperty('header')) this.setHeader(response.header);
        if (false === response.hasOwnProperty('data')) return;
        this.pushModules(response.data);

        let pathname = window.location.pathname.split(/[\\\/]/);
        if (pathname.hasOwnProperty(2)) {
            let list = this.getList();
            for (let item = 0; item < list.length; item++) {
                let href = list[item].out().getAttribute('href');
                if (href === null) continue;

                let split = href.split(/[\\\/]/);
                if (split.hasOwnProperty(2)
                    && pathname[2] === split[2]) list[item].out().classList.add('active');
            }
        }
    });

    window.page.addHTMLElement(widgets.menu.out());

    var title = window.reference.length === 0 ? window.page.getTranslate('nav.add') : window.page.getTranslate('nav.edit'),
        back = '/customer/contact/read';
    widgets.nav = new Nav();
    widgets.nav.setBack(back);
    widgets.nav.setReturnButton('arrow_back');
    widgets.nav.setTitle(title);

    window.elements.main.appendChild(widgets.nav.out());

    window.elements.actions = document.createElement('div');
    window.elements.actions.className = 'buttons-form';

    widgets.tabs = new Tabs();
    widgets.tabs.action = 'data-use-action';
    widgets.tabs.setEventShow(function (ev) {
        let action = Tabs.closestAttribute(ev.target, widgets.tabs.action), method = 1 === parseInt(action) ? 'remove' : 'add';
        window.elements.actions.classList[method]('hide');
    });
    window.elements.content.appendChild(widgets.tabs.out());

    let create = '/api/customer/contact/create'
        + String.fromCharCode(63)
        + 'timestamp'
        + String.fromCharCode(61)
        + Date.now();
    widgets.form = new Form();
    widgets.form.setRequestUrl(create);
    for (let item = 0; item < window.page.tables.upsert.fields.length; item++)
        widgets.form.addInput(window.page.tables.upsert.fields[item]);

    widgets.form.set('owner', window.page.user);

    let element = widgets.form.out(), business = widgets.tabs.addItem(window.page.getTranslate(window.page.tables.upsert.collection), element, 'material-icons business').out();

    business.setAttribute(widgets.tabs.action, 1);
    window.elements.content.appendChild(element);
    window.elements.content.appendChild(window.elements.actions);

    let submit = new Button(), icon = window.reference.length !== 0 ? 'save' : 'add';
    submit.getIcon().set(icon);
    submit.setText(window.reference.length !== 0 ? window.page.getTranslate('button.save') : window.page.getTranslate('button.add'));
    submit.onClick(function () {
        let text = window.page.getTranslate('button.loader');
        this.getLoader().apply(text);

        widgets.form.request(function (response) {
            submit.getLoader().remove();
            if (response.hasOwnProperty('id_contact')) window.location = '/customer/contact/upsert/' + response.id_contact;
        });
    });

    window.elements.actions.appendChild(submit.out());

    business.click();

    window.buttons = [];
    window.choosed = [];

    let related = document.createElement('div'), site = '/api/customer/contact/read/' + window.reference[0];
    widgets.upsert = new Infinite();
    widgets.upsert.setOptionSetting(window.page.user.setting.upsert);
    widgets.upsert.setOptionStructure(window.page.tables.upsert.fields);
    widgets.upsert.setContainer(element);
    widgets.upsert.setRequestUrl(site);
    widgets.upsert.setResponseKey('data');
    widgets.upsert.setResponseUnique('_key');
    widgets.upsert.getNotice().setTextEmpty(window.page.getTranslate('infinite.no_result'));
    widgets.upsert.request();
    widgets.upsert.addEventSelect(new Infinite.Event(Infinite.Event.always(), function () {
        window.choosed = this.getTR().getBody().getChecked();
        window.negotiateButtonStatus.call();
    }));

    widgets.action = new Nav();
    related.appendChild(widgets.action.out());
    related.appendChild(widgets.upsert.out());

    widgets.tabs.addItem(window.page.getTranslate('contact_reference'), related, 'material-icons policy').out().setAttribute(widgets.tabs.action, 0);
    window.elements.content.appendChild(related);

    if (window.page.checkPolicy('iam/user/action/read')) {
        let form_sharing_container = document.createElement('div'),
            form_sharing = widgets.form.findContainer('owner'),
            form_shared = widgets.form.findContainer('share');

        widgets.tabs.addItem(window.page.getTranslate('tabs.sharing'), form_sharing_container, 'material-icons share').out();

        form_sharing_container.appendChild(form_sharing.getRow().out());
        form_sharing_container.appendChild(form_shared.getRow().out());

        window.elements.content.appendChild(form_sharing_container);
    }

    if (window.page.checkPolicy(window.page.application + '/customer/contact/action/update')) {
        let edit = new Button();
        edit.addStyle('flat');
        edit.getIcon().set('edit');
        edit.setText(window.page.getTranslate('nav.buttons.edit'));
        edit.appendAttributes({
            'data-selected-min': 1
        });
        edit.onClick(function () {
            window.location = '/customer/contact/upsert/' + window.choosed[0];
        });
        window.buttons.push(edit);
    }

    widgets.modal = new window.Page.Widget.Organizer();
    widgets.modal = new Modal();

    let notice = document.createElement('p'), instructions = document.createElement('p');
    notice.appendChild(document.createTextNode(window.page.getTranslate('modal.warning.notice')));
    widgets.modal.addContent(notice);

    widgets.modal.addContent(instructions);
    widgets.modal.setActionShow(function () {
        let title = window.page.getTranslate('modal.warning.title'), parsed = title.replace(/\$0/, window.choosed.length);
        widgets.modal.setTitle(parsed);
    });

    window.page.addHTMLElement(widgets.modal.out());

    let dissociate = new Button();
    dissociate.addStyle('flat red');
    dissociate.setText(window.page.getTranslate('buttons.dissociate'));
    dissociate.onClick(function () {
        let preloader = widgets.upsert.getPreloader().status();
        if (preloader === true) return;

        let text = window.page.getTranslate('buttons.loader');
        this.getLoader().apply(text);

        window.requests = [];
        for (let x in window.choosed) {
            let xhr = new WXmlHttpRequest(),
                dissociate = '/api/customer/contact/dissociate'
                    + String.fromCharCode(47)
                    + encodeURIComponent(window.choosed[x])
                    + String.fromCharCode(63)
                    + 'timestamp'
                    + String.fromCharCode(61)
                    + Date.now();

            window.requests.push(xhr);
            xhr.setRequestUrl(dissociate);
            xhr.setCallbackSuccess(function () {
                let parser = document.createElement('a');
                parser.href = this.responseURL;

                let split = parser.pathname.split(String.fromCharCode(47)), key = split[split.length - 1] === 'dissociate' ? null : split[split.length - 1];
                console.log(parser.href, split)
                widgets.upsert.getBody().removeTR(key);
                window.choosed = window.choosed.filter(function (element) {
                    return element != key;
                });
                window.negotiateButtonStatus.call();
            });
        }
        for (let item = 0; item < window.requests.length; item++)
            window.requests[item].request();

        delete window.requests;

        setTimeout(function (button, modal) {
            button.getLoader().remove();
            modal.hide();
        }, 2048, this, widgets.modal);
    });

    let cancel = new Button();
    cancel.addStyle('flat');
    cancel.setText(window.page.getTranslate('buttons.cancel'));
    cancel.onClick(function () {
        widgets.modal.hide();
    });

    let container = document.createElement('div');
    container.className = 'buttons-form';
    container.appendChild(dissociate.out());
    container.appendChild(cancel.out());

    widgets.modal.addContent(container);

    if (window.page.checkPolicy(window.page.application + '/customer/contact/action/dissociate')) {
        let button = new Button();
        button.addStyle('flat');
        button.getIcon().set('delete');
        button.setText(window.page.getTranslate('nav.buttons.dissociate'));
        button.appendAttributes({
            'data-selected-min': 1
        });
        button.onClick(function () {
            widgets.modal.show();
        });
        window.buttons.push(button);
    }

    let infinite_setting = widgets.upsert.getSetting();
    if (infinite_setting !== null && window.page.checkPolicy(window.page.application + '/sso/user/action/save/widget/setting')) {
        let api = '/api/sso/user/save'
            + String.fromCharCode(63)
            + 'timestamp'
            + String.fromCharCode(61)
            + Date.now();
        widgets.sidepanel = new SidePanel();
        widgets.sidepanel.setTitle(window.page.getTranslate('sidepanel.title'));
        widgets.sidepanel.pushContent(infinite_setting.out());
        widgets.sidepanel.setActionShow(function () {
            window.setting.setText(window.page.getTranslate('nav.buttons.hide_settings'))
        });
        widgets.sidepanel.setActionHide(function () {
            window.setting.setText(window.page.getTranslate('nav.buttons.show_settings'))
        });

        infinite_setting.setRequestUrl(api);
        infinite_setting.setHardcode('widget', 'upsert');
        infinite_setting.setHardcode('application', window.page.getApplication());
        infinite_setting.setHardcode('module', window.page.getModule());
        infinite_setting.setHardcode('view', window.page.getView());

        related.appendChild(widgets.sidepanel.out());
    }

    for (let item = 0, action = widgets.action.getColumn(8); item < window.buttons.length; item++)
        action.addContent(window.buttons[item].out());

    if (window.page.checkPolicy(window.page.application + '/sso/user/action/save/widget/setting')) {
        window.setting = new Button();
        window.setting.addStyle('flat');
        window.setting.getIcon().set('settings');
        window.setting.setText(window.page.getTranslate('nav.buttons.show_settings'));
        window.setting.onClick(function (event) {
            widgets.sidepanel.toggle(event);
            window.setting.getText();
        });
        widgets.action.getColumn(10);
        widgets.action.getColumn(6).addContent(window.setting.out());
    }

    window.negotiateButtonStatus = function () {
        for (let item = 0; item < window.buttons.length; item++) {
            let button = window.buttons[item].out();
            let min = button.hasAttribute('data-selected-min') ? parseInt(button.getAttribute('data-selected-min')) : 0;
            let max = button.hasAttribute('data-selected-max') ? parseInt(button.getAttribute('data-selected-max')) : null;
            if (min === 0 && max === null) continue;
            if (min > window.choosed.length || max !== null && window.choosed.length > max) {
                button.setAttribute('disabled', true);
                button.classList.add('disabled');
            } else {
                button.classList.remove('disabled');
                button.removeAttribute('disabled');
            }
        }
    };

    window.negotiateButtonStatus.call();

    if (window.reference.length === 0) return;

    let api_update = '/api/customer/contact/update'
        + String.fromCharCode(47)
        + encodeURIComponent(window.reference[0])
        + String.fromCharCode(63)
        + 'timestamp'
        + String.fromCharCode(61)
        + Date.now();
    widgets.form.setRequestUrl(api_update);

    let main = new WXmlHttpRequest(),
        api_detail = '/api/customer/contact/detail'
            + String.fromCharCode(47)
            + encodeURIComponent(window.reference[0])
            + String.fromCharCode(63)
            + 'timestamp'
            + String.fromCharCode(61)
            + Date.now();
    main.setRequestUrl(api_detail);
    main.setCallbackSuccess(function (response) {
        for (let item in response.data) widgets.form.set(item, response.data[item]);
    });
    main.request();

})(window);
