VL_JS = js/vlbum_c.js
VL_CSS = css/vlbum_c.js

jsfiles := $(shell cat vlbum.html | grep "jscompact" | grep -o "src=[^>]*" | tr -d '"' | cut -c 5- )
cssfiles := $(shell cat vlbum.html | grep "csscompact" | grep -o "href=[^>]*" | tr -d '"' | cut -c 6- )


vlbum: $(VL_JS) $(VL_CSS)


$(VL_JS):
	>$(VL_JS)
	for fn in $(jsfiles); do \
	    cat $$fn >> $(VL_JS); \
	    echo ";" >> $(VL_JS); \
	done

$(VL_CSS):
	>$(VL_CSS)
	for fn in $(jsfiles); do \
	    cat $$fn >> $(VL_CSS); \
	    echo "" >> $(VL_CSS); \
	done


clean:
	[ -f $(VL_JS) ] && rm $(VL_JS)
