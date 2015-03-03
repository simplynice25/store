(function () {
    /**
     * @google.btn = google button
     * @google.googleCallBack & @google.googleLogin = popup for google oauth, if logged out of google
     * google login form will show, if login will show the scope else will automatically close
     * and send a request for user informations
     * once user informations received will send a request to server to automatically log the user
     **/
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
                        email : (response.emails && response.emails[0]) ? response.emails[0].value : null,
                        profile: response.url
                    };
                    socialRegistration.process(userData);
                });
            });
        }
    };

    /**
     * @facebook.btn = facebook button
     * @facebook.facebookCallBack & @facebook.facebookLogin = popup for facebook oauth, if logged out of facebook
     * facebook login form will show, if login will show the scope else will automatically close
     * and send a request for user informations
     * once user informations received will send a request to server to automatically log the user
     **/
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
                            email : (response.email) ? response.email : null,
                            profile: response.link
                        };
                        socialRegistration.process(userData);
                    });
                }
            }, { scope : "public_profile,email" });
        }
    };

    /* Google login button trigger */
    google.btn.click(function(){
        google.btn.find("i.fa").removeClass("fa-google").addClass("fa-circle-o-notch fa-spin");
        gapi.auth.signIn({"callback": google.googleCallBack});
    });

    /* Facebook login button trigger */
    facebook.btn.click(function(){
        facebook.btn.find("i.fa").removeClass("fa-facebook").addClass("fa-circle-o-notch fa-spin");
        FB.getLoginStatus(function(response) {
          facebook.facebookCallBack(response);
        });
    });

    /**
     * @socialRegistration.userData = this serve as a variable to store user data
     * @socialRegistration.process = this will process the user data and log the user, also checks for email
     * if user allow the system to get his/her email, if not
     * @socialRegistration.validateAcc will take cover which will check if the user is already registered then
     * it will can @socialRegistration.process else 
     * @socialRegistration.emailForm will take cover which will unhide the email form
     * where user will input his/her email and submit and call again @socialRegistration.process to log the user
     */
    var socialRegistration = {
        userData: null,
        process: function(userData) {
            if ( ! userData.email) {
                socialRegistration.validateAcc(userData);
                return false;
            }

            var type = userData.type, btn = $("." + type + "-btn").find("i.fa");

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
                btn.removeClass("fa-circle-o-notch fa-spin").addClass("fa-" + type);
            })
        },
        validateAcc: function(userData) {
            $.get("connect/validate-account", userData)
            .done(function(data) {
                data = jQuery.parseJSON( data );
                if (data.message == "invalid_acc") {
                    socialRegistration.userData = userData;
                    socialRegistration.emailForm();
                } else {
                    userData.email = data.message;
                    socialRegistration.process(userData);
                }
            })
            .fail(function() {
                console.log("Failed to process ...");
            });

            return true;
        },
        emailForm: function() {
            var noEmail = $('.no-email'),
                emailBox = noEmail.find('input[name=email]'),
                emailBtn = noEmail.find('button.btn');

            noEmail.removeClass('hide');
            emailBox.focus();
            emailBtn.click(function(){
                var email = emailBox.val();
                if ( ! email) {
                    emailBox.focus();
                } else {
                    noEmail.addClass('hide');
                    socialRegistration.userData.email = email;
                    socialRegistration.process(socialRegistration.userData);
                }
            });
        },
    };

})(jQuery, window, document);

/* Facebook Requirements for oAuth */
window.fbAsyncInit = function() {FB.init({appId:$(".facebook-btn").data("fb-app-id"),cookie:true,xfbml:true,version:'v2.2'})};
(function(d, s, id) {
var js, fjs = d.getElementsByTagName(s)[0];
if (d.getElementById(id)) return;
js = d.createElement(s); js.id = id;
js.src = "//connect.facebook.net/en_US/sdk.js";
fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));