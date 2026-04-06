window.initThemePlugin = function () {
    !(function (s) {
        "use strict";
        var e,
            t = localStorage.getItem("language"),
            a = "en";

        function n(e) {
            document.getElementById("header-lang-img") &&
                ("en" == e
                    ? (document.getElementById("header-lang-img").src =
                          "/assets/images/flags/us.jpg")
                    : "sp" == e
                      ? (document.getElementById("header-lang-img").src =
                            "/assets/images/flags/spain.jpg")
                      : "gr" == e
                        ? (document.getElementById("header-lang-img").src =
                              "/assets/images/flags/germany.jpg")
                        : "it" == e
                          ? (document.getElementById("header-lang-img").src =
                                "/assets/images/flags/italy.jpg")
                          : "ru" == e &&
                            (document.getElementById("header-lang-img").src =
                                "/assets/images/flags/russia.jpg"),
                localStorage.setItem("language", e),
                null == (t = localStorage.getItem("language")) && n(a),
                s.getJSON("/assets/lang/" + t + ".json", function (e) {
                    s("html").attr("lang", t);
                    s.each(e, function (e, t) {
                        "head" === e && s(document).attr("title", t.title);
                        s("[key='" + e + "']").text(t);
                    });
                }));
        }

        function r() {
            const topnav = document.getElementById("topnav-menu-content");
            if (!topnav) return;

            const links = topnav.getElementsByTagName("a");
            for (let t = 0, len = links.length; t < len; t++) {
                if (
                    links[t].parentElement &&
                    links[t].parentElement.getAttribute("class") ===
                        "nav-item dropdown active"
                ) {
                    links[t].parentElement.classList.remove("active");
                    if (links[t].nextElementSibling) {
                        links[t].nextElementSibling.classList.remove("show");
                    }
                }
            }
        }

        function c(e) {
            if (
                s("#light-mode-switch").length &&
                s("#light-mode-switch").prop("checked") == 1 &&
                e === "light-mode-switch"
            ) {
                s("html").removeAttr("dir");
                s("#dark-mode-switch").prop("checked", false);
                s("#rtl-mode-switch").prop("checked", false);
                s("#dark-rtl-mode-switch").prop("checked", false);
                s("html").attr("data-bs-theme", "light");
                sessionStorage.setItem("is_visited", "light-mode-switch");
            } else if (
                s("#dark-mode-switch").length &&
                s("#dark-mode-switch").prop("checked") == 1 &&
                e === "dark-mode-switch"
            ) {
                s("html").removeAttr("dir");
                s("#light-mode-switch").prop("checked", false);
                s("#rtl-mode-switch").prop("checked", false);
                s("#dark-rtl-mode-switch").prop("checked", false);
                s("html").attr("data-bs-theme", "dark");
                sessionStorage.setItem("is_visited", "dark-mode-switch");
            } else if (
                s("#rtl-mode-switch").length &&
                s("#rtl-mode-switch").prop("checked") == 1 &&
                e === "rtl-mode-switch"
            ) {
                s("#light-mode-switch").prop("checked", false);
                s("#dark-mode-switch").prop("checked", false);
                s("#dark-rtl-mode-switch").prop("checked", false);
                s("html").attr("dir", "rtl");
                s("html").attr("data-bs-theme", "light");
                sessionStorage.setItem("is_visited", "rtl-mode-switch");
            } else if (
                s("#dark-rtl-mode-switch").length &&
                s("#dark-rtl-mode-switch").prop("checked") == 1 &&
                e === "dark-rtl-mode-switch"
            ) {
                s("#light-mode-switch").prop("checked", false);
                s("#rtl-mode-switch").prop("checked", false);
                s("#dark-mode-switch").prop("checked", false);
                s("html").attr("dir", "rtl");
                s("html").attr("data-bs-theme", "dark");
                sessionStorage.setItem("is_visited", "dark-rtl-mode-switch");
            }
        }

        function l() {
            if (
                !document.webkitIsFullScreen &&
                !document.mozFullScreen &&
                !document.msFullscreenElement
            ) {
                s("body").removeClass("fullscreen-enable");
            }
        }

        if (s("#side-menu").length && $.fn.metisMenu) {
            s("#side-menu").metisMenu();
        }

        s("#vertical-menu-btn").off("click").on("click", function (e) {
            e.preventDefault();
            s("body").toggleClass("sidebar-enable");
            if (s(window).width() >= 992) {
                s("body").toggleClass("vertical-collpsed");
            } else {
                s("body").removeClass("vertical-collpsed");
            }
        });

        s("#sidebar-menu a").each(function () {
            const current = window.location.href.split(/[?#]/)[0];
            if (this.href == current) {
                s(this).addClass("active");
                s(this).parent().addClass("mm-active");
                s(this).parent().parent().addClass("mm-show");
                s(this).parent().parent().prev().addClass("mm-active");
                s(this).parent().parent().parent().addClass("mm-active");
                s(this).parent().parent().parent().parent().addClass("mm-show");
                s(this).parent().parent().parent().parent().parent().addClass("mm-active");
            }
        });

        s(".navbar-nav a").each(function () {
            const current = window.location.href.split(/[?#]/)[0];
            if (this.href == current) {
                s(this).addClass("active");
                s(this).parent().addClass("active");
                s(this).parent().parent().addClass("active");
                s(this).parent().parent().parent().addClass("active");
                s(this).parent().parent().parent().parent().addClass("active");
                s(this).parent().parent().parent().parent().parent().addClass("active");
                s(this).parent().parent().parent().parent().parent().parent().addClass("active");
            }
        });

        s('[data-bs-toggle="fullscreen"]').off("click").on("click", function (e) {
            e.preventDefault();
            s("body").toggleClass("fullscreen-enable");

            if (
                document.fullscreenElement ||
                document.mozFullScreenElement ||
                document.webkitFullscreenElement
            ) {
                if (document.cancelFullScreen) document.cancelFullScreen();
                else if (document.mozCancelFullScreen) document.mozCancelFullScreen();
                else if (document.webkitCancelFullScreen) document.webkitCancelFullScreen();
            } else {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }
            }
        });

        document.removeEventListener("fullscreenchange", l);
        document.removeEventListener("webkitfullscreenchange", l);
        document.removeEventListener("mozfullscreenchange", l);

        document.addEventListener("fullscreenchange", l);
        document.addEventListener("webkitfullscreenchange", l);
        document.addEventListener("mozfullscreenchange", l);

        [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function (el) {
            bootstrap.Tooltip.getOrCreateInstance(el);
        });

        [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]')).forEach(function (el) {
            bootstrap.Popover.getOrCreateInstance(el);
        });

        [].slice.call(document.querySelectorAll(".offcanvas")).forEach(function (el) {
            bootstrap.Offcanvas.getOrCreateInstance(el);
        });

        if (window.Waves) {
            Waves.init();
        }

        if (t && t !== a) n(t);
    })(window.jQuery);
};