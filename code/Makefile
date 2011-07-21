VL_JS = js/vlbum_c.js
VL_CSS = css/vlbum_c.css

jsfiles := $(shell cat vlbum.html | grep "jscompact" | grep -o "src=[^>]*" | tr -d '"' | cut -c 5- )
cssfiles := $(shell cat vlbum.html | grep "csscompact" | grep -o "href=[^ ]*" | tr -d '"' | cut -c 6- )


vlbum: $(VL_JS) $(VL_CSS)
	cat vlbum.html \
	    | grep -v "csscompact\|jscompact" \
	    | grep -v "^ *$$" \
	    | grep -v "^ *<!--.*-->$$" \
	    | awk '/^ *<!--/{ cmtStart = 1; } { if(!cmtStart) {print $$0} } /-->$$/{cmtStart=0}' \
	    > _tmp
	mv _tmp vlbum.html

js: $(VL_JS)

css: $(VL_CSS)

$(VL_JS): $(jsfiles)
	>$(VL_JS)
	for fn in $(jsfiles); do \
	    cat $$fn >> $(VL_JS); \
	    echo ";" >> $(VL_JS); \
	done

$(VL_CSS): $(cssfiles)
	>_tmp
	for fn in $(cssfiles); do \
	    cat $$fn >> _tmp; \
	    echo "\n" >> _tmp; \
	done
	cat _tmp \
	    | grep -v "^ *$$" \
	    | sed 's/^ *//' \
	    | grep -v "/\*.*\*/" \
	    >$(VL_CSS)


clean:
	rm $(VL_JS) $(VL_CSS)
