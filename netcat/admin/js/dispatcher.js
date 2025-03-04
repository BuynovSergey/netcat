// url dispatching
urlDispatcher = {
    ajaxAdd: false, // dunno why they wanted that
    intervalId: 0,
    userAreaRegExp: /(\w+(?:\.\w+)*)(?:\((.+?)\))?/, // cache compiled regexps (in sake of perfomance)

    // initialize
    init: function() {
        if (!this.intervalId) {
            urlDispatcher.intervalId = setInterval('urlDispatcher.observe()', 150);
        }
    },

    // слежение за fragment-частью URL
    observe: function() {
        if ((window.location.oldHash != window.location.hash) || this.ajaxAdd) {
            this.ajaxAdd = false;
            window.location.oldHash = window.location.hash;
            this.process(window.location.hash);
        }
    },

    // change hash (call
    load: function(hash, newWindow) {
        this.ajaxAdd = nc_is_frame();
        if (!newWindow) {
            window.location.hash = hash;
        } else {
            window.open(hash, newWindow);
        }
    },

    // update without processing:
    updateHash: function(hash) {
        if (hash.substr(0, 1) != '#') {
            hash = '#' + hash;
        }

        if (decodeURIComponent(window.location.oldHash) != decodeURIComponent(hash)) {
            window.location.oldHash = window.location.hash = hash;
        }
    },

    // просто смена url в главном фрейме
    route: {},

    // набор функций, реагирующих на определенный префикс в fragment
    // ключ — префикс, значение — функция
    prefixMatchers: {},

    // onHashChange
    process: function(hash) {
        hash = decodeURIComponent(hash.substring(1)); // remove '#'

        if (!hash) { // FIRST 'WELCOME' SCREEN
            //treeSelector.changeMode('sitemap');
            //FIRST_TREE_MODE пришел из index.php в началe, из переменной $treeMode; a она определена в function.inc.php
            treeSelector.changeMode(FIRST_TREE_MODE);

            //mainView.showStartScreen();
            //return;
            hash = '#index';
        }

        if (hash.match(this.userAreaRegExp)) {
            var functionName = RegExp.$1, param = RegExp.$2;

            // process url here
            // обработчики по префиксу
            for (var prefix in this.prefixMatchers) {
                if (functionName.indexOf(prefix) === 0) {
                    this.prefixMatchers[prefix](functionName, param);
                    return;
                }
            }

            // перезагрузка главного фрейма
            if (this.route[functionName]) {
                param = param.split(/\s*,\s*/);
                var url = this.route[functionName];

                for (var i=0; i < param.length; i++) {
                    url = url.replace(new RegExp("[$%]"+(i+1),"g"), param[i]);
                }

                // незамененные макропараметры нужно заменить нулем
                url = url.replace(new RegExp("[$%]([0-9])+","g"), 0);
                url = url.replace(/\$\d+/g, '');
                mainView.loadIframe(url);
                return;
            }
        }

        alert('Wrong params\n' + hash);
    }, // of urlDispatcher.process

    addRoutes: function(hashArray) {
        for (var newUrl in hashArray) {
            this.route[newUrl] = hashArray[newUrl];
        }

        return this;
    },

    addPrefixRouter: function(prefix, fn) {
        this.prefixMatchers[prefix] = fn;

        return this;
    }
};

bindEvent(window, 'load', function() {
    urlDispatcher.init();
});
