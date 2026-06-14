"use strict"
/* SweetAlert2 demo helpers — updated for Swal.fire() v11 API */
$(document).on('click', '#sweet-success', function(e) {
    Swal.fire({ icon: 'success', title: 'Success', text: 'Make your <b style="color:#38E25D;">Success</b> story!', confirmButtonColor: '#1d4ed8' });
});

$(document).on('click', '#sweet-error', function(e) {
    Swal.fire({ icon: 'error', title: 'Error!', text: 'You clicked the <b style="color:#FF4A55;">error</b> button!', confirmButtonColor: '#dc2626' });
});

$(document).on('click', '#sweet-warning', function(e) {
    Swal.fire({ icon: 'warning', title: 'Warning!', text: 'Be careful <b style="color:#FF9325;">warning</b> button!', confirmButtonColor: '#d97706' });
});

$(document).on('click', '#sweet-info', function(e) {
    Swal.fire({ icon: 'info', title: 'Info!', text: 'You clicked the <b style="color:#5ECFFF;">info</b> button!', confirmButtonColor: '#1d4ed8' });
});

$(document).on('click', '#sweet-question', function(e) {
    Swal.fire({ icon: 'question', title: 'Question!', text: 'You clicked the <b style="color:#924AEF;">question</b> button!', showCancelButton: true, confirmButtonColor: '#1d4ed8', cancelButtonColor: '#64748b' });
});

// Alert With Custom Icon and Background Image
$(document).on('click', '#sweet-icon', function(event) {
    Swal.fire({
        title: 'Custom icon!',
        text: 'Alert with a custom image.',
        imageUrl: '../../assets/img/svg/bell.svg',
        imageWidth: 100,
        imageHeight: 100,
        imageAlt: 'Custom Icon/Image',
        animation: false
    })
});

$(document).on('click', '#sweet-image', function(event) {
    Swal.fire({
        title: 'Modal with background image and width, padding.',
        width: 700,
        padding: 100,
        background: '#fff url(../../assets/img/company.jpg) center',
        color: '#fff'
    })
});

// Alert With Input Type
$(document).on('click', '#sweet-subscribe', function(e) {
    Swal.fire({
      title: 'Submit email to subscribe',
      input: 'email',
      inputPlaceholder: 'Enter Email',
      showCancelButton: true,
      confirmButtonText: 'Submit',
      showLoaderOnConfirm: true,
      preConfirm: (email) => {
        return new Promise((resolve) => {
          setTimeout(() => {
            if (email === 'example@email.com') {
              Swal.showValidationError(
                'This email is already taken.'
              )
            }
            resolve()
          }, 2000)
        })
      },
      allowOutsideClick: false
    }).then((result) => {
      if (result.value) {
        Swal.fire({
          icon: 'success',
          title: 'Thank you for subscribing!',
          html: 'Submitted email: ' + result.value
        })
      }
    })
});

// Alert Redirect to Another Link
$(document).on('click', '#sweet-link', function(e) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You will be redirected to #url',
        icon: 'warning',
        confirmButtonText: 'Yes, visit link!',
        cancelButtonText: 'Cancel',
        showCancelButton: true,
        confirmButtonColor: '#1d4ed8',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = '#url';
        }
    })
});