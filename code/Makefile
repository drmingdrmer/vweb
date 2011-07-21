VL_JS = js/vlbum_c.js
VL_CSS = css/vlbum_c.css

jsfiles := $(shell cat vlbum.html | grep "jscompact" | grep -o "src=[^>]*" | tr -d '"' | cut -c 5- )
cssfiles := $(shell cat vlbum.html | grep "csscompact" | grep -o "href=[^>]*" | tr -d '"' | cut -c 6- )


vlbum: $(VL_JS) $(VL_CSS)
	cat vlbum.html \
	    | grep -v "csscompact\|jscompact" \
	    | grep -v "^ *$$" \
	    | grep -v "^ *<!--.*-->$$" \
	    | awk '/^ *<!--/{ cmtStart = 1; } { if(!cmtStart) {print $$0} } /-->$$/{cmtStart=0}' \
	    > _tmp
	# mv _tmp vlbum.html


$(VL_JS): $(jsfiles)
	>$(VL_JS)
	for fn in $(jsfiles); do \
	    cat $$fn >> $(VL_JS); \
	    echo ";" >> $(VL_JS); \
	done

$(VL_CSS): $(cssfiles)
	>$(VL_CSS)
	for fn in $(jsfiles); do \
	    cat $$fn >> $(VL_CSS); \
	    echo "" >> $(VL_CSS); \
	done


clean:
	rm $(VL_JS) $(VL_CSS)
