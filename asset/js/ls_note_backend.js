function openTab(evt, cityName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    jQuery('.tabcontent').hide();
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(cityName).style.display = "block";
    evt.currentTarget.className += " active";
}
jQuery(document).ready(function($){

    jQuery('.reply').click(function(){
        jQuery(this).next('.modal-overlay').addClass('active');
        jQuery(this).next('.modal-overlay').find('.modal').addClass('active');
    });

    jQuery('.close-modal').click(function(){
        jQuery(this).closest('.modal-overlay').removeClass('active');
        jQuery(this).closest('.modal').removeClass('active');
    });

    // Settings form submit
    jQuery(document.body).on('change', 'select#allcommentpage', function(e){
        jQuery(this).closest('form').submit();
    });
    jQuery(document.body).on('change', 'select#buttonposition', function(e){
        jQuery(this).closest('form').submit();
    });


    if(jQuery('.jquerydatatable').length){
        jQuery('.jquerydatatable').DataTable();
    }

}); // End Document ready