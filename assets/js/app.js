document.addEventListener('DOMContentLoaded', function () {
    // Initialize the admin dashboard functionality
    const carForm = document.getElementById('car-form');
    const packageForm = document.getElementById('package-form');
    const bookingForm = document.getElementById('booking-form');

    if (carForm) {
        carForm.addEventListener('submit', function (event) {
            event.preventDefault();
            // Add AJAX call to handle car submission
            console.log('Car form submitted');
        });
    }

    if (packageForm) {
        packageForm.addEventListener('submit', function (event) {
            event.preventDefault();
            // Add AJAX call to handle package submission
            console.log('Package form submitted');
        });
    }

    if (bookingForm) {
        bookingForm.addEventListener('submit', function (event) {
            event.preventDefault();
            // Add AJAX call to handle booking submission
            console.log('Booking form submitted');
        });
    }

    // Additional JavaScript functionality can be added here
});


jQuery(document).ready(function ($) {
    var mediaUploader;
    $('.car_image_upload_button').click(function (e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media({
            title: 'เลือกรูปภาพรถ',
            button: {
                text: 'ใช้รูปนี้'
            },
            multiple: false
        });

        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#vehicle_image').val(attachment.id);
            $('.car_image_preview').html('<img src="' + attachment.url + '" class="mt-2" style="max-width:200px;">');
        });

        mediaUploader.open();
    });
});