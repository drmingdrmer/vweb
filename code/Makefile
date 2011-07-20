VLJS = js/vlbum.js
jsfiles = `cat vlbum.html | grep "jscompact" | grep -o "src=[^>]*" | tr -d '"' | cut -c 5- `

vlbum: $(VLJS)


$(VLJS):
	cat    $(jsfiles) \
	    > $(VLJS)

clean:
	rm $(VLJS) 
