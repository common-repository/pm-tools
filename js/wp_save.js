jQuery(document).ready(function($){
 if($('#template input[type="submit"]').length == 1){
  $(window).keydown(function(event){
   if(!event.ctrlKey || event.keyCode != 83) return true;
    event.preventDefault();
    $('#template input[type="submit"]').trigger('click');
    return false;
  });
 }
});