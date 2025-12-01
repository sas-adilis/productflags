$(document).ready(function () {
    $(document).on('click', '.js-product-flag-btn', function () {
       const $button = $(this);
       const ajaxUrl = $button.data('ajax-url');

       $.ajax({
           url: ajaxUrl,
           method: 'GET',
           dataType: 'json',
           success: function (response) {
               if (response.success) {
                   showSuccessMessage('Product flag updated successfully.');
                   $button.closest('.btn-group').find('.js-product-flag-btn').removeClass('active');
                   $button.addClass('active');
               } else {
                   showErrorMessage('Failed to update product flag');
               }
           },
           error: function () {
               showErrorMessage('An error occurred while processing the request.');
           }
       });
    });
});