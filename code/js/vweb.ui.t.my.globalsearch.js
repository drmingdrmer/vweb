$.extend( $.vweb.ui.t.my, { globalsearch: {
    init : function(){
        var self = this;
        self.buttonSubmit = self._elt.find( ".f_submit" );
        self.formParam = self._elt.find( "form.g_search" );

        var searchLoader = $.vweb.backend.weibo.create_loader(
            'statuses/search',
            {
                args: function() { return self.formParam.serialize(); },
                cb: [ '$.vweb.ui.t.list.show' ]
            }
        );

        self.buttonSubmit.click( searchLoader );
    }
} } );
