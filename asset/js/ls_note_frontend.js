   jQuery(document).ready(function($){
    "use strict";

    // Bydefault statid inactive
    localStorage.setItem("notestatus", 'in-active');
    //add class
    jQuery(document.body).on('click', 'li#wp-admin-bar-add_sticky_notes a', function(){
        jQuery('body').toggleClass('note_additable');

    });
    //add class end
 
    function htmlDecode(input){
        var e = document.createElement('div');
        e.innerHTML = input;
        return e.childNodes[0].nodeValue;
    }




    jQuery(document.body).one('click','.supper_sticky_note p', function(evt){

        //e.preventDefault()
        //jQuery('p, span, li, ...').disableSelection();
        
        //$('p, span, li, ...').not('input').disableSelection();
 
        // var status = localStorage.getItem("notestatus");

        if(jQuery(this).find('sub.new').length == 0 && notesAjax.status == 'active'){
        var content = jQuery(this);
        var parentClass = $(this).parent().attr('class');
        var currentClass = $(this).attr('class');
        let position = window.getSelection().focusOffset;
        console.log('position1: ' + position);

        var myAnchorNodeValue = window.getSelection().anchorNode.nodeValue;
        var myAnchorOffset = window.getSelection().anchorOffset;
        var myFocusOffset =  window.getSelection().focusOffset;
        var d = document.createDocumentFragment(),
        myFocusNodeLength = window.getSelection().focusNode.nodeValue.length;
        var newCount = myAnchorNodeValue.slice(0, myAnchorOffset);
        position = newCount.length;

        // console.log('myFocusNodeLength: ' + myFocusNodeLength);
        // console.log('position2: ' + position);
        window.getSelection().anchorNode.nodeValue = myAnchorNodeValue.slice(0, myAnchorOffset) + '<sub class="new stickyQuestion" data-parent="'+parentClass+'" data-current="'+currentClass+'" data-position="'+position+'" class="note-question"><span class="note-question-icon-button"></span></sub>' + myAnchorNodeValue.slice(myAnchorOffset);
        
        var myFocusNodeValue = window.getSelection().focusNode.nodeValue;
    
        if(window.getSelection().focusNode.nodeValue.length - myFocusNodeLength > 0) {
            myFocusOffset += window.getSelection().focusNode.nodeValue.length - myFocusNodeLength;
        }
     
        
        position = myFocusOffset;
        window.getSelection().focusNode.nodeValue = myFocusNodeValue.slice(0, myFocusOffset) + myFocusNodeValue.slice(myFocusOffset);
        
        // window.getSelection().focusNode.nodeValue = myFocusNodeValue.slice(0, myFocusOffset) + '</span></sub>' + myFocusNodeValue.slice(myFocusOffset);
        console.log(myFocusNodeValue);
        var thishtml = jQuery(this).html();
        
        var selection = window.getSelection();
            var result = thishtml;
            result = decodeHtml(result);

            content.html(result);
            jQuery(content).find('.note-question-icon-button').each(function(k, v){
                addQtip(jQuery(v));
            });
        }
    });
    
    function decodeHtml(html) {
        var txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    }
    

    function ajaxfuncton(parntclass, currentClass, text_content, position, data_id, current_page_url){

        
        var current_page_id = notesAjax['current_page_id'];
        var user_id = notesAjax['user_id'];
        var title = notesAjax['title'];
       
        if(typeof data_id == 'undefined') data_id = '';
   
        jQuery.ajax({
           type : 'post',
           dataType: 'json',
            data : {
                'position'                : position,
                'current_page_id'         : current_page_id,
                'parentClass'             : parntclass,
                'user_id'                 : user_id,
                'text_content'            : text_content,
                'currentClass'            : currentClass,
                'title'                   : title,
                'data_id'                 : data_id,
                'action'                  : 'sendtonotesajax' 
            },
            url : notesAjax.ajax,
            success:function(data){
                //alert('dfsfs');
                // console.log(data);
                if(data.message == 'no'){
                    window.location.href = current_page_url;
                }
            }, 
            error:function(){
                console.log('error');
            }
            });    

    }
    // Apply tooltip on all <a/> elements with title attributes. Mousing over
    // these elements will the show tooltip as expected, but mousing onto the
    // tooltip is now possible for interaction with it's contents.
    // setTimeout(function(){
    // jQuery(document.body).on('click', 'i.fas.fa-question', function(){
    jQuery('.note-question-icon-button').each(function () {
        addQtip(jQuery(this));
    });
    


    function addQtip(element){
        var parntclass = element.closest('sub').data('parent');
        var position = element.closest('sub').data('position');
        var currentClass = element.closest('sub').data('current');
        var data_id = element.closest('sub').data('id');
        var status = (element.hasClass('old')) ? 'old' : 'new';
        var current_page_url = notesAjax.current_page_url;


        var textdata = (typeof notesAjax.textval[data_id] != 'undefined' && notesAjax.submitorreply[data_id] == 0) ? notesAjax.textval[data_id] : (typeof notesAjax.reply_notes[data_id] != 'undefined') ? notesAjax.reply_notes[data_id] : '';
        var admin_reply = (typeof notesAjax.admin1st_reply[data_id] != 'undefined' && notesAjax.submitorreply[data_id] == 0) ? notesAjax.admin1st_reply[data_id] : (typeof notesAjax.admin2nd_reply[data_id] != 'undefined') ? notesAjax.admin2nd_reply[data_id] : '';
        
        var reply = ( admin_reply != '' ) ? '<div class="admin-reply" style="background-color:'+notesAjax.notetextbg+'">Admin reply : '+admin_reply+'</div>' : '';

        console.log(reply);
        console.log(notesAjax.admin1st_reply[data_id]);
        console.log(notesAjax.admin2nd_reply[data_id]);
        //console.log(notesAjax.submitorreply[data_id]);
        var thisElement = element;
        var submitorreply = (notesAjax.submitorreply[data_id] == 0 || typeof notesAjax.submitorreply[data_id] == 'undefined' ) ? 'SUBMIT' : 'REPLY';
        if(status == 'old' && submitorreply == 'SUBMIT' ) submitorreply = 'Update';
        if(notesAjax.login_status == 'logout') submitorreply = '';
        
        element.qtip({
            content: function() 
                {
                    var text = '<div data-parent="'+parntclass+'" data-current="'+currentClass+'" data-id="'+data_id+'" data-position="'+position+'" class="sticky-note-theme">'
                    +'<div class="note-top-option" style="background-color:'+notesAjax.nottopcolor+'">'
                    +'<div class="note-plus-button"><div class="note-plus-icon-button"></div></div>'
                    +'<div class="note-color-button"><div class="note-color-icon-button"></div></div>'
                    +'<div class="note-exest-button"><div class="note-exest-icon-button"></div></div></div>'
                    +'<div class="note-top-option-all" style="display:none;">'
                    +'<div class="note-top-option-color">'
                    +'<div class="color-1 color-option"></div><div class="color-2 color-option"></div>'
                    +'<div class="color-3 color-option"></div><div class="color-4 color-option"></div>'
                    +'<div class="color-5 color-option"></div><div class="color-6 color-option"></div>'
                    +'<div class="color-7 color-option"></div>'
                    +'<div class="note-top-option-comment-list"><p class="note-go-to-comment">Go to your comments list</p></div>'
                    +'<div class="note-top-option-delete-comment" data-position="'+position+'" data-id="'+data_id+'"><p class="note-top-option-delete-all-comment">Delete your comment</p></div>'
                    +'</div>'
                    +'</div>'
                    +reply
                    +'<textarea name="textarea" style="background-color:'+notesAjax.notetextbg+'" class="sticky-note-text-editor" placeholder="Ask Questions..">'+textdata+'</textarea>';
                    if(submitorreply !='') text+='<button class="note-reply" style="background-color:'+notesAjax.nottopcolor+'">'+submitorreply+'</button>'
                    text+='</div>';
                    jQuery(document.body).on('click', 'button.note-reply, div.note-exest-button', function(){
                        $(element).qtip().hide();
                        // $(text).remove();
                    });

                    return text;
                    
                },
            show   : 'click',
            hide   : {
                fixed: true,
                event: 'click unfocus'
            },
            events: {
                hide: function(event, api) {
                    var text_content = jQuery(this).find('textarea').val();
                    if(notesAjax.login_status != 'logout'){
                        ajaxfuncton(parntclass, currentClass, text_content, position, data_id, current_page_url);
                    }
                }
                
            }
        });
    }
    

    //css color 
        jQuery(document.body).on('click', ".note-color-icon-button", function(){
          $(".note-top-option").attr("style", "display:none");
          $(".note-top-option-all").attr("style", "display:block");
        });

        jQuery(document.body).on('click', ".sticky-notes-user", function(){
            $(".sticky-notes-user").attr("style", "display:none");
            $("ul.user-button-ul").attr("style", "display:block");
          });


        jQuery(document.body).on('click', ".color-option", function(){
            $(".note-top-option").attr("style", "display:block");
            $(".note-top-option-all").attr("style", "display:none");

            var classname = jQuery(this).attr('class');
            switch(classname){
                case 'color-1 color-option':
                        $(".note-top-option").attr("style", "background-color:#F0B30C");
                        $(".sticky-note-theme .admin-reply").attr("style", "background-color:#FFF7C3");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#FFF7C3");
                        $("button.note-reply").attr("style", "background-color:#F0B30C");
                break;
                case 'color-2 color-option':
                        $(".note-top-option").attr("style", "background-color:#CEE9FD");
                        $(".sticky-note-theme .admin-reply").attr("style", "background-color:#E2F0FF");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#E2F0FF");
                        $("button.note-reply").attr("style", "background-color:#CEE9FD");
                break;
                case 'color-3 color-option':
                        $(".note-top-option").attr("style", "background-color:#B45FF6");
                        $(".sticky-note-theme .admin-reply").attr("style", "background-color:#EFC9FF");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#EFC9FF");
                        $("button.note-reply").attr("style", "background-color:#B45FF6");
                break;
                case 'color-4 color-option':
                        $(".note-top-option").attr("style", "background-color:#F87098");
                        $(".sticky-note-theme .admin-reply").attr("style", "background-color:#FFC9DD");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#FFC9DD");
                        $("button.note-reply").attr("style", "background-color:#F87098");
                break;
                case 'color-5 color-option':
                        $(".note-top-option").attr("style", "background-color:#3FD981");
                        $(".sticky-note-theme .admin-reply").attr("style", "background-color:#DEF6D9");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#DEF6D9");
                        $("button.note-reply").attr("style", "background-color:#3FD981");
                break;
                case 'color-6 color-option':
                        $(".note-top-option").attr("style", "background-color:#AEAEAE");
                        $(".sticky-note-theme .admin-reply").attr("style", "background-color:#EBEBEB");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#EBEBEB");
                        $("button.note-reply").attr("style", "background-color:#AEAEAE");
                break;
                case 'color-7 color-option':
                        $(".note-top-option").attr("style", "background-color:#686868");
                        $(".sticky-note-theme .admin-reply").attr("style", "background-color:#5D5D5D;color:#ffff");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#5D5D5D;color:#ffff");
                        $("button.note-reply").attr("style", "background-color:#686868");
                break;

            }

            var topOption = jQuery('.note-top-option').css('backgroundColor'),
            textEditorBG = jQuery('textarea.sticky-note-text-editor').css('backgroundColor'),
            noteOptions = {
                topoption:topOption,
                texteditorbg:textEditorBG
            }
            noteOptions = JSON.stringify(noteOptions);
            setCookie('noteoptions', noteOptions, 365);

        });


        jQuery('.reply').click(function(){
            jQuery(this).next('.modal-overlay').addClass('active');
            jQuery(this).next('.modal-overlay').find('.modal').addClass('active');
        });
    
        jQuery('.close-modal').click(function(){
            jQuery(this).closest('.modal-overlay').removeClass('active');
            jQuery(this).closest('.modal').removeClass('active');
        });
        

        // onclick submit button qtip hide 
        function hideQtip(event){
            
        }

        // Set Local storage if click new commet  wp-admin-bar-note_new_comment
        // jQuery(document.body).on('click', 'li#wp-admin-bar-note_new_comment a', function(e){
        //     e.preventDefault();
        //     localStorage.setItem("notestatus", 'active');
        //     var status = localStorage.getItem("notestatus");
        //     if(status == 'active'){
        //         jQuery('sub.note-question').removeClass('d-none');
        //     }
        // });

        jQuery(document.body).on('click', ".note-go-to-comment", function(){

            jQuery.ajax({
                type : 'post',
                dataType: 'json',
                data : {
                    'action'                  : 'allcommentajax' 
                },
                url : notesAjax.ajax,
                success:function(data){
                    console.log(data);
                    if(data.message == 'success'){
                        var win = window.open(data.page_url , '_blank');
                        win.focus();
                    }
                }
            });
        });

        jQuery(document.body).on('click', ".note-top-option-delete-comment", function(){

            var position = jQuery(this).data('position');

            jQuery.ajax({
                type : 'post',
                dataType: 'json',
                data : {
                    'position'                : position,
                    'action'                  : 'deletecommentajax' 
                },
                url : notesAjax.ajax,
                success:function(data){
                    console.log(data);
                    if(data.message == 'success'){
                        window.location.reload();
                    }
                }
            });
        });

        jQuery(document.body).on('click', "#wp-admin-bar-admin_bar_custom_menu > a", function(){
            event.preventDefault();
            console.log('data');
        });

        jQuery(document.body).on('click', "#wp-admin-bar-note_old_comments a", function(){
            jQuery.ajax({
                type : 'post',
                dataType: 'json',
                data : {
                    'action'                  : 'allcommentajax' 
                },
                url : notesAjax.ajax,
                success:function(data){
                    console.log(data);
                    if(data.message == 'success'){
                        var win = window.open(data.page_url , '_blank');
                        win.focus();
                    }
                }
            });
        });
   }); // End Document ready

function openTab(evt, cityName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(cityName).style.display = "block";
    evt.currentTarget.className += " active";
}

function setCookie(name,value,days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}
