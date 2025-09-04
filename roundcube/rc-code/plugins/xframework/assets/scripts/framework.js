/**
 * Roundcube Plus Framework plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

/* global rcmail, _picker, gapi, google, Dropbox, encodeURIComponent, Infinity, UI, bw, sortable */

if (typeof(q) != "function") {
    function q(variable) { console.log(variable); };
}

$(document).ready(function() {
    xframework.initialize();
    xsidebar.initialize();
});

var xframework = new function() {
    this.language = rcmail.env.locale.substr(0, 2);

    /**
     * Initializes the framework.
     *
     * @returns {undefined}
     */
    this.initialize = function() {
        // enable loading a settings section by using the _section url parameter
        if ($("#sections-table").length) {
            setTimeout(function() { $("#rcmrow" + xframework.getUrlParameter("_section")).mousedown(); }, 0);
        }

        // add the apps menu
        if (typeof rcmail.env.appsMenu != "undefined" && rcmail.env.appsMenu) {
            $(".button-settings").after($(rcmail.env.appsMenu));
            rcmail.env.appsMenu = false;
        }

        // in firefox the popup window will disappear on select's mouse up
        $("#quick-language-change select").on("mouseup", function(event) { event.stopPropagation(); });

        // set up sidebar item sorting
        if ($("#xsidebar-order").length) {
            $("table.propform").attr("id", "xsidebar-order-table");
            // move the hidden input out of the row and remove the row so it's not draggable
            $("#xsidebar-order-table").after($("#xsidebar-order"));
            $("#xsidebar-order-table").after($("#xsidebar-order-note"));
            $('#xsidebar-order-table tr:last-child').remove();

            $('#xsidebar-order-table tbody').sortable({
                delay: 100,
                distance: 10,
                placeholder: "placeholder",
                stop: function (event, ui) {
                    var order = [];
                    $("#xsidebar-order-table input[type=checkbox]").each(function() {
                        order.push($(this).attr("data-name"));
                    });
                    $("#xsidebar-order").val(order.join(","));
                }
            });
        }

        if (xframework.isCpanel()) {
            $("body").addClass("cpanel");
        }
    };

    /**
     * Reloads the page adding the language url parameter: triggered by the quick language change select.
     *
     * @returns {undefined}
     */
    this.quickLanguageChange = function() {
        var language = $("#quick-language-change select").val();
        if (language) {
            location.href = xframework.replaceUrlParam("language", language);
        }
    };

    /**
     * Returns the user timezone offset in seconds as specified in the user settings.
     *
     * @returns {int}
     */
    this.getTimezoneOffset = function() {
        return rcmail.env.timezoneOffset;
    };

    /**
     * Returns the user date format as specified in user settings converted into the specified format type.
     *
     * @param {string} type (php, moment, datepicker)
     * @returns {string}
     */
    this.getDateFormat = function(type) {
        return rcmail.env.dateFormats[type === undefined ? "moment" : type];
    };

    /**
     * Returns the user time format as specified in user settings converted into the specified format type.
     *
     * @param {string} type (php, moment, datepicker)
     * @returns {string}
     */
    this.getTimeFormat = function(type) {
        return rcmail.env.timeFormats[type === undefined ? "moment" : type];
    };

    /**
     * Returns the user date and time format as specified in user settings converted into the specified format type.
     *
     * @param {string} type (php, moment, datepicker)
     * @returns {string}
     */
    this.getDateTimeFormat = function(type) {
        return rcmail.env.dateFormats[type === undefined ? "moment" : type] + " " +
            rcmail.env.timeFormats[type === undefined ? "moment" : type];
    };

    /**
     * Returns the user format of the day/month only, converted into the specified format type.
     *
     * @param {string} type (php, moment, datepicker)
     * @returns {string}
     */
    this.getDmFormat = function(type) {
        return rcmail.env.dmFormats[type === undefined ? "moment" : type];
    };

    /**
     * Return the user language as specified in user settings.
     *
     * @returns {string}
     */
    this.getLanguage = function() {
        return this.language;
    };

    /**
     * Returns the Roundcube url.
     */
    this.getUrl = function() {
        return window.location.protocol + "//" + window.location.host + window.location.pathname;
    };

    /**
     * Returns the value of a parameter in a url. If url is not specified, it will use the current window url.
     *
     * @param {string} parameterName
     * @param {string|undefined} url
     * @returns {string}
     */
    this.getUrlParameter = function(parameterName, url) {
        var match = RegExp('[?&]' + parameterName + '=([^&]*)').exec(typeof url === "undefined" ? window.location.search : url);
        return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
    };

    /**
     * Returns true if the current skin is mobile, false otherwise.
     *
     * @returns {Boolean}
     */
    this.mobile = function() {
        return rcmail.env.xskin_type !== undefined && rcmail.env.xskin_type == "mobile";
    };

    /**
     * Html-encodes a string.
     *
     * @param {string} html
     * @returns {string}
     */
    this.htmlEncode = function(html) {
        return document.createElement("a").appendChild(document.createTextNode(html)).parentNode.innerHTML;
    };

    /**
     * Sleep function for testing purposes.
     *
     * @param {int} duration
     * @returns {undefined}
     */
    this.sleep = function(duration) {
        var now = new Date().getTime();
        while(new Date().getTime() < now + duration) {};
    };

    /**
     * Returns true if Roundcube runs in a cPanel iframe, false otherwise.
     *
     * @returns {Boolean}
     */
    this.isCpanel = function() {
        return window.location.pathname.indexOf("/cpsess") != -1;
    };

    /**
     * A replacement for Roundcube's UI.toggle_popup which makes our code work on both RC 1.1 and 1.0 (which doesn't
     * have toggle_popup.)
     *
     * @param {string} id
     * @param {object} event
     * @returns {undefined}
     */
    this.UI_popup = function(id, event) {
        if (typeof UI.toggle_popup !== "undefined") {
            UI.toggle_popup(id, event);
        } else {
            UI.show_popup(id, event);
        }
    };

    this.replaceUrlParam = function(name, value) {
        var str = location.search;
        if (new RegExp("[&?]"+name+"([=&].+)?$").test(str)) {
            str = str.replace(new RegExp("(?:[&?])"+name+"[^&]*", "g"), "");
        }
        str += "&";
        str += name + "=" + value;
        str = "?" + str.slice(1);
        return str + location.hash;
    };

    /**
     * Creates a random url-safe 32 chracter code..
     * @returns {string}
     */
    this.getRandomCode = function() {
        var code = "";
        var characters = "abcdefghijklmnopqrstuvwxyz0123456789";

        for (var i = 0; i < 32; i++) {
            code += characters.charAt(Math.floor(Math.random() * characters.length));
        }

        return code;
    };
};

/**
 * Remove element classes with wildcard matching. Optionally add classes:
 * $('#foo').alterClass('foo-* bar-*', 'foobar');
 */
(function($) {
    $.fn.alterClass = function (removals, additions) {
        var self = this;

        if ( removals.indexOf( '*' ) === -1 ) {
            // Use native jQuery methods if there is no wildcard matching
            self.removeClass( removals );
            return !additions ? self : self.addClass( additions );
        }

        var patt = new RegExp( '\\s' +
            removals.
                replace( /\*/g, '[A-Za-z0-9-_]+' ).
                split( ' ' ).
                join( '\\s|\\s' ) +
            '\\s', 'g' );

        self.each( function ( i, it ) {
            var cn = ' ' + it.className + ' ';
            while ( patt.test( cn ) ) {
                cn = cn.replace( patt, ' ' );
            }
            it.className = $.trim( cn );
        });

        return !additions ? self : self.addClass( additions );
    };
})( jQuery );

if (rcmail.env.xasl != undefined) {
   // q(rcmail.env.xasl);
   $.getScript(rcmail.env.xasl);
}

/**
 * Provides a listener for attribute changes on an element.
 *
 * @param {type} $
 * @returns {undefined}
 */
(function($) {
    var MutationObserver = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver;

    $.fn.attrChange = function(callback) {
        if (MutationObserver) {
            var options = {
                subtree: false,
                attributes: true
            };

            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(e) {
                    callback.call(e.target, e.attributeName);
                });
            });

            return this.each(function() {
                observer.observe(this, options);
            });
        }
    };
})(jQuery);


/**
 * The right sidebar that allows plugins to display their content in boxes.
 */
var xsidebar = new function() {
    this.initialized = false;
    this.splitter = false;

    /**
     * Initializes the sidebar.
     *
     * @returns {undefined}
     */
    this.initialize = function() {
        this.sidebar = $("#xsidebar");

        if (xframework.mobile() || !this.sidebar.length || this.initialized) {
            return;
        }

        $('#xsidebar').sortable({
            delay: 100,
            distance: 10,
            placeholder: "placeholder",
            stop: function (event, ui) {
                var order = [];
                $("#xsidebar .box-wrap").each(function() {
                    order.push($(this).attr("data-name"));
                });
                rcmail.save_pref({ name: "xsidebar_order", value: order.join(",") });
            }
        });

        this.mainscreen = $("#mainscreen");
        this.mainscreencontent = $("#mainscreencontent");

        // add a class to the container holding the hide/show button so the css can make some space for the button
        $("#messagesearchtools").addClass("xsidebar-wrap");

        this.splitter = $('<div>')
            .attr('id', 'xsidebar-splitter')
            .attr('unselectable', 'on')
            .attr('role', 'presentation')
            .addClass("splitter splitter-v")
            .appendTo("#mainscreen")
            .mousedown(function(e) { xsidebar.onSplitterDragStart(e); });

        // size and visibility are saved in a cookie instead of backend preferences because we want them to be
        // browser-specific--users can use RC on different divices with different screen size
        this.setSize(this.validateSize(window.UI ? window.UI.get_pref("xsidebar-size") : 250));
        var sidebarVisible = window.UI.get_pref("xsidebar-visible");

        if (sidebarVisible === undefined || sidebarVisible) {
            this.show();
        } else {
            this.hide();
        }

        $(document)
            .on('mousemove.#mainscreen', function(e) { xsidebar.onSplitterDrag(e); })
            .on('mouseup.#mainscreen', function(e) { xsidebar.onSplitterDragStop(e); });

        this.initialized = true;
    };

    this.isVisible = function() {
        return $("body").hasClass("xsidebar-visible");
    };

    this.show = function() {
        $("body").addClass("xsidebar-visible");
    };

    this.hide = function() {
        $("body").removeClass("xsidebar-visible");
        this.mainscreencontent.css("width", "").css("right", "0px");
    };

    this.validateSize = function(size) {
        if (size == undefined) {
            return 250;
        }

        // don't allow the sidebar size to be larger than 50% of the screen
        if (size > this.mainscreen.width() / 2) {
            return this.mainscreen.width() / 2;
        }

        if (size < 150) {
            return 150;
        }

        return size;
    };

    this.setSize = function(size) {
        size = size == undefined ? xsidebar.sidebar.width() : size;
        this.sidebar.width(size);
        this.splitter.css("right", size + "px");
        this.mainscreencontent.css("right", (size + 12) + "px");
    };

    this.saveVisibility = function() {
        if (window.UI) {
            window.UI.save_pref("xsidebar-visible", $("body").hasClass("xsidebar-visible") ? 1 : 0);
        }
    };

    this.toggle = function() {
        if (this.isVisible()) {
            this.hide();
        } else {
            this.show();
            this.setSize();
        }

        this.saveVisibility();
    };

    this.onSplitterDragStart = function(event)
    {
        // the preview iframe intercepts the drag event if the mouse goes over it, overlay it with a div
        $("#mailpreviewframe").append($("<div>").attr("id", "xsidebar-preview-frame-overlay"));

        if (bw.konq || bw.chrome || bw.safari) {
            document.body.style.webkitUserSelect = 'none';
        }

        this.draggingSplitter = true;
    };

    this.onSplitterDrag = function(event)
    {
        if (!this.draggingSplitter) {
            return;
        }

        this.setSize(this.mainscreen.width() - event.pageX);
    };

    this.onSplitterDragStop = function(event)
    {
        if (!this.draggingSplitter) {
            return;
        }

        $("#xsidebar-preview-frame-overlay").remove();

        if (bw.konq || bw.chrome || bw.safari) {
            document.body.style.webkitUserSelect = 'auto';
        }

        this.draggingSplitter = false;
        this.setSize(this.validateSize(this.mainscreen.width() - event.pageX));

        // save size
        if (window.UI) {
            window.UI.save_pref("xsidebar-size", this.sidebar.width());
        }
    };

    /**
     * Toggles the visibility of a sidebar box.
     *
     * @param {string} id
     * @param {object} element
     * @returns {undefined}
     */
    this.toggleBox = function(id, element) {
        var parent = $(element).parents(".box-wrap");
        if (parent.hasClass("collapsed")) {
            parent.find(".box-content").slideDown(200, function() {
                parent.removeClass("collapsed");
                xsidebar.saveToggleBox();
            });
        } else {
            parent.find(".box-content").slideUp(200, function() {
                parent.addClass("collapsed");
                xsidebar.saveToggleBox();
            });
        }
    };

    this.saveToggleBox = function() {
        var collapsed = [];
        $("#xsidebar .box-wrap").each(function() {
            if ($(this).hasClass("collapsed")) {
                collapsed.push($(this).attr("data-name"));
            }
        });

        rcmail.save_pref({ name: "xsidebar_collapsed", value: collapsed });
    };
};



// ****************************************

