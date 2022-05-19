function confirmModal(e, title = 'Opravdu?', text = 'Není cesty zpět!') {
  e.preventDefault();
  var url = e.currentTarget.getAttribute('href');
  swal({
    icon: 'warning',
    title: title,
    text: text,
    dangerMode: true,
    buttons: {
      cancel: {
        text: 'Zrušit',
        value: null,
        visible: true,
        className: '',
        closeModal: true,
      },
      confirm: {
        text: 'Trvale smazat',
        value: true,
        visible: true,
        className: '',
        closeModal: true,
      },
    },
  }).then((result) => {
    if (result) {
      window.location.href = url;
    }
    return result;
  });
}

// PASSWORD VISIBLE TOGGLE
function toggleVisibility(inputId, toggler) {
  var x = document.getElementById(inputId);
  if (x.type === 'password') {
    x.type = 'text';
    toggler.title = 'Skrýt heslo';
    toggler.classList.remove('fa-eye-slash');
    toggler.classList.add('fa-eye-slash');
  } else {
    x.type = 'password';
    toggler.title = 'Zobrazit heslo';
    toggler.classList.remove('fa-eye-slash');
    toggler.classList.add('fa-eye');
  }
}
