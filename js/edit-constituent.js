(function($) {
    $(document).ready( function() {
       $('.add-donor-child').click( function() {
           if ( $(this).hasClass('disabled') )
               return false;

           var inputs = $('input[name="child_class_years[]"]'),
               index = inputs.length + 1,
               newInput = $('input[name="child_class_years[]"]').last().clone().attr('id', 'child_class_years' + index).val('');

           inputs.last().after(newInput);

           if ( index >= 5 )
               $(this).addClass('disabled');

           return false;
       });
    });
})(jQuery);


