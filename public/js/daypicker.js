(function($) {

  // Run a function when the page is fully loaded including graphics.
  $(window).on('load', function() {
      $('.daypicker').daterangepicker({
          'singleDatePicker': true,
          'timePicker': false,
          //'autoUpdateInput': false,
          locale: {
              format: 'D MMM YYYY',
          }
      },
      function(start, end, label) {
        //console.log('New date range selected: ' + start.format('YYYY-MM-DD h:mm A') + ' to ' + end.format('YYYY-MM-DD h:mm A') + ' (predefined range: ' + label + ')');
      });

      $('.daypicker').on('apply.daterangepicker', function(ev, picker) {
          // Convert the selected datetime in MySQL format. 
          let datetime = picker.startDate.format('YYYY-MM-DD');

          // Set the hidden field to the selected datetime
          $('#_'+$(this).attr('id')).val(datetime);

          $('#dayPicker').submit();
      });

      $('.daypicker').on('show.daterangepicker', function(ev, picker) {
          //$('.daterangepicker').hide();
      });

      $.fn.initStartDates();   
  });

  $.fn.initStartDates = function() {
      // The fields to initialized.
      let fields = $('.daypicker');
      
      for (let i = 0; i < fields.length; i++) {
          // Check first whether the element exists.
          if ($('#'+fields[i].id).length) {
              // Set to the current date.
              let startDate = moment().format('D MMM YYYY');

              // A datetime has been previously set.
              if ($('#'+fields[i].id).data('date') != 0) {
                  startDate = moment($('#'+fields[i].id).data('date')).format('D MMM YYYY');
                  // Set the hidden field to the datetime previously set.
                  $('#_'+fields[i].id).val(startDate);
              }
              else {
                  // Set the hidden field to the current datetime
                  $('#_'+fields[i].id).val(moment().format('YYYY-MM-DD'));
              }

              // Initialize the date field.
              $('#'+fields[i].id).data('daterangepicker').setStartDate(startDate);
          }
      }
  }

})(jQuery);

