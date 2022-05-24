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

/*
 * Searching availible students from db by fname/lastname
 * Insert into div#studentList
 */
function showStudents(str) {
  // var prevStudents = [...document.getElementById('studentList').children];
  // prevStudents.forEach((student) => {
  //   if (student.tagName == 'SPAN') {
  //     if (student.children[0].checked == false) {
  //       console.log(student.children[0].checked);
  //       student.remove();
  //     }
  //   } else if (student.tagName == 'DIV') student.remove();
  // });

  if (str.length == 0) {
    document.getElementById('studentList').innerHTML = '';
    return;
  } else {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        document.getElementById('studentList').innerHTML = this.responseText;
      }
    };
    str = str.substring(str.indexOf(' ') + 1);
    xmlhttp.open('POST', 'studentsearch?do=search&string=' + str, true);
    xmlhttp.setRequestHeader(
      'Content-type',
      'application/x-www-form-urlencoded'
    );
    xmlhttp.send();
  }
}
