WB.core.load(['connect', 'client'], function() {
    var cfg = {
        key: '2060512444',
        xdpath: 'http://vweb.sinaapp.com/xd.html'
    };
    WB.connect.init(cfg);
    WB.client.init(cfg);
});

function login() {
    WB.connect.login(function() {
        alert( 'ok' );
    });
}

function logout() {
    WB.connect.logout(function() {
        alert( 'logout' );
    });
}

function run_api_cmd() {

    var api_method_select = jQuery('#api_method_select')[0];

    var method = api_method_select.value;

    var api_type_select = jQuery('#api_type_select')[0];

    var type = api_type_select.value;

    var args = {};
    var keys = jQuery('.key_ipt');
    var values = jQuery('.value_ipt');
    for(var i = 0;i < keys.length; i ++ ) {
        if(keys[i].value != '' && values[i].value != '') {
            args[keys[i].value] = values[i].value;
        }
    }

    WB.client.parseCMD(method, function(sResult, bStatus) {
        log((bStatus == true) + '\n' + JSON.stringify(sResult, null, '\t'));
    }, args, {
        'method': type
    });
}
