
function TF_CreateAccount(url) {
    new Ajax.Request(url, {
        method: "get",
        parameters: {email: $('transfluent_create_email').value, terms: "ok"},
        onSuccess: function(transport) {
            try {
                if (transport.responseText.isJSON()) {
                    var response = transport.responseText.evalJSON();
                    switch (response.status) {
                        case 'ERROR':
                            alert('Error! ' + response.error.message);
                            break;
                        case 'OK':
                            location.reload();
                            break;
                    }
                }
            } catch (e) {
                alert(e);
                alert(transport.responseText);
            }
        }
    });
}

var TransfluentAccount = Class.create();
TransfluentAccount.prototype = {
    initialize: function(urls) {
        this.cUrl = urls.cUrl;
        this.aUrl = urls.aUrl;
        this.lUrl = urls.lUrl;

        this.cEmail = '';
        this.lEmail = '';
        this.pass   = '';
        this.terms  = '';
    },

    setCreateData: function() {
        this.cEmail = $('transfluent_create_email').value;
    },

    setAuthData: function() {
        this.lEmail = $('transfluent_login_email').value;
        this.pass   = $('transfluent_login_password').value;
    },

    accountCreate: function() {
        if (this.cEmail == '') {
            alert('Please provide an email address.');
            return;
        }

        if (!$('transfluent_create_terms').checked) {
            alert('Please accept Terms of Service.');
            return;
        }

        new Ajax.Request(this.cUrl, {
            method: "get",
            parameters: { email: this.cEmail, terms: 'ok'},
            onSuccess: function(transport) {
                try {
                    if (transport.responseText.isJSON()) {
                        var response = transport.responseText.evalJSON();

                        switch (response.status) {
                            case 'ERROR':
                                alert('Error! ' + response.error.message);
                                break;
                            case 'OK':
                                window.location.reload();
                                break;
                        }
                    }
                } catch (e) {
                    alert(e);
                    alert(transport.responseText);
                }
            }.bind(this)
        });
    },

    accountAuthenticate: function() {
        new Ajax.Request(this.aUrl, {
            method: "get",
            parameters: { email: this.lEmail, password: this.pass },
            onSuccess: function(transport) {
                try {
                    if (transport.responseText.isJSON()) {
                        var response = transport.responseText.evalJSON();
                        if (response.status == 'ok') {
                            window.location.reload();
                        } else {
                            alert(response.message);
                        }
                    }
                } catch (e) {
                    alert(e);
                    alert(transport.responseText);
                }
            }.bind(this)
        });
    },

    accountLogout: function() {
        new Ajax.Request(this.lUrl, {
            onSuccess: function(transport) {
                try {
                    if (transport.responseText.isJSON()) {
                        var response = transport.responseText.evalJSON();
                        if (response.status == 'OK') {
                            window.location.reload();
                            return;
                        }
                        alert('Failed to logout. Please try again!');
                    }
                } catch (e) {
                    alert(e);
                    alert(transport.responseText);
                }
            }.bind(this)
        });
    }
};

var Estimate = Class.create();
Estimate.prototype = {
    initialize: function(store, urls) {
        this.prepdata = urls.prepdata;
        this.savedata = urls.savedata;
        this.pushtext = urls.pushtext;
        this.esttexts = urls.esttexts;

        this.store      = store;
        this.language   = $('translateto').value;
        this.quality    = $('est_qual').down().value;

        this.loader_note = document
            .createElement('small')
            .writeAttribute('id', 'est_loader')
            .writeAttribute('style', 'height: 26px; display: block; font-weight: normal; font-size: 11px; line-height: 13px; padding-top: 8px;');
    },

    prepareData: function() {
        new Ajax.Request(this.prepdata, {
            onLoading: function() {
                this.loader_note.update('Preparing data...');
                $('loading_mask_loader').insert(this.loader_note);
            }.bind(this),
            onSuccess: function(transport) {
                var response = transport.responseText || "An error occurred: server did not send a response";
                this.loader_note.update(response);
                this.saveTextData();
            }.bind(this)
        });
    },

    saveTextData: function() {
        new Ajax.Request(this.savedata, {
            parameters : {
                'lang' : this.language,
                'qual' : this.quality,
                'store' : this.store
            },
            onSuccess: function(transport) {
                var response = transport.responseText || "An error occurred: server did not send a response";
                this.loader_note.update(response);
                this.pushText();
            }.bind(this)
        });
    },

    pushText: function() {
        new Ajax.Request(this.pushtext, {
            parameters: {
                'lang' : this.language,
                'qual' : this.quality
            },
            onSuccess: function(transport) {
                var response = transport.responseText || "An error occurred: server did not send a response";
                this.loader_note.update(response);
                this.estimateTexts();
            }.bind(this)
        });
    },

    estimateTexts: function() {
        new Ajax.Request(this.esttexts, {
            method: "post",
            parameters: {
                'lang' : this.language,
                'qual' : this.quality,
                'store' : this.store
            },
            onSuccess: function(transport) {
                var response = transport.responseText || "An error occurred: server did not send a response";
                $('est_loader').remove();
                $('estimation_result').update(response);
            }.bind(this)
        });
    }
};

var PostEstimate = Class.create();
PostEstimate.prototype = {
    initialize: function(urls) {
        this.transl = urls.transl;
        this.cancel = urls.cancel;
    },

    translateEstimated: function() {
        new Ajax.Request(this.transl, {
            onSuccess: function() {
                window.location.reload();
            }.bind(this),
            onFailure: function() {
                alert('Items were not translated!');
            }.bind(this)
        });
    },

    cancelEstimated: function() {
        new Ajax.Request(this.cancel, {
            onSuccess: function() {
                window.location.reload();
            }.bind(this),
            onFailure: function() {
                alert('Items were not deleted!');
            }.bind(this)
        });
    }
};

function translateAll(url, store) {
    new Ajax.Request(url, {
        method: "post",
        parameters: {
            'translate_store'   : store,
            'translate_from'    : $('translateto').value,
            'translate_quality' : $$('#est_qual .translate_quality')[0].value
        },
        onSuccess: function(transport) {
            var response = transport.responseText || "An error occurred: server did not send a response";
            $('estimation_result').update(response);
        }
    });
}
