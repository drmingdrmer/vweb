$.extend( $.vweb.ui.main.edit.cont, { dropbox: {
    init: function( self, e ) {
        $.log( 'dropbox init' );

        var isChrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
        var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;

        if(!isChrome && !isFirefox) {
            $("#browser-warning").fadeIn(125);
        }


        // Add drag handling to target elements
        $("body")[ 0 ].addEventListener("dragenter", onDragEnter, false);
        $("#drop-box-overlay")[ 0 ].addEventListener("dragleave", onDragLeave, false);
        $("#drop-box-overlay")[ 0 ].addEventListener("dragover", noopHandler, false);

        // Add drop handling
        document.getElementById("drop-box-overlay").addEventListener("drop", onDrop, false);

        // init the widgets
        $("#upload-status-progressbar").progressbar();


        function noopHandler(evt) {
            evt.stopPropagation();
            evt.preventDefault();
        }

        function onDragEnter(evt) {
            $("#drop-box-overlay").fadeIn(125);
        }

        function onDragLeave(evt) {
            /*
            * We have to double-check the 'leave' event state because this event stupidly
            * gets fired by JavaScript when you mouse over the child of a parent element;
            * instead of firing a subsequent enter event for the child, JavaScript first
            * fires a LEAVE event for the parent then an ENTER event for the child even
            * though the mouse is still technically inside the parent bounds. If we trust
            * the dragenter/dragleave events as-delivered, it leads to "flickering" when
            * a child element (drop prompt) is hovered over as it becomes invisible,
            * then visible then invisible again as that continually triggers the enter/leave
            * events back to back. Instead, we use a 10px buffer around the window frame
            * to capture the mouse leaving the window manually instead. (using 1px didn't
            * work as the mouse can skip out of the window before hitting 1px with high
            * enough acceleration).
            */
            var offset = 50;
            if(evt.pageX < offset || evt.pageY < offset
                || $(window).width() - evt.pageX < offset
                || $(window).height - evt.pageY < offset) {
                $("#drop-box-overlay").fadeOut(125);
            }
        }

        function onDrop(evt) {
            // Consume the event.
            noopHandler(evt);

            // Hide overlay
            $("#drop-box-overlay").fadeOut(0);

            // Empty status text
            $("#upload-details").html("");

            // Reset progress bar incase we are dropping MORE files on an existing result page
            $("#upload-status-progressbar").progressbar({value:0});

            // Show progressbar
            $("#upload-status-progressbar").fadeIn(0);

            // Get the dropped files.
            var files = evt.dataTransfer.files;

            // If anything is wrong with the dropped files, exit.
            if(typeof files == "undefined" || files.length == 0)
                return;

            // Update and show the upload box
            var label = (files.length == 1 ? " file" : " files");
            $("#upload-count").html(files.length + label);
            $("#upload-thumbnail-list").fadeIn(125);

            // Process each of the dropped files individually
            for(var i = 0, length = files.length; i < length; i++) {
                uploadFile(files[i], length);
            }
        }

        function uploadFile(file, totalFiles) {
            var reader = new FileReader();

            // Handle errors that might occur while reading the file (before upload).
            reader.onerror = function(evt) {
                var message;

                // REF: http://www.w3.org/TR/FileAPI/#ErrorDescriptions
                switch(evt.target.error.code) {
                    case 1:
                        message = file.name + " not found.";
                        break;

                    case 2:
                        message = file.name + " has changed on disk, please re-try.";
                        break;

                    case 3:
                        messsage = "Upload cancelled.";
                        break;

                    case 4:
                        message = "Cannot read " + file.name + ".";
                        break;

                    case 5:
                        message = "File too large for browser to upload.";
                        break;
                }

                $("#upload-status-text").html(message);
            }

            // When the file is done loading, POST to the server.
            reader.onloadend = function(evt){
                var data = evt.target.result;

                // Make sure the data loaded is long enough to represent a real file.
                if(data.length > 128){
                    /*
                    * Per the Data URI spec, the only comma that appears is right after
                    * 'base64' and before the encoded content.
                    */
                    var base64StartIndex = data.indexOf(',') + 1;

                    /*
                    * Make sure the index we've computed is valid, otherwise something 
                    * is wrong and we need to forget this upload.
                    */
                    if(base64StartIndex < data.length) {
                        $.ajax({
                            type: 'POST',
                            url: '/upload',
                            data: data.substring(base64StartIndex), // Just send the Base64 content in POST body
                            processData: false, // No need to process
                            timeout: 60000, // 1 min timeout
                            dataType: 'text', // Pure Base64 char data
                            beforeSend: function onBeforeSend(xhr, settings) {
                                // Put the important file data in headers
                                xhr.setRequestHeader('x-file-name', file.name);
                                xhr.setRequestHeader('x-file-size', file.size);
                                xhr.setRequestHeader('x-file-type', file.type);

                                // Update status
                                $("#upload-status-text").html("Uploading and Processing " + file.name + "...");
                            },
                            error: function onError(XMLHttpRequest, textStatus, errorThrown) {
                                // Have to increment the progress bar even if it's a failed upload.
                                updateAndCheckProgress(totalFiles, "Upload <span style='color: red;'>failed</span>");

                                if(textStatus == "timeout") {
                                    $("#upload-details").html("Upload was taking too long and was stopped.");
                                } else {
                                    $("#upload-details").html("An error occurred while uploading the image.");
                                }
                            },
                            success: function onUploadComplete(response) {
                                response = $.parseJSON(response);

                                // If the parse operation failed (for whatever reason) bail
                                if(!response || typeof response == "undefined") {
                                    // Error, update the status with a reason as well.
                                    $("#upload-status-text").html("Upload <span style='color: red;'>failed</span>");
                                    $("#upload-details").html("The server was unable to process the upload.");

                                    return;
                                }

                                if(response.success) {
                                    // Update status
                                    $("#upload-status-text").html(response.originalFileName + " Uploaded!");

                                    updateAndCheckProgress(totalFiles);

                                    var markup = new String();
                                    markup += "<div class='resultBox'>";
                                    markup += "  <div style='float: left;'>";
                                    markup += "    <span class='thumbnail-container'>";

                                    // First, try and use the generated thumbnail as the preview
                                    if(response.thumbnail.url)
                                        markup += "      <img width='150' src='" + response.thumbnail.url + "' />";
                                    // In the case of already-small-files, there will be no thumbnail, so use original.
                                    else if(response.original.url)
                                        markup += "      <img width='150' src='" + response.original.url + "' />";
                                    // Well the server couldn't process the image I guess, ruh-roh!
                                    else
                                        markup += "      <img width='150' src='/public/images/missing-thumbnail.png' />";

                                    markup += "    </span>";
                                    markup += "  </div>";
                                    markup += "  <div style='float: left; vertical-align: top;'>";
                                    markup += "    <ul>";

                                    markup += generateUploadResult("Original", response.original);

                                    if(response.large.url)
                                        markup += generateUploadResult("Large", response.large);

                                    if(response.medium.url)
                                        markup += generateUploadResult("Medium", response.medium);

                                    if(response.small.url)
                                        markup += generateUploadResult("Small", response.small);

                                    if(response.thumbnail.url)
                                        markup += generateUploadResult("Thumbnail", response.thumbnail);

                                    markup += "    </ul>";
                                    markup += "  </div>";
                                    markup += "  <div style='clear: both;'></div>"
                                    markup += "</div>";

                                    $("#upload-thumbnail-list").append(markup);

                                    // Add focus listener to the new text fields to make copying easier
                                    $("#upload-thumbnail-list input[type=text]").hover(
                                        function(){
                                            this.select();
                                        }, function() {
                                            this.selectionStart = this.selectionEnd = -1;
                                        });
                                        // And a click listener, otherwise the behavior feels weird/difficult.
                                        $("#upload-thumbnail-list input[type=text]").click(function(){
                                            this.select();
                                        });
                                } else {
                                    // Error, update the status with a reason as well.
                                    $("#upload-status-text").html("Upload <span style='color: red;'>failed</span>");
                                    $("#upload-details").html(response.message);

                                    updateAndCheckProgress(totalFiles);

                                    // Add an errored-upload placeholder
                                    var markup = new String();
                                    markup += "<div class='resultBox'>";
                                    markup += "  <div style='float: left;'>";
                                    markup += "    <span class='thumbnail-container'>";
                                    markup += "      <img width='150' src='/public/images/missing-thumbnail.png' />";
                                    markup += "    </span>";
                                    markup += "  </div>";
                                    markup += "  <div style='float: left; vertical-align: top;'>";
                                    markup += "    <ul>";

                                    markup += generateUploadResult("Bad File", response.original, response.originalFileName);

                                    markup += "    </ul>";
                                    markup += "  </div>";
                                    markup += "  <div style='clear: both;'></div>"
                                    markup += "</div>";

                                    $("#upload-thumbnail-list").append(markup);
                                }
                            }
                        });
                    }
                }
            };

            // Start reading the image off disk into a Data URI format.
            reader.readAsDataURL(file);
        }

        /**
        * Used to update the progress bar and check if all uploads are complete. Checking
        * progress entails getting the current value from the progress bar and adding
        * an incremental "unit" of completion to it since all uploads run async and
        * complete at different times we can't just update in-order.
        * 
        * This is only ever meant to be called from an upload 'success' handler.
        */
        function updateAndCheckProgress(totalFiles, altStatusText) {
            var currentProgress = $("#upload-status-progressbar").progressbar("option", "value");
            currentProgress = currentProgress + (100 / totalFiles);

            // Update the progress bar
            $("#upload-status-progressbar").progressbar({value: currentProgress});

            // Check if that was the last file and hide the animation if it was
            if(currentProgress >= 99) {
                $("#upload-status-text").html((altStatusText ? altStatusText : "All Uploads Complete!"));
                $("#upload-animation").hide();
            }
        }

        function generateUploadResult(label, image, altInputValue) {
            var markup = "    <li><span class='label'>" + label + "</span><input readonly type='text' value='";

            if(image.url)
                markup += image.url;
            else
                markup += altInputValue;

            markup += "' /></li><li><span class='details'>";

            if(image.width)
                markup += image.width + "x" + image.height;

            if(image.width && image.sizeInBytes)
                markup += " - ";

            if(image.sizeInBytes)
                markup += (image.sizeInBytes / 1000) + " KB";

            markup += "</span></li>";

            return markup;
        }
    }
} } );
