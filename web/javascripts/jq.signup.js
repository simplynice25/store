(function () {

    var google = {
        btn: $(".google-btn"),
        googleCallBack: function(authResult) {
            if (authResult["status"]["method"] === "PROMPT") {
                if (authResult["status"]["signed_in"]) {
                    google.googleLogin();
                }
            }
        },
        googleLogin: function() {
            gapi.client.load("plus","v1", function() {
                var request = gapi.client.plus.people.get({
                    "userId": "me"
                });

                request.execute(function(response) {
                    var userData = {
                        id : response.id,
                        type: "google",
                        firstName : response.name.givenName,
                        lastName : response.name.familyName,
                        email : response.emails[0].value,
                        profile: response.url
                    };
                    socialRegistration.process(userData);
                });
            });
        }
    };

    var facebook = {
        btn: $('.facebook-btn'),
        facebookCallBack: function(response) {
            if (response.status === "connected") {
                facebook.facebookLogin();
            } else if (response.status === "not_authorized") {
                facebook.facebookLogin();
            } else {
                alert("Please log into Facebook.");
            }
        },
        facebookLogin: function() {
            FB.login(function(response) {
                if (response.authResponse) {
                    access_token = response.authResponse.accessToken;
                    user_id = response.authResponse.userID;

                    FB.api("/me", function(response) {
                        var userData = {
                            id : response.id,
                            type: "facebook",
                            firstName : response.first_name,
                            lastName : response.last_name,
                            email : response.email,
                            profile: response.link
                        };
                        socialRegistration.process(userData);
                    });
                }
            }, { scope : "public_profile,email" });
        }
    };

    google.btn.click(function(){
        gapi.auth.signIn({"callback": google.googleCallBack});
    });

    facebook.btn.click(function(){
        FB.getLoginStatus(function(response) {
          facebook.facebookCallBack(response);
        });
    });

    var socialRegistration = {
        process: function(userData) {
            var type = userData.type, btn = $("." + type + "-btn").find("i.fa");
            btn.removeClass("fa-" + type).addClass("fa-spin fa-spinner");
            $.get("connect/login-auth", userData)
            .done(function(data) {
                data = jQuery.parseJSON( data );
                if (data.message === "success") {
                    window.location.href = window.location.pathname;
                }
            })
            .fail(function() {
                console.log("Failed to process ...");
            })
            .always(function() {
                btn.removeClass("fa-spin fa-spinner").addClass("fa-" + type);
            })
        }
    };

})(jQuery, window, document);
window.fbAsyncInit = function() {FB.init({appId:$(".facebook-btn").data("fb-app-id"),cookie:true,xfbml:true,version:'v2.2'})};
(function(d, s, id) {
var js, fjs = d.getElementsByTagName(s)[0];
if (d.getElementById(id)) return;
js = d.createElement(s); js.id = id;
js.src = "//connect.facebook.net/en_US/sdk.js";
fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));