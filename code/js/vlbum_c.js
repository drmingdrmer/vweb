/**
 * jQuery.ScrollTo
 * Copyright (c) 2007-2009 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 5/25/2009
 *
 * @projectDescription Easy element scrolling using jQuery.
 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html
 * Works with jQuery +1.2.6. Tested on FF 2/3, IE 6/7/8, Opera 9.5/6, Safari 3, Chrome 1 on WinXP.
 *
 * @author Ariel Flesler
 * @version 1.4.2
 *
 * @id jQuery.scrollTo
 * @id jQuery.fn.scrollTo
 * @param {String, Number, DOMElement, jQuery, Object} target Where to scroll the matched elements.
 *	  The different options for target are:
 *		- A number position (will be applied to all axes).
 *		- A string position ('44', '100px', '+=90', etc ) will be applied to all axes
 *		- A jQuery/DOM element ( logically, child of the element to scroll )
 *		- A string selector, that will be relative to the element to scroll ( 'li:eq(2)', etc )
 *		- A hash { top:x, left:y }, x and y can be any kind of number/string like above.
*		- A percentage of the container's dimension/s, for example: 50% to go to the middle.
 *		- The string 'max' for go-to-end. 
 * @param {Number} duration The OVERALL length of the animation, this argument can be the settings object instead.
 * @param {Object,Function} settings Optional set of settings or the onAfter callback.
 *	 @option {String} axis Which axis must be scrolled, use 'x', 'y', 'xy' or 'yx'.
 *	 @option {Number} duration The OVERALL length of the animation.
 *	 @option {String} easing The easing method for the animation.
 *	 @option {Boolean} margin If true, the margin of the target element will be deducted from the final position.
 *	 @option {Object, Number} offset Add/deduct from the end position. One number for both axes or { top:x, left:y }.
 *	 @option {Object, Number} over Add/deduct the height/width multiplied by 'over', can be { top:x, left:y } when using both axes.
 *	 @option {Boolean} queue If true, and both axis are given, the 2nd axis will only be animated after the first one ends.
 *	 @option {Function} onAfter Function to be called after the scrolling ends. 
 *	 @option {Function} onAfterFirst If queuing is activated, this function will be called after the first scrolling ends.
 * @return {jQuery} Returns the same jQuery object, for chaining.
 *
 * @desc Scroll to a fixed position
 * @example $('div').scrollTo( 340 );
 *
 * @desc Scroll relatively to the actual position
 * @example $('div').scrollTo( '+=340px', { axis:'y' } );
 *
 * @dec Scroll using a selector (relative to the scrolled element)
 * @example $('div').scrollTo( 'p.paragraph:eq(2)', 500, { easing:'swing', queue:true, axis:'xy' } );
 *
 * @ Scroll to a DOM element (same for jQuery object)
 * @example var second_child = document.getElementById('container').firstChild.nextSibling;
 *			$('#container').scrollTo( second_child, { duration:500, axis:'x', onAfter:function(){
 *				alert('scrolled!!');																   
 *			}});
 *
 * @desc Scroll on both axes, to different values
 * @example $('div').scrollTo( { top: 300, left:'+=200' }, { axis:'xy', offset:-20 } );
 */
;(function( $ ){
	
	var $scrollTo = $.scrollTo = function( target, duration, settings ){
		$(window).scrollTo( target, duration, settings );
	};

	$scrollTo.defaults = {
		axis:'xy',
		duration: parseFloat($.fn.jquery) >= 1.3 ? 0 : 1
	};

	// Returns the element that needs to be animated to scroll the window.
	// Kept for backwards compatibility (specially for localScroll & serialScroll)
	$scrollTo.window = function( scope ){
		return $(window)._scrollable();
	};

	// Hack, hack, hack :)
	// Returns the real elements to scroll (supports window/iframes, documents and regular nodes)
	$.fn._scrollable = function(){
		return this.map(function(){
			var elem = this,
				isWin = !elem.nodeName || $.inArray( elem.nodeName.toLowerCase(), ['iframe','#document','html','body'] ) != -1;

				if( !isWin )
					return elem;

			var doc = (elem.contentWindow || elem).document || elem.ownerDocument || elem;
			
			return $.browser.safari || doc.compatMode == 'BackCompat' ?
				doc.body : 
				doc.documentElement;
		});
	};

	$.fn.scrollTo = function( target, duration, settings ){
		if( typeof duration == 'object' ){
			settings = duration;
			duration = 0;
		}
		if( typeof settings == 'function' )
			settings = { onAfter:settings };
			
		if( target == 'max' )
			target = 9e9;
			
		settings = $.extend( {}, $scrollTo.defaults, settings );
		// Speed is still recognized for backwards compatibility
		duration = duration || settings.speed || settings.duration;
		// Make sure the settings are given right
		settings.queue = settings.queue && settings.axis.length > 1;
		
		if( settings.queue )
			// Let's keep the overall duration
			duration /= 2;
		settings.offset = both( settings.offset );
		settings.over = both( settings.over );

		return this._scrollable().each(function(){
			var elem = this,
				$elem = $(elem),
				targ = target, toff, attr = {},
				win = $elem.is('html,body');

			switch( typeof targ ){
				// A number will pass the regex
				case 'number':
				case 'string':
					if( /^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(targ) ){
						targ = both( targ );
						// We are done
						break;
					}
					// Relative selector, no break!
					targ = $(targ,this);
				case 'object':
					// DOMElement / jQuery
					if( targ.is || targ.style )
						// Get the real position of the target 
						toff = (targ = $(targ)).offset();
			}
			$.each( settings.axis.split(''), function( i, axis ){
				var Pos	= axis == 'x' ? 'Left' : 'Top',
					pos = Pos.toLowerCase(),
					key = 'scroll' + Pos,
					old = elem[key],
					max = $scrollTo.max(elem, axis);

				if( toff ){// jQuery / DOMElement
					attr[key] = toff[pos] + ( win ? 0 : old - $elem.offset()[pos] );

					// If it's a dom element, reduce the margin
					if( settings.margin ){
						attr[key] -= parseInt(targ.css('margin'+Pos)) || 0;
						attr[key] -= parseInt(targ.css('border'+Pos+'Width')) || 0;
					}
					
					attr[key] += settings.offset[pos] || 0;
					
					if( settings.over[pos] )
						// Scroll to a fraction of its width/height
						attr[key] += targ[axis=='x'?'width':'height']() * settings.over[pos];
				}else{ 
					var val = targ[pos];
					// Handle percentage values
					attr[key] = val.slice && val.slice(-1) == '%' ? 
						parseFloat(val) / 100 * max
						: val;
				}

				// Number or 'number'
				if( /^\d+$/.test(attr[key]) )
					// Check the limits
					attr[key] = attr[key] <= 0 ? 0 : Math.min( attr[key], max );

				// Queueing axes
				if( !i && settings.queue ){
					// Don't waste time animating, if there's no need.
					if( old != attr[key] )
						// Intermediate animation
						animate( settings.onAfterFirst );
					// Don't animate this axis again in the next iteration.
					delete attr[key];
				}
			});

			animate( settings.onAfter );			

			function animate( callback ){
				$elem.animate( attr, duration, settings.easing, callback && function(){
					callback.call(this, target, settings);
				});
			};

		}).end();
	};
	
	// Max scrolling position, works on quirks mode
	// It only fails (not too badly) on IE, quirks mode.
	$scrollTo.max = function( elem, axis ){
		var Dim = axis == 'x' ? 'Width' : 'Height',
			scroll = 'scroll'+Dim;
		
		if( !$(elem).is('html,body') )
			return elem[scroll] - $(elem)[Dim.toLowerCase()]();
		
		var size = 'client' + Dim,
			html = elem.ownerDocument.documentElement,
			body = elem.ownerDocument.body;

		return Math.max( html[scroll], body[scroll] ) 
			 - Math.min( html[size]  , body[size]   );
			
	};

	function both( val ){
		return typeof val == 'object' ? val : { top:val, left:val };
	};

})( jQuery );;
(function(a){var r=a.fn.domManip,d="_tmplitem",q=/^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,b={},f={},e,p={key:0,data:{}},h=0,c=0,l=[];function g(e,d,g,i){var c={data:i||(d?d.data:{}),_wrap:d?d._wrap:null,tmpl:null,parent:d||null,nodes:[],calls:u,nest:w,wrap:x,html:v,update:t};e&&a.extend(c,e,{nodes:[],parent:d});if(g){c.tmpl=g;c._ctnt=c._ctnt||c.tmpl(a,c);c.key=++h;(l.length?f:b)[h]=c}return c}a.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(f,d){a.fn[f]=function(n){var g=[],i=a(n),k,h,m,l,j=this.length===1&&this[0].parentNode;e=b||{};if(j&&j.nodeType===11&&j.childNodes.length===1&&i.length===1){i[d](this[0]);g=this}else{for(h=0,m=i.length;h<m;h++){c=h;k=(h>0?this.clone(true):this).get();a.fn[d].apply(a(i[h]),k);g=g.concat(k)}c=0;g=this.pushStack(g,f,i.selector)}l=e;e=null;a.tmpl.complete(l);return g}});a.fn.extend({tmpl:function(d,c,b){return a.tmpl(this[0],d,c,b)},tmplItem:function(){return a.tmplItem(this[0])},template:function(b){return a.template(b,this[0])},domManip:function(d,l,j){if(d[0]&&d[0].nodeType){var f=a.makeArray(arguments),g=d.length,i=0,h;while(i<g&&!(h=a.data(d[i++],"tmplItem")));if(g>1)f[0]=[a.makeArray(d)];if(h&&c)f[2]=function(b){a.tmpl.afterManip(this,b,j)};r.apply(this,f)}else r.apply(this,arguments);c=0;!e&&a.tmpl.complete(b);return this}});a.extend({tmpl:function(d,h,e,c){var j,k=!c;if(k){c=p;d=a.template[d]||a.template(null,d);f={}}else if(!d){d=c.tmpl;b[c.key]=c;c.nodes=[];c.wrapped&&n(c,c.wrapped);return a(i(c,null,c.tmpl(a,c)))}if(!d)return[];if(typeof h==="function")h=h.call(c||{});e&&e.wrapped&&n(e,e.wrapped);j=a.isArray(h)?a.map(h,function(a){return a?g(e,c,d,a):null}):[g(e,c,d,h)];return k?a(i(c,null,j)):j},tmplItem:function(b){var c;if(b instanceof a)b=b[0];while(b&&b.nodeType===1&&!(c=a.data(b,"tmplItem"))&&(b=b.parentNode));return c||p},template:function(c,b){if(b){if(typeof b==="string")b=o(b);else if(b instanceof a)b=b[0]||{};if(b.nodeType)b=a.data(b,"tmpl")||a.data(b,"tmpl",o(b.innerHTML));return typeof c==="string"?(a.template[c]=b):b}return c?typeof c!=="string"?a.template(null,c):a.template[c]||a.template(null,q.test(c)?c:a(c)):null},encode:function(a){return(""+a).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;")}});a.extend(a.tmpl,{tag:{tmpl:{_default:{$2:"null"},open:"if($notnull_1){_=_.concat($item.nest($1,$2));}"},wrap:{_default:{$2:"null"},open:"$item.calls(_,$1,$2);_=[];",close:"call=$item.calls();_=call._.concat($item.wrap(call,_));"},each:{_default:{$2:"$index, $value"},open:"if($notnull_1){$.each($1a,function($2){with(this){",close:"}});}"},"if":{open:"if(($notnull_1) && $1a){",close:"}"},"else":{_default:{$1:"true"},open:"}else if(($notnull_1) && $1a){"},html:{open:"if($notnull_1){_.push($1a);}"},"=":{_default:{$1:"$data"},open:"if($notnull_1){_.push($.encode($1a));}"},"!":{open:""}},complete:function(){b={}},afterManip:function(f,b,d){var e=b.nodeType===11?a.makeArray(b.childNodes):b.nodeType===1?[b]:[];d.call(f,b);m(e);c++}});function i(e,g,f){var b,c=f?a.map(f,function(a){return typeof a==="string"?e.key?a.replace(/(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g,"$1 "+d+'="'+e.key+'" $2'):a:i(a,e,a._ctnt)}):e;if(g)return c;c=c.join("");c.replace(/^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/,function(f,c,e,d){b=a(e).get();m(b);if(c)b=j(c).concat(b);if(d)b=b.concat(j(d))});return b?b:j(c)}function j(c){var b=document.createElement("div");b.innerHTML=c;return a.makeArray(b.childNodes)}function o(b){return new Function("jQuery","$item","var $=jQuery,call,_=[],$data=$item.data;with($data){_.push('"+a.trim(b).replace(/([\\'])/g,"\\$1").replace(/[\r\t\n]/g," ").replace(/\$\{([^\}]*)\}/g,"{{= $1}}").replace(/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g,function(m,l,j,d,b,c,e){var i=a.tmpl.tag[j],h,f,g;if(!i)throw"Template command not found: "+j;h=i._default||[];if(c&&!/\w$/.test(b)){b+=c;c=""}if(b){b=k(b);e=e?","+k(e)+")":c?")":"";f=c?b.indexOf(".")>-1?b+c:"("+b+").call($item"+e:b;g=c?f:"(typeof("+b+")==='function'?("+b+").call($item):("+b+"))"}else g=f=h.$1||"null";d=k(d);return"');"+i[l?"close":"open"].split("$notnull_1").join(b?"typeof("+b+")!=='undefined' && ("+b+")!=null":"true").split("$1a").join(g).split("$1").join(f).split("$2").join(d?d.replace(/\s*([^\(]+)\s*(\((.*?)\))?/g,function(d,c,b,a){a=a?","+a+")":b?")":"";return a?"("+c+").call($item"+a:d}):h.$2||"")+"_.push('"})+"');}return _;")}function n(c,b){c._wrap=i(c,true,a.isArray(b)?b:[q.test(b)?b:a(b).html()]).join("")}function k(a){return a?a.replace(/\\'/g,"'").replace(/\\\\/g,"\\"):null}function s(b){var a=document.createElement("div");a.appendChild(b.cloneNode(true));return a.innerHTML}function m(o){var n="_"+c,k,j,l={},e,p,i;for(e=0,p=o.length;e<p;e++){if((k=o[e]).nodeType!==1)continue;j=k.getElementsByTagName("*");for(i=j.length-1;i>=0;i--)m(j[i]);m(k)}function m(j){var p,i=j,k,e,m;if(m=j.getAttribute(d)){while(i.parentNode&&(i=i.parentNode).nodeType===1&&!(p=i.getAttribute(d)));if(p!==m){i=i.parentNode?i.nodeType===11?0:i.getAttribute(d)||0:0;if(!(e=b[m])){e=f[m];e=g(e,b[i]||f[i],null,true);e.key=++h;b[h]=e}c&&o(m)}j.removeAttribute(d)}else if(c&&(e=a.data(j,"tmplItem"))){o(e.key);b[e.key]=e;i=a.data(j.parentNode,"tmplItem");i=i?i.key:0}if(e){k=e;while(k&&k.key!=i){k.nodes.push(j);k=k.parent}delete e._ctnt;delete e._wrap;a.data(j,"tmplItem",e)}function o(a){a=a+n;e=l[a]=l[a]||g(e,b[e.parent.key+n]||e.parent,null,true)}}}function u(a,d,c,b){if(!a)return l.pop();l.push({_:a,tmpl:d,item:this,data:c,options:b})}function w(d,c,b){return a.tmpl(a.template(d),c,b,this)}function x(b,d){var c=b.options||{};c.wrapped=d;return a.tmpl(a.template(b.tmpl),b.data,c,b.item)}function v(d,c){var b=this._wrap;return a.map(a(a.isArray(b)?b.join(""):b).filter(d||"*"),function(a){return c?a.innerText||a.textContent:a.outerHTML||s(a)})}function t(){var b=this.nodes;a.tmpl(null,null,null,this).insertBefore(b[0]);a(b).remove()}})(jQuery);
jQuery.fn.DefaultValue = function(text){
    return this.each(function(){

            if ( this.__default_value_inited__ ) {
                return;
            }

            this.__default_value_inited__ = true;

            //Make sure we're dealing with text-based form fields
            if(this.type != 'text' && this.type != 'password' && this.type != 'textarea')
                return;

            //Store field reference
            var fld_current=this;

            //Set value initially if none are specified
            if( this.value=='' || this.value == text ) {
                this.value=text;
            } else {
                //Other value exists - ignore
                return;
            }

            //Remove values on focus
            $(this).focus(function() {
                    if(this.value==text || this.value=='')
                        this.value='';
                });

            //Place values back on blur
            $(this).blur(function() {
                    if(this.value==text || this.value=='')
                        this.value=text;
                });

            //Capture parent form submission
            //Remove field values that are still default
            $(this).parents("form").each(function() {
                    //Bind parent form submit
                    $(this).submit(function() {
                            if(fld_current.value==text) {
                                fld_current.value='';
                            }
                        });
                });
        });
};
;
/*
    http://www.JSON.org/json2.js
    2010-03-20

    Public Domain.

    NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.

    See http://www.JSON.org/js.html


    This code should be minified before deployment.
    See http://javascript.crockford.com/jsmin.html

    USE YOUR OWN COPY. IT IS EXTREMELY UNWISE TO LOAD CODE FROM SERVERS YOU DO
    NOT CONTROL.


    This file creates a global JSON object containing two methods: stringify
    and parse.

        JSON.stringify(value, replacer, space)
            value       any JavaScript value, usually an object or array.

            replacer    an optional parameter that determines how object
                        values are stringified for objects. It can be a
                        function or an array of strings.

            space       an optional parameter that specifies the indentation
                        of nested structures. If it is omitted, the text will
                        be packed without extra whitespace. If it is a number,
                        it will specify the number of spaces to indent at each
                        level. If it is a string (such as '\t' or '&nbsp;'),
                        it contains the characters used to indent at each level.

            This method produces a JSON text from a JavaScript value.

            When an object value is found, if the object contains a toJSON
            method, its toJSON method will be called and the result will be
            stringified. A toJSON method does not serialize: it returns the
            value represented by the name/value pair that should be serialized,
            or undefined if nothing should be serialized. The toJSON method
            will be passed the key associated with the value, and this will be
            bound to the value

            For example, this would serialize Dates as ISO strings.

                Date.prototype.toJSON = function (key) {
                    function f(n) {
                        // Format integers to have at least two digits.
                        return n < 10 ? '0' + n : n;
                    }

                    return this.getUTCFullYear()   + '-' +
                         f(this.getUTCMonth() + 1) + '-' +
                         f(this.getUTCDate())      + 'T' +
                         f(this.getUTCHours())     + ':' +
                         f(this.getUTCMinutes())   + ':' +
                         f(this.getUTCSeconds())   + 'Z';
                };

            You can provide an optional replacer method. It will be passed the
            key and value of each member, with this bound to the containing
            object. The value that is returned from your method will be
            serialized. If your method returns undefined, then the member will
            be excluded from the serialization.

            If the replacer parameter is an array of strings, then it will be
            used to select the members to be serialized. It filters the results
            such that only members with keys listed in the replacer array are
            stringified.

            Values that do not have JSON representations, such as undefined or
            functions, will not be serialized. Such values in objects will be
            dropped; in arrays they will be replaced with null. You can use
            a replacer function to replace those with JSON values.
            JSON.stringify(undefined) returns undefined.

            The optional space parameter produces a stringification of the
            value that is filled with line breaks and indentation to make it
            easier to read.

            If the space parameter is a non-empty string, then that string will
            be used for indentation. If the space parameter is a number, then
            the indentation will be that many spaces.

            Example:

            text = JSON.stringify(['e', {pluribus: 'unum'}]);
            // text is '["e",{"pluribus":"unum"}]'


            text = JSON.stringify(['e', {pluribus: 'unum'}], null, '\t');
            // text is '[\n\t"e",\n\t{\n\t\t"pluribus": "unum"\n\t}\n]'

            text = JSON.stringify([new Date()], function (key, value) {
                return this[key] instanceof Date ?
                    'Date(' + this[key] + ')' : value;
            });
            // text is '["Date(---current time---)"]'


        JSON.parse(text, reviver)
            This method parses a JSON text to produce an object or array.
            It can throw a SyntaxError exception.

            The optional reviver parameter is a function that can filter and
            transform the results. It receives each of the keys and values,
            and its return value is used instead of the original value.
            If it returns what it received, then the structure is not modified.
            If it returns undefined then the member is deleted.

            Example:

            // Parse the text. Values that look like ISO date strings will
            // be converted to Date objects.

            myData = JSON.parse(text, function (key, value) {
                var a;
                if (typeof value === 'string') {
                    a =
/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}(?:\.\d*)?)Z$/.exec(value);
                    if (a) {
                        return new Date(Date.UTC(+a[1], +a[2] - 1, +a[3], +a[4],
                            +a[5], +a[6]));
                    }
                }
                return value;
            });

            myData = JSON.parse('["Date(09/09/2001)"]', function (key, value) {
                var d;
                if (typeof value === 'string' &&
                        value.slice(0, 5) === 'Date(' &&
                        value.slice(-1) === ')') {
                    d = new Date(value.slice(5, -1));
                    if (d) {
                        return d;
                    }
                }
                return value;
            });


    This is a reference implementation. You are free to copy, modify, or
    redistribute.
*/

/*jslint evil: true, strict: false */

/*members "", "\b", "\t", "\n", "\f", "\r", "\"", JSON, "\\", apply,
    call, charCodeAt, getUTCDate, getUTCFullYear, getUTCHours,
    getUTCMinutes, getUTCMonth, getUTCSeconds, hasOwnProperty, join,
    lastIndex, length, parse, prototype, push, replace, slice, stringify,
    test, toJSON, toString, valueOf
*/


// Create a JSON object only if one does not already exist. We create the
// methods in a closure to avoid creating global variables.

if (!this.JSON) {
    this.JSON = {};
}

(function () {

    function f(n) {
        // Format integers to have at least two digits.
        return n < 10 ? '0' + n : n;
    }

    if (typeof Date.prototype.toJSON !== 'function') {

        Date.prototype.toJSON = function (key) {

            return isFinite(this.valueOf()) ?
                   this.getUTCFullYear()   + '-' +
                 f(this.getUTCMonth() + 1) + '-' +
                 f(this.getUTCDate())      + 'T' +
                 f(this.getUTCHours())     + ':' +
                 f(this.getUTCMinutes())   + ':' +
                 f(this.getUTCSeconds())   + 'Z' : null;
        };

        String.prototype.toJSON =
        Number.prototype.toJSON =
        Boolean.prototype.toJSON = function (key) {
            return this.valueOf();
        };
    }

    var cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        escapable = /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        gap,
        indent,
        meta = {    // table of character substitutions
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        },
        rep;


    function quote(string) {

// If the string contains no control characters, no quote characters, and no
// backslash characters, then we can safely slap some quotes around it.
// Otherwise we must also replace the offending characters with safe escape
// sequences.

        escapable.lastIndex = 0;
        return escapable.test(string) ?
            '"' + string.replace(escapable, function (a) {
                var c = meta[a];
                return typeof c === 'string' ? c :
                    '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
            }) + '"' :
            '"' + string + '"';
    }


    function str(key, holder) {

// Produce a string from holder[key].

        var i,          // The loop counter.
            k,          // The member key.
            v,          // The member value.
            length,
            mind = gap,
            partial,
            value = holder[key];

// If the value has a toJSON method, call it to obtain a replacement value.

        if (value && typeof value === 'object' &&
                typeof value.toJSON === 'function') {
            value = value.toJSON(key);
        }

// If we were called with a replacer function, then call the replacer to
// obtain a replacement value.

        if (typeof rep === 'function') {
            value = rep.call(holder, key, value);
        }

// What happens next depends on the value's type.

        switch (typeof value) {
        case 'string':
            return quote(value);

        case 'number':

// JSON numbers must be finite. Encode non-finite numbers as null.

            return isFinite(value) ? String(value) : 'null';

        case 'boolean':
        case 'null':

// If the value is a boolean or null, convert it to a string. Note:
// typeof null does not produce 'null'. The case is included here in
// the remote chance that this gets fixed someday.

            return String(value);

// If the type is 'object', we might be dealing with an object or an array or
// null.

        case 'object':

// Due to a specification blunder in ECMAScript, typeof null is 'object',
// so watch out for that case.

            if (!value) {
                return 'null';
            }

// Make an array to hold the partial results of stringifying this object value.

            gap += indent;
            partial = [];

// Is the value an array?

            if (Object.prototype.toString.apply(value) === '[object Array]') {

// The value is an array. Stringify every element. Use null as a placeholder
// for non-JSON values.

                length = value.length;
                for (i = 0; i < length; i += 1) {
                    partial[i] = str(i, value) || 'null';
                }

// Join all of the elements together, separated with commas, and wrap them in
// brackets.

                v = partial.length === 0 ? '[]' :
                    gap ? '[\n' + gap +
                            partial.join(',\n' + gap) + '\n' +
                                mind + ']' :
                          '[' + partial.join(',') + ']';
                gap = mind;
                return v;
            }

// If the replacer is an array, use it to select the members to be stringified.

            if (rep && typeof rep === 'object') {
                length = rep.length;
                for (i = 0; i < length; i += 1) {
                    k = rep[i];
                    if (typeof k === 'string') {
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            } else {

// Otherwise, iterate through all of the keys in the object.

                for (k in value) {
                    if (Object.hasOwnProperty.call(value, k)) {
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            }

// Join all of the member texts together, separated with commas,
// and wrap them in braces.

            v = partial.length === 0 ? '{}' :
                gap ? '{\n' + gap + partial.join(',\n' + gap) + '\n' +
                        mind + '}' : '{' + partial.join(',') + '}';
            gap = mind;
            return v;
        }
    }

// If the JSON object does not yet have a stringify method, give it one.

    if (typeof JSON.stringify !== 'function') {
        JSON.stringify = function (value, replacer, space) {

// The stringify method takes a value and an optional replacer, and an optional
// space parameter, and returns a JSON text. The replacer can be a function
// that can replace values, or an array of strings that will select the keys.
// A default replacer method can be provided. Use of the space parameter can
// produce text that is more easily readable.

            var i;
            gap = '';
            indent = '';

// If the space parameter is a number, make an indent string containing that
// many spaces.

            if (typeof space === 'number') {
                for (i = 0; i < space; i += 1) {
                    indent += ' ';
                }

// If the space parameter is a string, it will be used as the indent string.

            } else if (typeof space === 'string') {
                indent = space;
            }

// If there is a replacer, it must be a function or an array.
// Otherwise, throw an error.

            rep = replacer;
            if (replacer && typeof replacer !== 'function' &&
                    (typeof replacer !== 'object' ||
                     typeof replacer.length !== 'number')) {
                throw new Error('JSON.stringify');
            }

// Make a fake root object containing our value under the key of ''.
// Return the result of stringifying the value.

            return str('', {'': value});
        };
    }


// If the JSON object does not yet have a parse method, give it one.

    if (typeof JSON.parse !== 'function') {
        JSON.parse = function (text, reviver) {

// The parse method takes a text and an optional reviver function, and returns
// a JavaScript value if the text is a valid JSON text.

            var j;

            function walk(holder, key) {

// The walk method is used to recursively walk the resulting structure so
// that modifications can be made.

                var k, v, value = holder[key];
                if (value && typeof value === 'object') {
                    for (k in value) {
                        if (Object.hasOwnProperty.call(value, k)) {
                            v = walk(value, k);
                            if (v !== undefined) {
                                value[k] = v;
                            } else {
                                delete value[k];
                            }
                        }
                    }
                }
                return reviver.call(holder, key, value);
            }


// Parsing happens in four stages. In the first stage, we replace certain
// Unicode characters with escape sequences. JavaScript handles many characters
// incorrectly, either silently deleting them, or treating them as line endings.

            text = String(text);
            cx.lastIndex = 0;
            if (cx.test(text)) {
                text = text.replace(cx, function (a) {
                    return '\\u' +
                        ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
                });
            }

// In the second stage, we run the text against regular expressions that look
// for non-JSON patterns. We are especially concerned with '()' and 'new'
// because they can cause invocation, and '=' because it can cause mutation.
// But just to be safe, we want to reject all unexpected forms.

// We split the second stage into 4 regexp operations in order to work around
// crippling inefficiencies in IE's and Safari's regexp engines. First we
// replace the JSON backslash pairs with '@' (a non-JSON character). Second, we
// replace all simple value tokens with ']' characters. Third, we delete all
// open brackets that follow a colon or comma or that begin the text. Finally,
// we look to see that the remaining characters are only whitespace or ']' or
// ',' or ':' or '{' or '}'. If that is so, then the text is safe for eval.

            if (/^[\],:{}\s]*$/.
test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@').
replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').
replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {

// In the third stage we use the eval function to compile the text into a
// JavaScript structure. The '{' operator is subject to a syntactic ambiguity
// in JavaScript: it can begin a block or an object literal. We wrap the text
// in parens to eliminate the ambiguity.

                j = eval('(' + text + ')');

// In the optional fourth stage, we recursively walk the new structure, passing
// each name/value pair to a reviver function for possible transformation.

                return typeof reviver === 'function' ?
                    walk({'': j}, '') : j;
            }

// If the text is not JSON parseable, then a SyntaxError is thrown.

            throw new SyntaxError('JSON.parse');
        };
    }
}());
;
$.extend( $, {
    arrayIndexOf: function( arr, obj ){
        for(var i=0; i<arr.length; i++){
            if(arr[i]==obj){
                return i;
            }
        }
        return -1;
    },
    log: function(mes) {
        // $( '#debug' ).append( '<div>' + mes + '</div>' );
        // console && console.log( mes );
    },
    evstop: function( ev ) {
        if ( ev ) {
            ev.stopPropagation && ev.stopPropagation(); /* pop up                  */
            ev.preventDefault && ev.preventDefault();   /* other event on this DOM */
        }
    },
    simpText: function( str ) {
        return $.trim( str ).replace( / +/g, ' ' );
    },
    unparam: function( s ) {
        var o = {};
        var args = s.split( "&" );
        $.each( args, function( i, e ){
            if ( e != "" ) {
                var kv = e.split( "=" );
                var k = decodeURIComponent( kv[ 0 ] );
                var v = decodeURIComponent( kv[ 1 ] );
                o[ k ] = v;
            }
        } );

        return o;
    },
    unescape: function( html ) {
        var htmlNode = document.createElement('div');
        htmlNode.innerHTML = html;
        if (htmlNode.innerText) {
            return htmlNode.innerText; // IE
        }
        return htmlNode.textContent; // FF
    }
} );


$.extend( $.fn, {
    to_json: function() {
        // TODO filter non-comment node
        var rst = [];
        $( this ).each( function( i, v ) {
            var e = $( this );
            var j, text;
            if ( this.nodeType == 3 ) {
                text = e.simpText();
                if ( text != '' ) {
                    rst.push( { text: this.nodeValue } );
                }
            }
            else {

                j = { node: { tag: this.tagName, id:e.id(), 'class': e.attr( 'class' ) } };

                switch ( j.node.tag ) {
                case 'IMG' :
                    j.node.src = e.attr( 'src' );
                    break;
                case 'A' :
                    j.node.href = e.attr( 'href' );
                    break;
                }
                var children = e.contents();
                if ( children.length > 0 ) {
                    j.children = children.to_json();
                }
                rst.push( j );
            }
        } );

        return rst;
    },
    offset_tl: function(){
        var tl = $(this).offset();
        return { t:tl.top, l:tl.left };
    },
    _outerSize: function( funcname, withMargin ){
        withMargin = withMargin == undefined ? true : withMargin;
        var s = 0;
        this.each( function( i, v ){
            s += $(v)[ funcname ]( withMargin );
        } );
        return s;
    },
    h: function( withMargin ) {
        return this._outerSize( 'outerHeight', withMargin );
    },
    w: function( withMargin ) {
        return this._outerSize( 'outerWidth', withMargin );
    },
    size_wh: function( withMargin ) {
        return { w: this.w( withMargin ), h: this.h( withMargin ) };
    },
    id: function() {
        return this.attr( 'id' );
    },
    simpText: function() {
        return $.simpText( this.text() );
    },
    simpVal: function() {
        return $.simpText( this.val() );
    },
    btn_opt: function (  ) {
        var e = $( this );
        var opt = {
            icons : {},
            text : e.attr( "_text" ) != "no"
        };

        e.attr( "_icon" ) && ( opt.icons.primary = "ui-icon-" + e.attr( "_icon" ) );
        e.attr( "_icon2" ) && ( opt.icons.secondary = "ui-icon-" + e.attr( "_icon2" ) );

        return opt;
    },
    jsonRequest: function( succ ){

        $( this ).each( function(){

            var form = $( this );

            $.ajax( {
                url : form.attr( 'action' ),
                type : form.attr( 'method' ),
                data : form.serialize(),
                dataType : 'json',
                success : succ
            } );

        } );
    }
} );

$.extend( $.fn, {
    tl: $.fn.offset_tl,
    p: $.fn.parents
} );
;
// Works with jquery-1.4.4, jquery-ui-1.8.9

$.vweb = {
    conf: {
        loginPage: "http://" + window.location.host,
        appLink: 'http://t.cn/a0yUgu',  // vweb
        appLinkDev: 'http://t.cn/aOXV5H',  // 2.vweb
        maxChar: 110
    },
    account: undefined,
    ui : {
        t : {
            update : {},
            my : {
                friend : {},
                globalsearch : {}
            },
            paging : {},
            list : {}
        }
    },
    backend : {}
};

$.extend( $.vweb, {
    // TODO request not through t_cmd should also be handled like this
    tweets: function( data ) {
        function _stdAvatar( e ) {
            if ( e.profile_image_url ) {
                e.avatar_50 = e.profile_image_url;
                e.avatar_30 = e.profile_image_url.replace( /\/50\//, '/30/' );
            }
        }
        var self = {
            _d : data,
            get: function () { return this._d; },
            splitRetweet: function () {
                var d = [];
                $.each( this._d, function( i, v ){
                    d.push( v );
                    if ( v.retweeted_status ) {
                        v.status = 'retweeter';
                        v.retweeted_status.status = "retweet";
                        d.push( v.retweeted_status );
                    }
                } );
                this._d = d;
                return this;
            },
            exclude: function (ids) {
                this._d = ids.length == 0
                    ? this._d
                    : $.grep( this._d, function( v, i ) {
                        return $.arrayIndexOf( ids, v.id + "" ) < 0
                            && ( !v.retweeted_status || $.arrayIndexOf( ids, v.retweeted_status.id + "" ) < 0  );
                    } );
                return this;
            },
            stdAvatar: function ( userkey ) {
                $.each( this._d, function( i, v ) {
                    $.each( [ 'user', 'sender', 'recipient' ], function( ii, k ){
                        v[ k ] && _stdAvatar( v[ k ] );
                    } );
                } );
                return this;
            },
            htmlLinks: function () {
                $.each( this._d, function( i, v ) {
                    v.html = v.text.replace(
                        /(http:\/\/(?:sinaurl|t)\.cn\/[a-zA-Z0-9_]+)/g,
                        "<a target='_blank' class='raw' href='$1'>$1</a>" )
                    .replace(
                        /@([_a-zA-Z0-9\u4e00-\u9fa5\-]+)/g,
                        "<a class='at' screen_name='$1' href=''>@$1</a>" )
                    .replace(
                        /#([^#]+)#/g,
                        "<a class='topic' href=''>@$1</a>" )
                    ;
                } );
                return this;
            },
            defaultUser: function ( which ) {
                $.each( this._d, function( i, v ) {
                    v[ which ] && !v.user && ( v.user = v[ which ] );
                } );
                return this;
            },
            setMe: function( id ){
                $.each( this._d, function( i, v ){
                    v.user.isme = ( id == v.user.id );
                } );
                return this;
            },
            historyText: function () {
                $.each( this._d, function( i, v ){
                    v.text_forhis = v.text.replace( /([\u0100-\uffff])/g, '\u00ff$1' )
                    .substr( 0, 10 ).replace( /\u00ff/g, '' );

                    if ( v.text_forhis != v.text ) {
                        v.text_forhis +=  '...';
                    }
                } );
                return this;
            },
            historyTime: function () {
                var now = new Date();
                $.each( this._d, function( i, v ){
                    var d = new Date( v.created_at );
                    var dt = d.getYear() + "年" + d.getMonth() + "月" + d.getDate();
                    dt = ( dt == now.getYear() + "年" + now.getMonth() + "月" + now.getDate() ) ?  "今天" : dt;
                    v.time_forhis = dt + " " + d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds();
                } );
                return this;
            }
        };
        return self;
    },
    handle_json: function( handlers, json, st, xhr ) {
        if ( handlers[ json.rst ] ) {
            return handlers[ json.rst ]( json, st, xhr );
        }
        else if ( json.rst != "ok" && handlers[ "any" ] ) {
            return handlers.any( json, st, xhr );
        }
    },
    create_handler: function( handlers ) {
        return function ( json, st, xhr ) {
            return $.vweb.handle_json( handlers, json, st, xhr );
        };
    },
    init_sub: function( self ) {
        $.each( self, function( k, v ){
            var u = self[ k ];
            if ( u.init ) {
                u._elt = $( "#" + k );
                // $.log( 'to  init_sub: ' + k );
                u.init( u, u._elt );
                // $.log( 'end init_sub: ' + k );
            }
        } );
    },

    setup_img_switch : function ( container ) {
        var swi = { thumb: "midpic", midpic: "thumb" };

        $( container ).delegate( ".t_msg .imgwrap", "click", function(){
            var e = $( this ).p( ".t_msg" );
            $.log( e + 'clicked' );
            e.toggleClass( 'thumb' );
            e.toggleClass( 'midpic' );
        } );
    }

} );

;

$.extend( $.vweb.backend, { weibo: {
    // TODO request not through t_cmd should also be handled like this
    t_cmd: function( verb, cmd, args, data, cbs ) {

        args = $.isPlainObject( args ) ? $.param( args ) : args;
        cbs = cbs || {};

        $.log( 'cmd:' );
        $.log( cmd );
        $.log( 'args:' );
        $.log( args );

        $.ajax( {
            type : verb,
            url : "t.php?act=" + cmd + "&resptype=json&" + args,
            data: data,
            dataType : "json",
            success : function( json, st, xhr ) {

                if ( json.rst == 'weiboerror' ) {

                    $.log( 'weiboerror error' );

                    var msg = json.msg || '0:';
                    var cm = msg.split( ':' );
                    var msgCode = cm[ 0 ];
                    msg = cm[ 1 ];

                    if ( msgCode == '40028' ) {
                        // too many repeated update
                        // content length error
                        // TODO do not use ui.appmsg
                        // $.vweb.ui.appmsg.err( msg );
                    }
                    else {
                        // TODO do not use ui.appmsg
                        // $.vweb.ui.appmsg.err( msg );
                    }

                    return;
                }
                else if ( json.rst == 'auth' ) {
                    // no Oauth key
                    window.location.href = $.vweb.conf.loginPage;
                }
                else{
                }

                $.log( 'it is ok' );
                $.log( json );

                // TODO do not use ui.appmsg
                // $.vweb.ui.appmsg.msg( json.rst + " " + json.msg );
                cbs.success && cbs.success( json );

            },
            error : function( jqxhr, errstr, exception ) {
                // TODO do not use ui.appmsg
                // $.vweb.ui.appmsg.msg( errstr );
                cbs.error && cbs.error( jqxhr, errstr, exception );
            }
        } );

    },

    cmd_serialize: function ( cmdname, opt, idfirst ) {
        var args = [];
        var o = {};

        $.isPlainObject( opt ) && ( $.extend( o, opt ) ) || ( o = $.unparam( opt ) );
        o.max_id || ( o.max_id = idfirst );
        $.each( o, function( k, v ){ args.push( k + '__' + v ); } );
        args.sort();

        var s = cmdname.replace( /\//g, '__' ) + '____' + args.join( '____' ) ;
        $.log( 'cmd str=' + s );
        return s;
    },

    cmd_unserialize: function ( s ) {
        var args = s.split( /____/ );
        var cmdname = args.shift().replace( /__/g, '/' );
        var opt = {};

        $.each( args, function( i, v ){
            var q = v.split( '__' );
            opt[ q[ 0 ] ] = q[ 1 ];
        } );

        return [ cmdname, opt ];
    },

    create_loader : function ( cmdname, opt ) {
        var realload = this.load;
        return function ( ev ) {
            $.evstop( ev );
            realload.apply( $(this), [ cmdname, opt ] );
        }
    },

    load : function( cmdname, opt ) {
        // 'this' is set by create_loader and which is the DOM fired the event

        $.log( opt );
        var trigger = this;
        var args = {};
        if ( opt.args ) {
            args = opt.args.apply ? opt.args.apply( this, [] ) : opt.args;
            args = $.isPlainObject( args ) ? args : $.unparam( args );
        }

        $.log( "args:" );
        $.log( args );

        $.vweb.backend.weibo.t_cmd( 'GET', cmdname, args, undefined, {
            success: function( json ) {
                if ( json.rst == 'ok' ) {
                    var t = trigger;
                    var cmd = { name: cmdname, args: args, cb:opt.cb };

                    // $.vweb.ui.appmsg.msg( "载入成功" );

                    // TODO do not addhis after paging down/up
                    $.vweb.backend.weibo.addhis( json, cmd );

                    opt.cb && $.each( opt.cb, function( i, v ){
                        eval( v + "(json.data,t,cmd)" );
                    } );
                }
                else { /* need something to be done? */  }
            }
        }
        );
    },

    addhis: function ( json, cmd ) {
        if ( json.rst != 'ok' || ! json.data[ 0 ] ) {
            return;
        }

        $.vweb.ui.t.paging.addhis( json, cmd );
        $.vweb.ui.main.edit.addhis( json, cmd );
    },

    gen_cmd_hisrecord: function( json, cmd ){
        var d = json.data[ 0 ];

        d.hisid = $.vweb.backend.weibo.cmd_serialize( cmd.name, cmd.args, d.id );
        $.log( d );

        var hisdata = $.vweb.tweets( [ d ] ).stdAvatar().defaultUser('sender')
        .historyText().historyTime().get()[ 0 ];

        $.log( hisdata );

        return hisdata;
    }

} } );
;
$.extend( $.vweb.ui, {

    init : function ( self, e ) {

        $.log( 'ui.init start' );

        self.relayout();

        $( ".t_opt" ).each( function() {
            var container = $( this );
            var tp = container.attr( "_type" );
            var id = container.attr( "id" );
            var name = container.attr( "name" );

            var data = [];
            container.children( "a" ).each( function( i, e ){
                e = $( e );

                data.push( {
                    type : tp,
                    id : id + i,
                    name : name,
                    value : e.attr( "href" ),
                    label : e.text() } );

                e.remove();
            } );
            $( "#tmpl_opts" ).tmpl( data ).appendTo( container );

        } );

        $( ".t_btn" ).each( function() {
            $( this ).button( $( this ).btn_opt() );
        } );

        $( ".t_btn_set" ).buttonset();

        $( ".t-dialog" ).addClass( "ui-dialog ui-widget ui-widget-content ui-corner-all" );
        $( ".t-panel" ).addClass( "ui-widget ui-corner-all" );
        $( ".t_ctrl" ).addClass( "ui-widget ui-corner-all" );
        $( ".t-group" ).addClass( "ui-widget ui-corner-all" );
        $( "#func" ).addClass( "cont_dark2 cont_dark_shad2" );
        $( "#paging" ).addClass( "cont_dark0 cont_dark_shad0" );


        $.vweb.init_sub( this );

        $( window ).resize( function() { self.relayout(); } );

        $( "body" ).click( function( ev ) {
            var tagname = ev.target.tagName;
            if ( tagname != 'INPUT' && tagname != 'BUTTON' ) {
                $( ".t-autoclose" ).hide();
            }
        } )
        .droppable( {
            drop: function( ev, theui ) {
                $.log( ev );
                $.log( theui );
                var msg = theui.draggable;
                if ( $( msg ).parent( '#page' ).length ) {
                    msg.remove();
                    $.vweb.ui.t.list.msg_visible( msg.id(), true );
                }
            }
        } )
        ;

    },

    relayout : function () {
        var bodyHeight = $( "body" ).height();
        var footerHeight = $( "#footer" ).h();
        var edit = $( "#edit" );
        var subtabHeight = bodyHeight - footerHeight;

        edit.height( bodyHeight - footerHeight - $( "#maintool" ).h() );
        $( '#appmsg' ).css( { top: $( '#maintool' ).h() } );

        $( "#t>#list" ).height( subtabHeight
            - $( "#t>#update,#t>#func,#t>#paging" ).filter( ':visible' ).h() );

        $( "#tree" ).height( subtabHeight - $( "#vdaccpane" ).h() );

        // // NOTE temporarily disabled
        // $( "#edit>#cont" )
        // .width( edit.width() - 30 )
        // .height( edit.height() - 30 );

    }

} );
;
$.extend( $.vweb.ui, { appmsg: {
    init: function( self, e ) {
        e.bind( 'ajaxSend', function(){
            self.msg( '载入中..' );
        } )
        .bind( 'ajaxSuccess', function( ev, xhr, opts ){
            // ev is and event object
        } )
        .bind( 'ajaxError', function( ev, jqxhr, ajaxsetting, thrownErr ){
            // self.err( ev );
        } );
    },
    msg : function ( text, level ) {
        if ( text ) {
            var e = this._elt;
            var data = [{text:text, level:level || 'info'}];

            $( "#tmpl_appmsg" ).tmpl( data ).appendTo( e.empty() );

            this.lastid && window.clearTimeout( this.lastid );
            this.lastid = window.setTimeout( function(){ e.empty(); }, 5000 );
        }
    },
    alert: function( text ) {
        return this.msg( text, 'alert' );
    },
    err: function ( text ) {
        return this.msg( text, 'error' );
    }
} } );
;
$.extend( $.vweb.ui, { main: {
    init : function () {
        $.vweb.init_sub( this );
    }
} } );
;
$.extend( $.vweb.ui.main, { maintool: {
    init : function ( self, e ) {

        var fn = this.fn = $( "#fn", e );
        var pubmsg = this.pubmsg = $( "#pubmsg", e );
        var charleft = this.charleft = $( "#charleft" ).hide();
        var pub = this.pub = $( "#pub", e );
        this._defaultAlbumName = '未命名相册';


        fn.DefaultValue( this._defaultAlbumName );

        function pubmsg_open () {
            pubmsg.addClass( 'focused' );
            charleft.show();
            self.update_charleft();
            $.vweb.ui.relayout();
        }

        function pubmsg_close () {
            pubmsg.removeClass( 'focused' );
            charleft.hide();
            $.vweb.ui.relayout();
        }

        pubmsg.focus( function( ev ){

            if ( $( this ).simpVal() == '' ) {
                return;
            }

            self.focusTWID && window.clearTimeout( self.focusTWID );
            self.focusTWID = window.setTimeout( pubmsg_open, 50 );

        } )
        .keydown( function( ev ){

            if ( ev.keyCode == 13 && ( ev.metaKey || ev.ctrlKey ) ) {
                // ctrl-cr. command-cr on Mac.

                pub.click();
            }
            else if ( ev.keyCode == 27 ) {
                // esc

                pubmsg.blur();
                pubmsg_close();
            }
            else {

                // NOTE: val() retreives text without the key just pressed.
                self.keydownTW && window.clearTimeout( self.keydownTW );
                self.keydownTW = window.setTimeout(function() {
                    var txt = pubmsg.simpVal();
                    if ( txt.length > 20 || pubmsg.val().match( /\n/ ) ) {
                        pubmsg.hasClass( 'focused' ) || pubmsg_open();
                    }
                    self.update_charleft();
                 }, 10);
                return;
            }

            $.evstop( ev );
        } )
        ;

        $( document ).click( function( ev ){
            var tagname = ev.target.tagName;
            if ( tagname && tagname.match( /INPUT|BUTTON|TEXTAREA/ ) ) {
                return;
            }
            pubmsg_close();
        } )

        $( document ).keydown( function( ev ){

            if ( ev.keyCode == 13 && ( ev.metaKey || ev.ctrlKey ) ) {
                // ctrl-cr. command-cr on Mac.

                pubmsg.focus();
            }
            else {
                return;
            }

            $.evstop( ev );
        } );

        pub.click( function( ev ){
            $.evstop( ev );

            var msg = pubmsg.simpVal();

            if ( msg.length == 0 ) {
                $.vweb.ui.appmsg.alert( "还没有填写内容" );
                pubmsg.focus();
            }
            else if ( msg.length > $.vweb.conf.maxChar ) {
                $.vweb.ui.appmsg.alert( "字数太多" );
                pubmsg.focus();
            }
            else {
                var data = {
                    page: $.vweb.ui.main.edit.pagedata(),
                    layout: $.vweb.ui.main.edit.layoutdata() };

                $.vweb.backend.weibo.t_cmd( 'POST', 'pub', { albumname: fn.val(), msg: msg },
                    JSON.stringify( data ), {
                        success: function( json ) {
                            if ( json.rst == 'ok' ) {
                                $.vweb.ui.appmsg.msg( "发布成功" );
                                pubmsg.val( '' ).blur();
                            }
                            else {
                                $.vweb.ui.appmsg.alert( json.msg );
                            }
                        }
                    } );
            }

        } );
    },

    update_charleft: function(){
        this.charleft.text( "剩余 " + (
            $.vweb.conf.maxChar - this.pubmsg.val().length ) + " 字" );
    }

} } );
;
$.extend( $.vweb.ui.main, { edit: {
    init : function ( self, e ) {
        this.cont = e.children( "#cont" );
        this.page = this.cont.children( "#page" );

        this.setup_func();


        e.find( "#edit_mode input" ).button().click( function() {
            $( this ).parent().find( "input" ).each( function() {
                self.cont.removeClass( $( this ).val() );
            } );
            self.cont.addClass( $( this ).val() );
        } );

        e.find( "#screen_mode input" ).button();
    },
    setup_func : function () {
        $.vweb.setup_img_switch( this.page );
        var e = $( '<div class="t_msg"></div>' ).hide();

        // NOTE: jquery ui bug: if there is no item matches "items" option, no
        // sortable function would be set up. Thus first msg would be lost
        this.page.append( e );
        this.page.sortable({
            items:".t_msg",
            tolerance:'pointer',
            appendTo:"body",
            zIndex:2000,
            receive : function ( ev, theui ) {
                $.evstop( ev );
                $.log( ev );
                $( '#pagehint' ).remove();

                var msg = theui.item;
                $.vweb.ui.t.list.msg_visible( msg.id(), false );
            },
            // NOTE: helper setting to "clone" prevents click event to trigger
            helper : "clone"
        })
        .droppable( {
            // doing nothing but prevent 'body' from receiving 'drop' event
            drop: function( ev, theui ) {
                $.evstop( ev );
            }
        } );
        ;

        // e.remove();

    },
    ids : function () {
        return $.map( $( '.t_msg', this.page ), function( v, i ){
            return $( v ).id();
        } );
    },
    pagedata: function(){
        return this.page.children( ".t_msg:not(.t_his)" ).to_json();
    },
    layoutdata : function () {
        var rst = [];
        var root = this.page.offset();
        root.top -= this.page.scrollTop();
        root.left -= this.page.scrollLeft();

        function lo( elt, attrs ) {
            return $.extend( attrs || {}, elt.offset_tl(), elt.size_wh( false ) );
        }

        this.cont.find( ".t_msg" ).each( function() {
            var e = $( this );
            var thumb = $( "img.thumb:visible", e );
            var midpic = $( "img.midpic:visible", e);

            if ( thumb.length > 0 ) {
                rst.push( lo( thumb.p( '.imgwrap' ), { bgcolor:'#000' } ) );
                rst.push( lo( thumb, { img : thumb.attr( "src" ) } ) );
            }

            if ( e.find( ".cont .msg:visible" ).length > 0 ) {
                rst.push( lo( e, { color : "#000", text : e.simpText() } ) );
                rst[ rst.length-1 ].w -= thumb ? lo( thumb ).w + 4 : 0;
            }

            midpic.length > 0 && rst.push( lo( midpic, { img : midpic.attr( "src" ) } ) );

        } );

        var actualsize = { w:0, h:0 };

        $.each( rst, function( i, v ){
            v.t -= root.top;
            v.l -= root.left;

            actualsize.w = Math.max( v.l+v.w, actualsize.w );
            actualsize.h = Math.max( v.t+v.h, actualsize.h );
        } );

        return $.extend( { bgcolor : "#fff", d : rst }, actualsize );
    },
    addhis: function ( json, cmd ) {

        var rec = $.vweb.backend.weibo.gen_cmd_hisrecord( json, cmd );

        this.page.find( "#" + rec.hisid ).remove();
        $( "#tmpl_hisrec" ).tmpl( [ rec ] ).prependTo( this.page );
    },
    removehis: function ( id ) {
        this.page.find( ".t_his#" + id ).remove();
    },
    html : function( h ) {
        return this.page.html( h );
    }
} } );
;
$.extend( $.vweb.ui, { t: {
    init: function (){
        $.vweb.init_sub( this );
    }
} } );
;
$.extend( $.vweb.ui.t, { list: {
    init : function ( self, e ) {
        this.last = {};
        $.vweb.setup_img_switch( this._elt.empty() );
        this.setup_func( self, e );
    },

    repost_cb: function ( rst ) {
        $.vweb.handle_json( { 'ok': function(){
            $( "#" + rst.info.id, e ).removeClass( 'in_repost' );
            $( "#" + rst.info.id + " .g_repost", e ).remove();
        } }, rst );
    },

    comment_cb: function ( rst ) {
        $.vweb.handle_json( { 'ok': function(){
            $( "#" + rst.info.id + " .g_comment", e ).remove();
        } }, rst );
    },

    setup_func : function ( self, e ) {

        var uldr = $.vweb.backend.weibo.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { user_id: this.id() }; },
                cb: [ '$.vweb.ui.t.list.show' ]
            } );

        var atldr = $.vweb.backend.weibo.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { screen_name: this.attr( 'screen_name' ) }; },
                cb: [ '$.vweb.ui.t.list.show' ]
            } );

        var atldr = $.vweb.backend.weibo.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { screen_name: this.attr( 'screen_name' ) }; },
                cb: [ '$.vweb.ui.t.list.show' ]
            } );



        this._elt
        .delegate( ".t_msg .avatar a.user, .t_msg .username a.user", "click", uldr )
        .delegate( ".t_msg .cont.msg a.at", "click", atldr )
        .delegate( ".t_msg .f_destroy", "click", function( ev ){
            $.evstop( ev );
            var msg = $( this ).p( ".t_msg" );
            $.vweb.backend.weibo.t_cmd( 'POST', "destroy", '',
                { id: msg.id() },
                { 'success':function( json ){
                    $.vweb.ui.appmsg.msg( '已删除' );
                    msg.hide( 200, function(){
                        msg.remove();
                    } );
                } } );
        } )
        .delegate( ".t_msg .f_retweet", "click", function( ev ){
            $.evstop( ev );
            var e = $( this ).p( ".t_msg" );
            e.addClass( 'in_repost' );
            $( ".g_repost", e ).remove();
            var rp = $( "#tmpl_repost" ).tmpl( [ {
                id: e.id(),
                text: e.hasClass( "retweeter" )
                    ? '//@' + $( ".username .user", e ).simpText() + ':' + $( ".cont.msg .msg", e ).simpText()
                    : ''
            } ] ).prependTo( e );
            $( '.f_text', rp ).focus();
        } )
        .delegate( ".t_msg .g_repost .f_cancel", "click", function( ev ){
            $.evstop( ev );
            $( this ).p( ".t_msg" ).removeClass( 'in_repost' );
            $( this ).p( ".g_repost" ).remove();
        } )
        .delegate( ".t_msg .f_comment", "click", function ( ev ) {
            $.evstop( ev );
            var e = $( this ).p( ".t_msg" );
            $( ".g_comment", e ).remove();
            $( "#tmpl_comment" ).tmpl( [ {
                id: e.id(),
                text: ''
            } ] ).appendTo( e );
        } )
        .delegate( ".t_msg .g_comment .f_cancel", "click", function( ev ){
            $.evstop( ev );
            $( this ).p( ".g_comment" ).remove();
        } )
        .delegate( ".t_msg .f_fav", "click", function( ev ){
            $.evstop( ev );
            $.log( this );
            $.vweb.backend.weibo.t_cmd( 'POST', "fav", '',
                { id: $( this ).p( ".t_msg" ).id() }, { } );
        } )
        ;
    },

    filter_existed : function ( data ) {
        return data;
    },

    msg_visible: function( id, visible ) {
        var e = $( '#' + id, this._elt );

        if ( e.length ) {
            if ( visible ) {
                e.show( 200 ).prev( '.retweeter' ).show( 200 );
                // this._elt.scrollTo( e, { duration:0 } );
            }
            else {
                e.hide( 200 ).prev( '.retweeter' ).hide( 200 );
            }
        }
    },

    show : function ( data ) {

        data = $.vweb.tweets( data ).splitRetweet().exclude( $.vweb.ui.main.edit.ids() )
        .stdAvatar().defaultUser( 'sender' ).setMe( $.vweb.account.id )
        .htmlLinks().get();

        this._elt.empty();
        try {
            // var t = $( "#tmpl_msg." + MODE );
            var t = $( "#tmpl_msg_album" );
            $.log( "MODE=" + MODE );
            $.log( t.length );
            t = t.tmpl( data );
            $.log( t );
            t.appendTo( this._elt );
        }
        catch (err) {
            $.log( err );
        }

        this.setup_draggable();
    },
    setup_draggable : function () {

        this._elt.children().has( '.imgwrap' ).draggable( {
            connectToSortable: "#page",
            handle : ".imgwrap",
            helper : "clone",
            revert : "invalid",
            zIndex : 2000,
            cursorAt:{ left:50, top:50 },
            stop : function ( ev, ui ) {
            }
        } );


    }
} } );
;
$.extend( $.vweb.ui.t, { my: {
    init : function () {
        var self = this;
        $( "#expand.t_btn" ).click( function (ev){
            $.evstop( ev );
            self._elt.removeClass( 'hideall' );
        } );

        var statLoader = $.vweb.backend.weibo.create_loader( 'statuses/unread', {
            args: function () {
                return {
                    'since_id': $.vweb.ui.t.my.friend.since_id,
                    'with_new_status': 1
                }
            },
            cb: [ '$.vweb.ui.t.my.setStat' ] } );

        statLoader();
        self.whatID = window.setInterval( statLoader, 60 * 1000 );


        $( "body" ).click( function( ev ){
            var tagname = ev.target.tagName;
            if ( tagname != 'INPUT' && tagname != 'BUTTON' ) {
                self._elt.addClass( 'hideall' );
            }
        } );

        $.vweb.init_sub( this );
    },

    setStat: function( d, tgr ){
        var e = this._elt
        d.new_status && $( '#friend .f_idx .stat', e ).text( "(新)" );
        d.comments && $( '#friend .f_comment .stat', e ).text( "(" + d.comments + ")" );
        d.mentions && $( '#friend .f_at .stat', e ).text( "(" + d.mentions + ")" );
        d.dm && $( '#friend .f_message .stat', e ).text( "(" + d.dm + ")" );
    }

} } );
;
$.extend( $.vweb.ui.t.my, { friend : {
    init : function( self, e ){
        self.formSimp = e.find( "form.g_simp" );
        self.formSearch = e.find( "form.g_search" );

        var simpLoader = $.vweb.backend.weibo.create_loader( 'statuses/friends_timeline', {
            args: function() { return self.formSimp.serialize(); },
            cb: [ '$.vweb.ui.t.list.show', '$.vweb.ui.t.my.friend.addLast', '$.vweb.ui.t.my.friend.clearStat']
        } );

        // option arg of all these 3 loader: since_id, max_id, count, page
        var mineLoader = $.vweb.backend.weibo.create_loader( 'statuses/user_timeline', {
            args: function(){ return { user_id: $.vweb.account.id }; },
            cb: [ '$.vweb.ui.t.list.show' ]
        } );
        var atLoader = $.vweb.backend.weibo.create_loader( 'statuses/mentions',
            { cb: [ '$.vweb.ui.t.list.show', '$.vweb.ui.t.my.friend.clearStat' ] } );

        var cmtLoader = $.vweb.backend.weibo.create_loader( 'statuses/comments_to_me',
            { cb: [ '$.vweb.ui.t.list.show', '$.vweb.ui.t.my.friend.clearStat' ] });

        var msgLoader = $.vweb.backend.weibo.create_loader( 'direct_messages',
            { cb: [ '$.vweb.ui.t.my.friend.clearStat' ] });


        $( '.f_mine', e ).click( mineLoader );

        $( ".f_idx", e ).click( simpLoader );
        self.formSimp.find( "input" ).button().click( simpLoader );


        $( ".f_at", e ).click( atLoader );
        $( ".f_comment", e ).click( cmtLoader );

        // don't stop event propagation. it links to another page.
        $( ".f_message", e ).click(
            function( ev ){ $.vweb.ui.t.my.friend.clearStat( null, $( this ) ); }
        );

        $.vweb.init_sub( self );

        // TODO default action after page loaded
        window.setTimeout(function() { self._elt.find( ".f_idx" ).trigger( 'click' ); }, 0);
    },

    // used only for record last id
    addLast: function ( d ) {
        d = d[ 0 ];
        if ( !this.since_id || d && (d.id+0) > (this.since_id+0) ) {
            this.since_id = d.id;
        }
    },

    clearStat: function ( data, triggerElt ) {
        // triggerElt may be not a valid DOM if "load" is called directly
        if ( ! triggerElt.attr ) { return; }

        $( '.stat', triggerElt ).empty();
        if ( triggerElt.attr( "_reset_type" ) ) {
            $.vweb.backend.weibo.create_loader(
                'statuses/reset_count',
                { args: function() { return { type: triggerElt.attr( "_reset_type" ) }; } }
                )();
        }
    }

} } );
;
$.extend( $.vweb.ui.t, { paging: {
    init: function() {
        var self = this;

        this._elt.find( "#btnhis" ).click( function(){
            $( "#history" ).toggle();
        } );

        $( '.f_prev', this._elt ).click( function(  ){
            if ( ! self.last ) { return; }
            var l = self.last;
            // var since_id = l.json.data[ 0 ].id;
            // var args = $.extend( {}, l.cmd.args, { since_id: since_id } );
            // delete args.max_id;

            var args = $.extend( {}, l.cmd.args );
            args.page = args.page ? args.page - 1 : 1;
            args.page = args.page <= 0 ? 1 : args.page;

            $.vweb.backend.weibo.load( l.cmd.name, { args: args, cb: l.cmd.cb } );

        } );
        $( '.f_next', this._elt ).click( function(  ){
            if ( ! self.last ) { return; }
            var l = self.last;
            // var max_id = l.json.data[ l.json.data.length - 1 ].id;
            // var args = $.extend( {}, l.cmd.args, { max_id: max_id } );
            // delete args.since_id;

            var args = $.extend( {}, l.cmd.args );
            args.page = args.page ? args.page + 1 : 2;

            $.vweb.backend.weibo.load( l.cmd.name, { args: args, cb: l.cmd.cb } );

        } );

        $( "#history" ).delegate( ".t_msg .f_del", "click", function( ev ){
            var e = $( this ).parent();
            $.vweb.ui.main.edit.removehis( e.attr( "id" ) );
            e.remove();
        } );

    },
    loadhis: function () {
        $.vweb.ui.main.edit.page.find( ".t_his" ).clone().appendTo(
            $( "#history" ).empty() );
    },
    addhis: function ( json, cmd ) {
        var rec = $.vweb.backend.weibo.gen_cmd_hisrecord( json, cmd );

        this.last = { cmd:cmd, json:json };

        $.log( 'paging.addhis:' );
        $.log( rec );

        $( this._elt ).find( ".t_his#" + rec.hisid ).remove();
        $( "#tmpl_hisrec" ).tmpl( [ rec ] ).prependTo( $( "#history" ) );
    }
} } );
;
$(document).ready( function() {
    if ( MODE == 'album' ) {
        $( '.nomode_album' ).remove();
    }

    if ( $.browser.msie ){
        $( "#list" ).css( { 'position' : 'relative' } );
        // $('<link rel="stylesheet" type="text/css" href="css/msie.css" />').appendTo("head");
    }

    $.vweb.backend.weibo.t_cmd( 'GET', 'account/verify_credentials', {}, undefined, {
        success:function( json ) {
            $.vweb.account = json.data;
            $.vweb.ui.init( $.vweb.ui );
        },
        error: function( jqxhr, errstr, exception ) {

        }
    } );

} );
;
