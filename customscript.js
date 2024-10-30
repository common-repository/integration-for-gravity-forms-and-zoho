/*
 * Scripts 
 */
function zgfgetModuleList(curEle){
    jQuery('.moduleloading').css("display","inline-block");
    jQuery.ajax({
           type: 'POST',
           url: ajaxurl,
           data: {
               action: 'zgfmodulelist',
           },
           success: function (data) {
             if(data !='' && data !=null){
               jQuery('.moduleloading').css("display","none");
               jQuery('#moduleList').html(data).removeAttr('disabled','');
               jQuery('#fetchmodule').removeAttr('disabled','');

             }
           },
           error: function (errorThrown) {

           }
       });

}


function zgfupdateLayout(curEle){
  var moduleName = jQuery(curEle).val();
  jQuery('#layoutloading').css("display","inline-block");
  jQuery.ajax({
         type: 'POST',
         url: ajaxurl,
         data: {
             action: 'zgfgetCrmFields',
             modulename:moduleName
         },
         success: function (data) {
           if(data !='' && data !=null){
             jQuery('#layoutloading').css("display","none");
             jQuery('#layoutlist').html(data).removeAttr('disabled','');
              jQuery('#saveform').removeAttr('disabled','');


           }
         },
         error: function (errorThrown) {

         }

     });
}
function zgfDeleteForm(curEle){
  var formId = jQuery(curEle).attr('data-id');
  jQuery.ajax({
         type: 'POST',
         url: ajaxurl,
         data: {
             action: 'zgfdeleteForm',
             formId:formId
         },
         success: function (data) {
          alert(data);
          location.reload();
         },
         error: function (errorThrown) {

         }

     });
}

function zgfgetuserlist(curEle){
  var usertype = jQuery(curEle).val();
  var modulename = jQuery("#modulename").val();
  var acionnname = "zgfgetuserlist";
  var elementid = "#usertype";
  jQuery.ajax({
         type: 'POST',
         url: ajaxurl,
         data: {
             action: acionnname,
             usertype:usertype,
             modulename:modulename
         },
         success: function (data) {
           if(data !='' && data !=null){
             jQuery(elementid).html(data).removeAttr('disabled','');
           }

         },
         error: function (errorThrown) {

         }

     });
}
function zgfcreatePopup(){
  jQuery('.createPopup').show();
}
