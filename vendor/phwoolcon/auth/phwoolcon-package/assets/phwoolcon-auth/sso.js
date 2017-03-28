/*! phwoolcon sso.js v1.0-dev https://github.com/phwoolcon/auth | Apache-2.0 */
/* SSO api */
!function (w, d) {
    w.$p || (w.$p = {
        options: {
            ssoCheckUri: "sso/check",
            ssoServerCheckUri: "sso/server-check",
            baseUrl: "/"
        }
    });

    var $ = w.jQuery;
    var initialized, body, cIframe, sIframe, clientWindow, serverWindow, notifyForm, msgTargetOrigin, timerServerCheck;
    var options = {
            ssoServer: $p.options.baseUrl,
            ssoCheckUri: $p.options.ssoCheckUri,
            ssoServerCheckUri: $p.options.ssoServerCheckUri,
            initToken: "",
            initTime: 0,
            notifyUrl: "",
            debug: false
        }, ssoClientNotifyIframeName = "_sso_client_iframe_" + (+new Date),
        vars = {};
    var simpleStorage = w.simpleStorage || {
            get: function (key) {
                return w.localStorage ? _getJson(w.localStorage.getItem("_sso_" + key)) : false;
            },
            set: function (key, value) {
                return w.localStorage ? w.localStorage.setItem("_sso_" + key, _jsonStringify(value)) : false;
            }
        };
    var sso = w.$p.sso = {
        options: options,
        init: function (ssoOptions) {
            sso.setOptions(ssoOptions);
            if (initialized) {
                return;
            }
            initialized = true;
            body = d.getElementsByTagName("body")[0];
            if ($p.options.isSsoServer) {
                msgTargetOrigin = d.referrer;
                _listen(w, "message", function (e) {
                    _serverOnMessage.apply(sso, [e]);
                });
            } else {
                msgTargetOrigin = options.ssoServer;
                _listen(w, "message", function (e) {
                    _clientOnMessage.apply(sso, [e]);
                });
                cIframe = d.createElement("iframe");
                cIframe.width = cIframe.height = cIframe.frameBorder = 0;
                cIframe.style.display = "none";
                cIframe.name = ssoClientNotifyIframeName;
                cIframe.src = "";
                body.appendChild(cIframe);

                var notifyField = d.createElement("input");
                notifyField.type = "hidden";
                notifyField.name = "sso_user_data";
                notifyForm = d.createElement("form");
                notifyForm.action = options.notifyUrl;
                notifyForm.style.display = "none";
                notifyForm.method = "POST";
                notifyForm.target = ssoClientNotifyIframeName;
                notifyForm.appendChild(notifyField);
                notifyForm.notifyField = notifyField;
                body.appendChild(notifyForm);
            }
        },
        setOptions: function (ssoOptions) {
            if (ssoOptions) for (var key in ssoOptions) {
                if (ssoOptions.hasOwnProperty(key)) {
                    options[key] = ssoOptions[key];
                }
            }
            return sso;
        },
        check: function () {
            if (!initialized) {
                throw new Error("Please invoke $p.sso.init() first.");
            }
            _debug("Start checking");
            var clientUid = sso.getUid(),
                message = {
                    clientUid: clientUid, check: true, setOptions: {
                        debug: options.debug,
                        initToken: options.initToken,
                        initTime: options.initTime,
                        notifyUrl: options.notifyUrl
                    }
                };
            if (sIframe) {
                return serverWindow && _sendMsgTo(serverWindow, message);
            }
            sIframe = d.createElement("iframe");
            sIframe.src = options.ssoServer + options.ssoCheckUri;
            sIframe.width = sIframe.height = sIframe.frameBorder = 0;
            sIframe.style.display = "none";
            _listen(sIframe, "load", function () {
                serverWindow = sIframe.contentWindow;
                _sendMsgTo(serverWindow, message);
            });
            body.appendChild(sIframe);
        },
        stopCheck: function () {
            _sendMsgTo(serverWindow, {stopCheck: true});
        },
        getUid: function () {
            return simpleStorage && simpleStorage.get("uid");
        },
        setUid: function (uid, ttl) {
            _debug("Set uid: " + uid);
            simpleStorage && simpleStorage.set("uid", uid, {TTL: ttl || 0});
        }
    };

    function _clientLogin(loginData) {
        _debug("Client login");
        _debug(loginData);
        notifyForm.notifyField.value = loginData;
        notifyForm.submit();
        _debug("App notification sent");
    }

    function _clientLogout() {
        vars.clientUid && _debug("Client logout");
        sso.setUid(null);
        notifyForm.notifyField.value = "";
        notifyForm.submit();
        _debug("App notification sent");
    }

    function _clientOnMessage(e) {
        var data = _getJson(e.data);
        _debug("Handle in client");
        if (data.login) {
            _clientLogin(data.login);
        }
        if (data.setUid) {
            sso.setUid(data.setUid);
        }
        if (data.logout) {
            _clientLogout();
        }
    }

    function _debug(info) {
        w.console && options.debug && (w.console.trace ? w.console.trace(info) : w.console.log(info));
    }

    function _getJson(data) {
        var jsonData;
        try {
            jsonData = w.JSON.parse(data);
            return jsonData;
        } catch (E) {
            return data;
        }
    }

    function _jsonStringify(obj) {
        return w.JSON.stringify(obj);
    }

    function _listen(host, eventName, callback) {
        if ("addEventListener" in host) {
            host.addEventListener(eventName, callback, false);
        } else {
            host.attachEvent("on" + eventName, callback);
        }
    }

    function _sendMsgTo(frame, message) {
        frame.postMessage(typeof message == "string" ? message : _jsonStringify(message), msgTargetOrigin);
    }

    function _serverCheck() {
        var clientUid = vars.clientUid,
            serverUid = sso.getUid();
        clientWindow = w.parent;
        timerServerCheck = setTimeout(function () {
            _serverCheck.apply(sso);
        }, 1000);
        if (clientUid == serverUid) {
            return;
        }
        _debug("Server uid: " + serverUid);
        if (serverUid) {
            // Login
            _debug("Login: " + serverUid);
            $.post(options.ssoServer + options.ssoServerCheckUri, {
                notifyUrl: options.notifyUrl,
                initTime: options.initTime,
                initToken: options.initToken
            }, function (data) {
                vars.clientUid = serverUid;
                _debug(data);
                _sendMsgTo(clientWindow, {login: data["user_data"]});
            }, "json");
            _sendMsgTo(clientWindow, {setUid: serverUid});
        } else {
            // Logout
            clientUid && _debug("Logout");
            vars.clientUid = null;
            _sendMsgTo(clientWindow, {logout: true});
        }
    }

    function _serverOnMessage(e) {
        var data = _getJson(e.data),
            clientUid,
            setOptions;
        if (setOptions = data.setOptions) {
            sso.setOptions(setOptions);
        }
        _debug("Handle in iframe");
        if (clientUid = data.clientUid) {
            _debug("Aware client uid: " + clientUid);
            vars.clientUid = clientUid;
        }
        if (data.check) {
            _serverCheck.apply(sso);
        }
        if (data.stopCheck) {
            _debug("Stop checking");
            clearTimeout(timerServerCheck);
        }
    }

    if (!w.JSON) {
        throw new Error("Please include JSON support script to your page.");
    }
    _listen(w, "load", function () {
        sso.init();
    });
}(window, document);
