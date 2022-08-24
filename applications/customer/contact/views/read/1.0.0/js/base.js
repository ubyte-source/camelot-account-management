
(function (window) {

    'use strict';

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
            if (response.hasOwnProperty('return_url')) document.location.href = response.return_url;
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

    let title = window.page.getTranslate('nav.title'),
        back = '/';
    widgets.nav = new Nav();
    widgets.nav.setBack(back);
    widgets.nav.setReturnButton('arrow_back');
    widgets.nav.setTitle(title);

    window.elements.main.appendChild(widgets.nav.out());

    window.buttons = [];
    window.choosed = [];

    let wrapper = document.createElement('div');
    wrapper.className = 'table-wrapper';
    window.elements.content.appendChild(wrapper);

    widgets.read = new Infinite();
    widgets.read.setOptionSetting(window.page.user.setting.read);
    widgets.read.setOptionStructure(window.page.tables.read.fields);
    widgets.read.setContainer(window.elements.content);
    widgets.read.setRequestUrl('/api/customer/contact/read');
    widgets.read.setResponseKey('data');
    widgets.read.setResponseUnique('_key');
    widgets.read.getNotice().setTextEmpty(window.page.getTranslate('infinite.no_result'));
    widgets.read.request();
    widgets.read.addEventSelect(new Infinite.Event(Infinite.Event.always(), function () {
        window.choosed = this.getTR().getBody().getChecked();
        window.negotiateButtonStatus.call();
    }));

    wrapper.appendChild(widgets.read.out());

    if (window.page.checkPolicy(window.page.application + '/customer/contact/action/create')) {
        let add = new Button();
        add.addStyle('flat');
        add.getIcon().set('add');
        add.setText(window.page.getTranslate('nav.buttons.add'));
        add.onClick(function () {
            window.location = '/customer/contact/upsert';
        });
        window.buttons.push(add);
    }

    if (window.page.checkPolicy(window.page.application + '/customer/contact/action/update')) {
        let edit = new Button();
        edit.addStyle('flat');
        edit.getIcon().set('edit');
        edit.setText(window.page.getTranslate('nav.buttons.edit'));
        edit.appendAttributes({
            'data-selected-min': 1,
            'data-selected-max': 1
        });
        edit.onClick(function () {
            window.location = '/customer/contact/upsert/' + window.choosed[0];
        });
        window.buttons.push(edit);
    }

    widgets.modal = new Modal();

    let notice = document.createElement('p'), instructions = document.createElement('p');
    notice.appendChild(document.createTextNode(window.page.getTranslate('modal.delete.notice')));
    widgets.modal.addContent(notice);

    widgets.modal.addContent(instructions);
    widgets.modal.setActionShow(function () {
        let title = window.page.getTranslate('modal.delete.title'), parsed = title.replace(/\$0/, window.choosed.length);
        widgets.modal.setTitle(parsed);
    });

    window.page.addHTMLElement(widgets.modal.out());

    widgets.modal.buttons = {};
    widgets.modal.buttons.submit = new Button();
    widgets.modal.buttons.submit.addStyle('flat red');
    widgets.modal.buttons.submit.setText(window.page.getTranslate('buttons.delete'));
    widgets.modal.buttons.submit.onClick(function () {
        let preloader = widgets.read.getPreloader().status();
        if (preloader === true) return;

        let text = window.page.getTranslate('buttons.loader');
        this.getLoader().apply(text);

        window.requests = [];
        for (let x in window.choosed) {
            let xhr = new WXmlHttpRequest(),
                url = '/api/customer/contact/delete'
                    + String.fromCharCode(47)
                    + encodeURIComponent(window.choosed[x])
                    + String.fromCharCode(63)
                    + 'timestamp'
                    + String.fromCharCode(61)
                    + Date.now();
            window.requests.push(xhr);
            xhr.setRequestUrl(url);
            xhr.setCallbackSuccess(function () {
                let parser = document.createElement('a');
                parser.href = this.getXHR().responseURL;

                let split = parser.pathname.split(String.fromCharCode(47)), key = split[split.length - 1] === 'delete' ? null : split[split.length - 1];
                widgets.read.getBody().removeTR(key);
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

    widgets.modal.buttons.submit.cancel = new Button();
    widgets.modal.buttons.submit.cancel.addStyle('flat');
    widgets.modal.buttons.submit.cancel.setText(window.page.getTranslate('buttons.cancel'));
    widgets.modal.buttons.submit.cancel.onClick(function () {
        widgets.modal.hide();
    });

    widgets.modal.container = document.createElement('div');
    widgets.modal.container.className = 'buttons-form';
    widgets.modal.container.appendChild(widgets.modal.buttons.submit.out());
    widgets.modal.container.appendChild(widgets.modal.buttons.submit.cancel.out());

    widgets.modal.setBottom(widgets.modal.container);

    if (window.page.checkPolicy(window.page.application + '/customer/contact/action/delete')) {
        let button = new Button();
        button.addStyle('flat');
        button.getIcon().set('delete');
        button.setText(window.page.getTranslate('nav.buttons.delete'));
        button.appendAttributes({
            'data-selected-min': 1
        });
        button.onClick(function () {
            widgets.modal.show();
        });
        window.buttons.push(button);
    }

    for (let item = 0, action = widgets.nav.getColumn(14); item < window.buttons.length; item++)
        action.addContent(window.buttons[item].out());

    let infinite_setting = widgets.read.getSetting();
    widgets.sidepanel = new SidePanel();

    if (infinite_setting !== null && window.page.checkPolicy(window.page.application + '/sso/user/action/save/widget/setting')) {
        let api = '/api/sso/user/save'
            + String.fromCharCode(63)
            + 'timestamp'
            + String.fromCharCode(61)
            + Date.now();
        widgets.sidepanel.setTitle(window.page.getTranslate('sidepanel.title'));
        widgets.sidepanel.pushContent(infinite_setting.out());
        widgets.sidepanel.setActionShow(function () {
            window.elements.content.className = 'widget-infinite-enable-print pure-u-24-24 pure-u-lg-18-24 resize';
            window.setting.setText(window.page.getTranslate('nav.buttons.hide_settings'))
        });
        widgets.sidepanel.setActionHide(function () {
            window.elements.content.className = 'widget-infinite-enable-print pure-u-24-24 pure-u-lg-24-24 resize';
            window.setting.setText(window.page.getTranslate('nav.buttons.show_settings'));
        });

        infinite_setting.setRequestUrl(api);
        infinite_setting.setHardcode('widget', 'read');
        infinite_setting.setHardcode('application', window.page.getApplication());
        infinite_setting.setHardcode('module', window.page.getModule());
        infinite_setting.setHardcode('view', window.page.getView());

        window.elements.main.appendChild(widgets.sidepanel.out());
    }

    if (window.page.checkPolicy(window.page.application + '/sso/user/action/save/widget/setting')) {
        window.setting = new Button();
        window.setting.addStyle('flat');
        window.setting.getIcon().set('settings');
        window.setting.setText(window.page.getTranslate('nav.buttons.show_settings'));
        window.setting.onClick(function (event) {
            widgets.sidepanel.toggle(event);
            window.setting.getText();
        });
        widgets.nav.getColumn(4).addContent(window.setting.out());
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

    if (widgets.hasOwnProperty('modal')) window.page.addHTMLElement(widgets.modal.out());

})(window);
