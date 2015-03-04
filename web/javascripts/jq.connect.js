$(document).ready(function(){

    var facebookBtn = ".facebook-btn",
        googleBtn = ".google-btn";

    $(facebookBtn).facebookConnect();
    $(googleBtn).googleConnect();

    window.fbAsyncInit = function() {
        FB.init({
            appId   : $(facebookBtn).data("fb-app-id"),
            cookie  : true,
            xfbml   : true,
            version : "v2.2"
        })
    };

    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, "script", "facebook-jssdk"));
});

(function($, window, document, undefined){

    var noEmail = ".no-email",
        _fb = "facebook",
        _btnFa = ["fa-" + _fb, "fa-google"],
        _btnFb = $(".facebook-btn").find("i.fa"),
        _btnGo = $(".google-btn").find("i.fa"),
        notchSpin = "fa-circle-o-notch fa-spin";

    /**
     * @GoogleConnect
     **/
    var GoogleConnect = function(el) {
        this.el = el;
        this.$el = $(el);
    };

    GoogleConnect.prototype = {
        init:function(){
            var self = this,
                _btn = self.$el;

            _btn.on("click", function(e){
                e.preventDefault();
                loadIcon(_btnGo, _btnFa[1]);

                gapi.auth.signIn({
                    "callback": function(authResult) {
                        if (authResult["status"]["method"] === "PROMPT") {
                            if (authResult["status"]["signed_in"]) {
                                self.oAuthForm();
                            }
                        }

                        if (authResult["error"] == "access_denied") {
                                unloadIcon(_btnGo, _btnFa[1]);
                        }

                    }
                });
            });
            return self;

        },
        oAuthForm:function(){
            gapi.client.load("plus","v1", function() {
                var request = gapi.client.plus.people.get({
                    "userId": "me"
                });

                request.execute(function(resp) {
                    var userData = {
                        id : resp.id,
                        type: "google",
                        firstName : resp.name.givenName,
                        lastName : resp.name.familyName,
                        email : (resp.emails && resp.emails[0]) ? resp.emails[0].value : null,
                        profile: resp.url
                    };
                    siginProcess(userData, 1);
                });
            });

            return true;
        },
    };

    GoogleConnect.defaults = GoogleConnect.prototype.defaults;
    $.fn.googleConnect = function() {
        return this.each(function(){
            new GoogleConnect(this).init();
        });
    }

    /**
     * @FacebookConnect
     **/
    var FacebookConnect = function(el) {
        this.el = el;
        this.$el = $(el);
    };

    FacebookConnect.prototype = {
        init:function() {
            var self = this,
                _btn = self.$el;

            _btn.on("click", function(e){
                e.preventDefault();
                loadIcon(_btnFb, _btnFa[0]);

                FB.getLoginStatus(function(resp) {
                    self.oAuthForm();
                });
            });
            return self;
        },
        oAuthForm: function(){
            FB.login(function(resp) {
                if (resp.authResponse) {
                    FB.api("/me", function(resp) {
                        var userData = {
                            id : resp.id,
                            type: "facebook",
                            firstName : resp.first_name,
                            lastName : resp.last_name,
                            email : (resp.email) ? resp.email : null,
                            profile: resp.link
                        };
                        siginProcess(userData, 0);
                    });
                } else {
                    unloadIcon(_btnFb, _btnFa[0]);
                }
            }, { scope : "public_profile,email" });

            return true;
        }
    };

    FacebookConnect.defaults = FacebookConnect.prototype.defaults;
    $.fn.facebookConnect = function() {
        return this.each(function(){
            new FacebookConnect(this).init();
        });
    }

    /**
     * @siginProcess, @loadIcon, @unloadIcon
     **/
    function siginProcess(userData) {
        if ( ! userData.email && userData.type == _fb) {
            FB.api("/me/permissions", "DELETE", function(response){
                if (response.success) {
                    $(noEmail).removeClass("hide");
                    unloadIcon(_btnFb, _btnFa[0]);
                }
            });

            return false;
        }

        $.get("connect/login-auth", userData)
        .done(function() {
            if (userData.type == _fb) {
                unloadIcon(_btnFb, _btnFa[0]);
            } else {
                unloadIcon(_btnGo, _btnFa[1]);
            }
        })
        .fail(function() {
            console.log("Failed to process ...");
        })
        .always(function(data) {
            data = jQuery.parseJSON( data );
            if (data.message === "success") {
                window.location.href = window.location.pathname;
            }
        });
        
        return true;
    }

    $(noEmail + " button.btn").on("click", function() {
        $(noEmail).addClass("hide");
        $("." + _fb + "-btn").trigger("click");
    });

    function loadIcon(self, fa) {
        self.removeClass(fa).addClass(notchSpin);
        $('.social-buttons a.btn').attr('disabled', true);
        return true;
    }

    function unloadIcon(self, fa) {
        self.removeClass(notchSpin).addClass(fa);
        $('.social-buttons a.btn').attr('disabled', false);
        return true;
    }

})(jQuery, window, document);