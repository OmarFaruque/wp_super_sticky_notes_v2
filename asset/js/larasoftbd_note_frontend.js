
   
   jQuery(document).ready(function($){
   
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

    $(document).ready(function(){
        var str = $(".supper_sticky_note").html();
        var regex = /<br\s*[\/]?>/gi;
        $(".supper_sticky_note").html(str.replace(regex, "<div class='br-replace'></div><p>"));
    });


    jQuery(document.body).one('click','.supper_sticky_note p', function(){

        var content = jQuery(this);
        var thishtml = jQuery(this).html();
        let position = window.getSelection().focusOffset;

        String.prototype.splice = function(idx, rem, str) {
            return this.slice(0, idx) + str + this.slice(idx + Math.abs(rem));
        };

        parentClass = $(this).parent().attr('class');
        currentClass = $(this).attr('class');

        var result = thishtml.splice(position, 0, '<sub data-parent="'+parentClass+'" data-current="'+currentClass+'" data-position="'+position+'" class="note-question"><span class="note-question-icon-button"></span></sub>');
        
        content.html(result);
        var dynamici = jQuery(content).find('.note-question-icon-button');
        
        addQtip(jQuery(dynamici));
    });
    

    function ajaxfuncton(parntclass, currentClass, text_content, position, data_id){

        console.log(parntclass);
        console.log(currentClass);
        console.log(text_content); 
        console.log(position);  
        var current_page_id = notesAjax['current_page_id'];
        var user_id = notesAjax['user_id'];
        var title = notesAjax['title'];
        console.log(current_page_id); 
        console.log(user_id); 
        console.log(notesAjax.ajax);
        console.log(title);
        console.log(data_id);
   
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
                console.log(data);
                if(data.message == 'success'){
                    }
                }
            });    

    }
    // Apply tooltip on all <a/> elements with title attributes. Mousing over
    // these elements will the show tooltip as expected, but mousing onto the
    // tooltip is now possible for interaction with it's contents.
    // setTimeout(function(){
    // jQuery(document.body).on('click', 'i.fas.fa-question', function(){
    $('.note-question-icon-button').each(function () {
        addQtip(jQuery(this));
    });


    function addQtip(element){
        var parntclass = element.closest('sub').data('parent');
        var position = element.closest('sub').data('position');
        var currentClass = element.closest('sub').data('current');
        var data_id = element.closest('sub').data('id');
        var textdata = (typeof notesAjax.textval[data_id] != 'undefined') ? notesAjax.textval[data_id] : '';
        var submitorreply = (notesAjax.submitorreply[data_id] == 0 || typeof notesAjax.submitorreply[data_id] == 'undefined' ) ? 'SUBMIT' : 'REPLY';
        element.qtip({
            content: 
                {
                    text: '<div data-parent="'+parntclass+'" data-current="'+currentClass+'" data-id="'+data_id+'" data-position="'+position+'" class="sticky-note-theme">'
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
                    +'<div class="note-top-option-delete-comment" data-id="'+data_id+'"><p class="note-top-option-delete-all-comment">Delete your comment</p></div>'
                    +'</div>'
                    +'</div><textarea name="textarea" style="background-color:'+notesAjax.notetextbg+'" class="sticky-note-text-editor" placeholder="Ask Questions..">'+textdata+'</textarea>'
                    +'<button class="note-reply" style="background-color:'+notesAjax.nottopcolor+'">'+submitorreply+'</button>'
                    +'</div>',
                    
                },
            show   : 'click',
            hide   : 'click',
            events: {
                hide: function(event, api) {
                    text_content = jQuery(this).find('textarea').val(),
                    ajaxfuncton(parntclass, currentClass, text_content, position, data_id);
                }
                
            }
        });
    }
    

    //css color 
    $(document).ready(function(){
        jQuery(document.body).on('click', ".note-color-icon-button", function(){
          $(".note-top-option").attr("style", "display:none");
          $(".note-top-option-all").attr("style", "display:block");
        });
        jQuery(document.body).on('click', ".color-option", function(){
            $(".note-top-option").attr("style", "display:block");
            $(".note-top-option-all").attr("style", "display:none");

            var classname = jQuery(this).attr('class');
            switch(classname){
                case 'color-1 color-option':
                        $(".note-top-option").attr("style", "background-color:#F0B30C");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#FFF7C3");
                        $("button.note-reply").attr("style", "background-color:#F0B30C");
                break;
                case 'color-2 color-option':
                        $(".note-top-option").attr("style", "background-color:#CEE9FD");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#E2F0FF");
                        $("button.note-reply").attr("style", "background-color:#CEE9FD");
                break;
                case 'color-3 color-option':
                        $(".note-top-option").attr("style", "background-color:#B45FF6");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#EFC9FF");
                        $("button.note-reply").attr("style", "background-color:#B45FF6");
                break;
                case 'color-4 color-option':
                        $(".note-top-option").attr("style", "background-color:#F87098");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#FFC9DD");
                        $("button.note-reply").attr("style", "background-color:#F87098");
                break;
                case 'color-5 color-option':
                        $(".note-top-option").attr("style", "background-color:#3FD981");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#DEF6D9");
                        $("button.note-reply").attr("style", "background-color:#3FD981");
                break;
                case 'color-6 color-option':
                        $(".note-top-option").attr("style", "background-color:#AEAEAE");
                        $("textarea.sticky-note-text-editor").attr("style", "background-color:#EBEBEB");
                        $("button.note-reply").attr("style", "background-color:#AEAEAE");
                break;
                case 'color-7 color-option':
                        $(".note-top-option").attr("style", "background-color:#686868");
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