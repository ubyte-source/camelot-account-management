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

    widgets.form = new window.Page.Widget.Organizer();
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
        account.href = window.page.iam + 'iam/user/upsert'
            + String.fromCharCode(47)
            + window.page.user._key;
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

    let back = '/customer/contact/read',
        title = window.reference.length === 0
            ? window.page.getTranslate('nav.add')
            : window.page.getTranslate('nav.edit');

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
        let action = Tabs.closestAttribute(ev.target, widgets.tabs.action),
            method = 1 === parseInt(action) ? 'remove' : 'add';
        window.elements.actions.classList[method]('hide');
    });
    window.elements.content.appendChild(widgets.tabs.out());

    let api_create = '/api/customer/contact/create'
        + String.fromCharCode(63)
        + 'timestamp'
        + String.fromCharCode(61)
        + Date.now();
    widgets.form.upsert = new Form();
    widgets.form.upsert.setRequestUrl(api_create);
    for (let item = 0; item < window.page.tables.upsert.fields.length; item++)
        widgets.form.upsert.addInput(window.page.tables.upsert.fields[item]);

    if (window.reference.length === 0) widgets.form.upsert.set('owner', window.page.user);

    let element = widgets.form.upsert.out(),
        business = widgets.tabs.addItem(window.page.getTranslate('tabs.contact'), element, 'material-icons business').out();

    business.setAttribute(widgets.tabs.action, 1);

    window.elements.content.appendChild(element);
    window.elements.content.appendChild(window.elements.actions);

    let submit = new Button(),
        icon = window.reference.length !== 0
            ? 'save'
            : 'add';
    submit.getIcon().set(icon);
    submit.setText(window.reference.length !== 0 ? window.page.getTranslate('buttons.save') : window.page.getTranslate('buttons.add'));
    submit.onClick(function () {
        let text = window.page.getTranslate('buttons.loader');
        this.getLoader().apply(text);

        widgets.form.upsert.request(function (response) {
            submit.getLoader().remove();
            if (response.hasOwnProperty('id_contact'))
                window.location = '/customer/contact/upsert'
                    + String.fromCharCode(47)
                    + response.id_contact;
        });
    });

    window.elements.actions.appendChild(submit.out());

    business.click();

    window.buttons = {};
    window.buttons.contact = [];

    window.choosed = {};
    window.choosed.contact = [];

    let related = document.createElement('div'),
        site = '/api/customer/contact/read'
            + String.fromCharCode(47)
            + window.reference[0];

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
        window.choosed.contact = this.getTR().getBody().getChecked();
        window.negotiateButtonStatus.call();
    }));

    widgets.action = new Nav();
    related.appendChild(widgets.action.out());
    related.appendChild(widgets.upsert.out());

    widgets.tabs.addItem(window.page.getTranslate('tabs.contact_reference'), related, 'material-icons policy').out().setAttribute(widgets.tabs.action, 0);
    window.elements.content.appendChild(related);

    if (window.page.checkPolicy('iam/user/action/read')) {
        let form_sharing_container = document.createElement('div'),
            form_sharing = widgets.form.upsert.findContainer('owner'),
            form_shared = widgets.form.upsert.findContainer('share');

        widgets.tabs.addItem(window.page.getTranslate('tabs.contact_sharing'), form_sharing_container, 'material-icons share').out();

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
            window.location = '/customer/contact/upsert'
                + String.fromCharCode(47)
                + window.choosed.contact[0];
        });
        window.buttons.contact.push(edit);
    }

    widgets.modal = new window.Page.Widget.Organizer();
    widgets.modal.customer = new Modal();

    let notice = document.createElement('p'),
        instructions = document.createElement('p');
    notice.appendChild(document.createTextNode(window.page.getTranslate('modal.reference.remove.notice')));
    widgets.modal.customer.addContent(notice);
    widgets.modal.customer.addContent(instructions);
    widgets.modal.customer.setActionShow(function () {
        let title = window.page.getTranslate('modal.reference.remove.title');
        widgets.modal.customer.setTitle(title.replace(/\$0/, window.choosed.contact.length));
    });

    window.page.addHTMLElement(widgets.modal.customer.out());

    let dissociate = new Button();
    dissociate.addStyle('flat red');
    dissociate.setText(window.page.getTranslate('buttons.dissociate'));
    dissociate.onClick(function () {
        let preloader = widgets.upsert.getPreloader().status();
        if (preloader === true) return;

        let text = window.page.getTranslate('buttons.loader');
        this.getLoader().apply(text);

        window.requests = [];
        for (let x in window.choosed.contact) {
            let xhr = new WXmlHttpRequest(),
                dissociate = '/api/customer/contact/dissociate'
                    + String.fromCharCode(47)
                    + encodeURIComponent(window.choosed.contact[x])
                    + String.fromCharCode(63)
                    + 'timestamp'
                    + String.fromCharCode(61)
                    + Date.now();

            window.requests.push(xhr);
            xhr.setRequestUrl(dissociate);
            xhr.setCallbackSuccess(function () {
                let parser = document.createElement('a');
                parser.href = this.responseURL;

                let split = parser.pathname.split(String.fromCharCode(47)),
                    key = split[split.length - 1] !== 'dissociate'
                        ? split[split.length - 1]
                        : null;

                widgets.upsert.getBody().removeTR(key);
                window.choosed.contact = window.choosed.contact.filter(function (element) {
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
        widgets.modal.customer.hide();
    });

    let container = document.createElement('div');
    container.className = 'buttons-form';
    container.appendChild(dissociate.out());
    container.appendChild(cancel.out());

    widgets.modal.customer.addContent(container);

    if (window.page.checkPolicy(window.page.application + '/customer/contact/action/dissociate')) {
        let button = new Button();
        button.addStyle('flat');
        button.getIcon().set('delete');
        button.setText(window.page.getTranslate('nav.buttons.dissociate'));
        button.appendAttributes({
            'data-selected-min': 1
        });
        button.onClick(function () {
            widgets.modal.customer.show();
        });
        window.buttons.contact.push(button);
    }

    widgets.sidepanel = new window.Page.Widget.Organizer();

    let infinite_setting = widgets.upsert.getSetting();
    if (infinite_setting !== null && window.page.checkPolicy(window.page.application + '/sso/user/action/save/widget/setting')) {
        let api = '/api/sso/user/save'
            + String.fromCharCode(63)
            + 'timestamp'
            + String.fromCharCode(61)
            + Date.now();
        widgets.sidepanel.reference = new SidePanel();
        widgets.sidepanel.reference.setTitle(window.page.getTranslate('sidepanel.title'));
        widgets.sidepanel.reference.pushContent(infinite_setting.out());
        widgets.sidepanel.reference.setActionShow(function () {
            window.setting.customer.setText(window.page.getTranslate('nav.buttons.hide_settings'))
        });
        widgets.sidepanel.reference.setActionHide(function () {
            window.setting.customer.setText(window.page.getTranslate('nav.buttons.show_settings'))
        });

        infinite_setting.setRequestUrl(api);
        infinite_setting.setHardcode('widget', 'upsert');
        infinite_setting.setHardcode('application', window.page.getApplication());
        infinite_setting.setHardcode('module', window.page.getModule());
        infinite_setting.setHardcode('view', window.page.getView());

        related.appendChild(widgets.sidepanel.reference.out());
    }

    for (let item = 0, action = widgets.action.getColumn(8); item < window.buttons.contact.length; item++)
        action.addContent(window.buttons.contact[item].out());

    window.setting = new window.Page.Widget.Organizer();
    if (window.page.checkPolicy(window.page.application + '/sso/user/action/save/widget/setting')) {
        window.setting.customer = new Button();
        window.setting.customer.addStyle('flat');
        window.setting.customer.getIcon().set('settings');
        window.setting.customer.setText(window.page.getTranslate('nav.buttons.show_settings'));
        window.setting.customer.onClick(function (event) {
            widgets.sidepanel.reference.toggle(event);
            window.setting.customer.getText();
        });
        widgets.action.getColumn(10);
        widgets.action.getColumn(6).addContent(window.setting.customer.out());
    }

    window.negotiateButtonStatus = function () {
        for (let item = 0; item < window.buttons.contact.length; item++) {
            let button = window.buttons.contact[item].out();
            let min = button.hasAttribute('data-selected-min') ? parseInt(button.getAttribute('data-selected-min')) : 0;
            let max = button.hasAttribute('data-selected-max') ? parseInt(button.getAttribute('data-selected-max')) : null;
            if (min === 0 && max === null) continue;
            if (min > window.choosed.contact.length || max !== null && window.choosed.contact.length > max) {
                button.setAttribute('disabled', true);
                button.classList.add('disabled');
            } else {
                button.classList.remove('disabled');
                button.removeAttribute('disabled');
            }
        }
    };

    window.negotiateButtonStatus.call();

    //////////////// BOOK

    if (window.reference.length === 0) return;

    window.buttons.book = [];
    window.choosed.book = [];

    let book = document.createElement('div'),
        url = '/api/sso/user/read'
            + String.fromCharCode(47)
            + window.reference[0];

    for (let item = 0; item < window.page.tables.book.fields.length; item++) switch (window.page.tables.book.fields[item].name) {
        case 'picture':
            window.page.tables.book.fields[item][Infinite.Body.TD.handling()] = function (value) {
                let text = typeof value !== 'string'
                    || value.length === 0
                    ? Infinite.Plugin.Text.void()
                    : value;

                if (text === Infinite.Plugin.Text.void()) {
                    let node = document.createTextNode(text),
                        result = document.createElement('div');
                    result.className = 'result null';
                    result.appendChild(node);
                    return result;
                }

                let result = document.createElement('div'),
                    image = document.createElement('img');
                result.className = 'result';
                result.appendChild(image);
                image.setAttribute('src', text);

                return result;
            }
            break;
    }

    widgets.book = new Infinite();
    widgets.book.setOptionSetting(window.page.user.setting.book);
    widgets.book.setOptionStructure(window.page.tables.book.fields);
    widgets.book.setContainer(element);
    widgets.book.setRequestUrl(url);
    widgets.book.setResponseKey('data');
    widgets.book.setResponseUnique('_key');
    widgets.book.getNotice().setTextEmpty(window.page.getTranslate('infinite.no_result'));
    widgets.book.request();
    widgets.book.addEventSelect(new Infinite.Event(Infinite.Event.always(), function () {
        window.choosed.book = this.getTR().getBody().getChecked();
        window.negotiateButtonStatusBook.call();
    }));

    widgets.action_book = new Nav();
    book.appendChild(widgets.action_book.out());
    book.appendChild(widgets.book.out());

    widgets.tabs.addItem(window.page.getTranslate('tabs.contact_book'), book, 'material-icons person').out().setAttribute(widgets.tabs.action, 0);
    window.elements.content.appendChild(book);

    let notice_book_associate = document.createElement('p'),
        notice_book_associate_instruction = document.createElement('p');
    notice_book_associate.appendChild(document.createTextNode(window.page.getTranslate('modal.associate.notice')));

    widgets.modal.associate = new Modal();
    widgets.modal.associate.addContent(notice_book_associate);

    let api_associate = '/api/sso/user/associate'
        + String.fromCharCode(47)
        + encodeURIComponent(window.reference[0])
        + String.fromCharCode(63)
        + 'timestamp'
        + String.fromCharCode(61)
        + Date.now();
    widgets.form.associate = new Form();
    widgets.form.associate.setRequestUrl(api_associate);
    widgets.form.associate.setCallbackSuccess(function () {
        widgets.book.setRequestOffset(0);
        widgets.book.request();
    });

    for (let item = 0; item < window.page.tables.associate.fields.length; item++)
        widgets.form.associate.addInput(window.page.tables.associate.fields[item]);

    widgets.modal.associate.addContent(widgets.form.associate.out());
    widgets.modal.associate.addContent(notice_book_associate_instruction);
    widgets.modal.associate.setActionShow(function () {
        widgets.modal.associate.setTitle(window.page.getTranslate('modal.associate.title'));
    });

    window.page.addHTMLElement(widgets.modal.associate.out());

    let associate = new Button();
    associate.addStyle('flat red');
    associate.setText(window.page.getTranslate('buttons.associate'));
    associate.onClick(function () {
        let preloader = widgets.upsert.getPreloader().status();
        if (preloader === true) return;

        widgets.form.associate.request(function () {
            associate.getLoader().remove();
            widgets.modal.associate.hide();
        });
    });

    let cancel_book_associate = new Button();
    cancel_book_associate.addStyle('flat');
    cancel_book_associate.setText(window.page.getTranslate('buttons.cancel'));
    cancel_book_associate.onClick(function () {
        widgets.modal.associate.hide();
    });

    let container_associate = document.createElement('div');
    container_associate.className = 'buttons-form';
    container_associate.appendChild(associate.out());
    container_associate.appendChild(cancel_book_associate.out());

    if (window.page.checkPolicy('iam/user/action/create')) {
        let newbook_associate = new Button();
        newbook_associate.addStyle('flat green');
        newbook_associate.setText(window.page.getTranslate('buttons.newbook'));
        newbook_associate.onClick(function () {
            widgets.modal.associate.hide();
            widgets.sidepanel.user.hide();
            widgets.sidepanel.book.show();
        });
        container_associate.appendChild(newbook_associate.out());
    }

    widgets.modal.associate.addBottom(container_associate);

    if (window.page.checkPolicy(window.page.application + '/sso/user/action/associate')) {
        let button = new Button();
        button.addStyle('flat');
        button.getIcon().set('person_add');
        button.setText(window.page.getTranslate('nav.buttons.associate'));
        button.onClick(function () {
            widgets.modal.associate.show();
        });
        window.buttons.book.push(button);
    }

    let notice_remove = document.createElement('p'),
        instructions_remove = document.createElement('p');

    notice_remove.appendChild(document.createTextNode(window.page.getTranslate('modal.book.remove.notice')));

    widgets.modal.book = new Modal();
    widgets.modal.book.addContent(notice_remove);
    widgets.modal.book.addContent(instructions_remove);
    widgets.modal.book.setActionShow(function () {
        let title = window.page.getTranslate('modal.book.remove.title');
        widgets.modal.book.setTitle(title.replace(/\$0/, window.choosed.book.length));
    });

    window.page.addHTMLElement(widgets.modal.book.out());

    let remove = new Button();
    remove.addStyle('flat red');
    remove.setText(window.page.getTranslate('buttons.dissociate'));
    remove.onClick(function () {
        let preloader = widgets.upsert.getPreloader().status();
        if (preloader === true) return;

        let text = window.page.getTranslate('buttons.loader');
        this.getLoader().apply(text);

        let xhr = new WXmlHttpRequest(),
            xhrdissociate = '/api/sso/user/dissociate'
                + String.fromCharCode(47)
                + encodeURIComponent(window.reference[0])
                + String.fromCharCode(63)
                + 'timestamp'
                + String.fromCharCode(61)
                + Date.now();

        for (let x in window.choosed.book) xhr.setHardcode(
            'book' + String.fromCharCode(91) + x + String.fromCharCode(93),
            window.choosed.book[x]
        );

        xhr.setRequestUrl(xhrdissociate);
        xhr.setCallbackSuccess(function () {
            remove.getLoader().remove();
            widgets.modal.book.hide();

            for (let x in window.choosed.book)
                widgets.book.getBody().removeTR(window.choosed.book[x]);

            window.choosed.book = [];
            window.negotiateButtonStatusBook.call();
        });
        xhr.request();
    });

    let cancel_book = new Button();
    cancel_book.addStyle('flat');
    cancel_book.setText(window.page.getTranslate('buttons.cancel'));
    cancel_book.onClick(function () {
        widgets.modal.book.hide();
    });

    let container_book = document.createElement('div');
    container_book.className = 'buttons-form';
    container_book.appendChild(remove.out());
    container_book.appendChild(cancel_book.out());

    widgets.modal.book.addBottom(container_book);

    if (window.page.checkPolicy(window.page.application + '/sso/user/action/dissociate')) {
        let button = new Button();
        button.addStyle('flat');
        button.getIcon().set('person_remove');
        button.setText(window.page.getTranslate('nav.buttons.remove'));
        button.appendAttributes({
            'data-selected-min': 1
        });
        button.onClick(function () {
            widgets.modal.book.show();
        });
        window.buttons.book.push(button);
    }

    let insert_api = '/api/sso/user/gateway/iam/iam/user/create'
        + String.fromCharCode(63)
        + 'timestamp'
        + String.fromCharCode(61)
        + Date.now();

    widgets.form.user = new Form();
    widgets.form.user.setCallbackSuccess(function (json) {
        widgets.sidepanel.book.hide();
        widgets.form.user.reset();
        widgets.modal.associate.show();
        widgets.form.associate.set('book', [
            json.data
        ]);
    });

    widgets.form.user.setRequestUrl(insert_api);
    for (let item = 0; item < window.page.tables.book.fields.length; item++) {
        if ('picture' === window.page.tables.book.fields[item].name
            || 'type' === window.page.tables.book.fields[item].name) continue;

        if ('phone_number_prefix' === window.page.tables.book.fields[item].name
            || 'phone_number_office_prefix' === window.page.tables.book.fields[item].name)
            window.page.tables.book.fields[item].required = true;

        widgets.form.user.addInput(window.page.tables.book.fields[item]);
    }

    let tabcontent = document.createElement('div');
    tabcontent.id = 'tabcontent';
    tabcontent.appendChild(widgets.form.user.out());

    let side_group = document.createElement('div');
    side_group.className = 'buttons-form';
    tabcontent.appendChild(side_group);

    let side_submit = new Button();
    side_submit.getIcon().set('add');
    side_submit.setText(window.page.getTranslate('buttons.add'));
    side_submit.onClick(function () {
        this.getLoader().apply(window.page.getTranslate('buttons.loader'));
        widgets.form.user.request(function () {
            side_submit.getLoader().remove();
        });
    });
    side_group.appendChild(side_submit.out());

    let side_close = new Button();
    side_close.getIcon().set('close');
    side_close.addStyle('red');
    side_close.setText(window.page.getTranslate('buttons.close'));
    side_close.onClick(function () {
        widgets.sidepanel.book.hide();
    });
    side_group.appendChild(side_close.out());

    widgets.sidepanel.book = new SidePanel();
    widgets.sidepanel.book.setTitle(window.page.getTranslate('sidepanel.create'));
    widgets.sidepanel.book.pushContent(tabcontent);
    book.appendChild(widgets.sidepanel.book.out());

    let infinite_setting_book = widgets.book.getSetting();
    if (infinite_setting_book !== null && window.page.checkPolicy(window.page.application + '/sso/user/action/save/widget/setting')) {
        let api = '/api/sso/user/save'
            + String.fromCharCode(63)
            + 'timestamp'
            + String.fromCharCode(61)
            + Date.now();

        widgets.sidepanel.user = new SidePanel();
        widgets.sidepanel.user.setTitle(window.page.getTranslate('sidepanel.title'));
        widgets.sidepanel.user.pushContent(infinite_setting_book.out());
        widgets.sidepanel.user.setActionShow(function () {
            window.setting.book.setText(window.page.getTranslate('nav.buttons.hide_settings'))
        });
        widgets.sidepanel.user.setActionHide(function () {
            window.setting.book.setText(window.page.getTranslate('nav.buttons.show_settings'))
        });

        infinite_setting_book.setRequestUrl(api);
        infinite_setting_book.setHardcode('widget', 'book');
        infinite_setting_book.setHardcode('application', 'sso');
        infinite_setting_book.setHardcode('module', 'user');
        infinite_setting_book.setHardcode('view', 'user');

        book.appendChild(widgets.sidepanel.user.out());
    }

    for (let item = 0, action = widgets.action_book.getColumn(8); item < window.buttons.book.length; item++)
        action.addContent(window.buttons.book[item].out());

    if (window.page.checkPolicy(window.page.application + '/sso/user/action/save/widget/setting')) {
        window.setting.book = new Button();
        window.setting.book.addStyle('flat');
        window.setting.book.getIcon().set('settings');
        window.setting.book.setText(window.page.getTranslate('nav.buttons.show_settings'));
        window.setting.book.onClick(function (event) {
            widgets.sidepanel.book.hide();
            widgets.sidepanel.user.toggle(event);
            window.setting.book.getText();
        });
        widgets.action_book.getColumn(10);
        widgets.action_book.getColumn(6).addContent(window.setting.book.out());
    }

    window.negotiateButtonStatusBook = function () {
        for (let item = 0; item < window.buttons.book.length; item++) {
            let button = window.buttons.book[item].out();
            let min = button.hasAttribute('data-selected-min') ? parseInt(button.getAttribute('data-selected-min')) : 0;
            let max = button.hasAttribute('data-selected-max') ? parseInt(button.getAttribute('data-selected-max')) : null;
            if (min === 0 && max === null) continue;
            if (min > window.choosed.book.length || max !== null && window.choosed.book.length > max) {
                button.setAttribute('disabled', true);
                button.classList.add('disabled');
            } else {
                button.classList.remove('disabled');
                button.removeAttribute('disabled');
            }
        }
    };

    window.negotiateButtonStatusBook.call();

    let api_update = '/api/customer/contact/update'
        + String.fromCharCode(47)
        + encodeURIComponent(window.reference[0])
        + String.fromCharCode(63)
        + 'timestamp'
        + String.fromCharCode(61)
        + Date.now();
    widgets.form.upsert.setRequestUrl(api_update);

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
        for (let item in response.data)
            widgets.form.upsert.set(item, response.data[item]);
    });
    main.request();

})(window);
