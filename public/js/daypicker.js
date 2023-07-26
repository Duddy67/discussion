(function($) {

  // Run a function when the page is fully loaded including graphics.
  $(window).on('load', function() {

      // Loop through the datetime inputs.
      $('.daypicker').each(function(index, element) {
          let format = $(element).data('format');
          let timePicker = ($(element).data('time') === undefined) ? false : true;

          // Set the init according to the input dataset values.
          $('#'+element.id).daterangepicker({
              'singleDatePicker': true,
              'timePicker': timePicker,
              'timePicker24Hour': true,
              'timePickerIncrement': 15,
              locale: {
                  format: format,
              },
          });

          // Set the time format (if any).
          let timeFormat = (timePicker) ? ' HH:mm' : '';

          $('#'+element.id).on('apply.daterangepicker', function(ev, picker) {
              // Convert the selected datetime in MySQL format. 
              let datetime = picker.startDate.format('YYYY-MM-DD'+timeFormat);

              // Set the hidden field to the selected datetime
              $('#_'+element.id).val(datetime);

              // Submit the possible form that sends the datetime data.
              if ($('#'+element.id+'Form').length > 0) {
                  $('#'+element.id+'Form').submit();
              }
          });

          // Set to the current date.
          let startDate = moment().format(format);

          // A datetime has been previously set.
          if ($(element).data('date') != 0) {
              // Get date from dataset parameters. 
              let date = $(element).data('date');

              // Check for time value (if any).
              if (timePicker) {
                  // Concatenate date and time.
                  date = date+' '+$(element).data('time');
              }

              startDate = moment(date).format(format);
              // Set the hidden field to the datetime previously set.
              $('#_'+element.id).val(date);
          }
          else {
              // Set the hidden field to the current datetime
              $('#_'+element.id).val(moment().format('YYYY-MM-DD'+timeFormat));
          }

          // Initialize the date field.
          $('#'+element.id).data('daterangepicker').setStartDate(startDate);
      });
  });
})(jQuery);

