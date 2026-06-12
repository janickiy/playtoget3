'use strict';

var arraydata = [];

function getmenus() {
  arraydata = [];
  $('#spinsavemenu').show();

  let cont = 0;
  const menuItems = $('#menu-to-edit li');

  menuItems.each(function() {
    let dept = 0;
    for (let i = 0; i < menuItems.length; i++) {
      const n = $(this)
        .attr('class')
        .indexOf('menu-item-depth-' + i);
      if (n !== -1) {
        dept = i;
      }
    }
    const textoiner = $(this)
      .find('.item-edit')
      .text();
    const id = this.id.split('-');
    const textoexplotado = textoiner.split('|');
    let padre = 0;
    if (
      !!textoexplotado[textoexplotado.length - 2] &&
      textoexplotado[textoexplotado.length - 2] !== id[2]
    ) {
      padre = textoexplotado[textoexplotado.length - 2];
    }
    arraydata.push({
      depth: dept,
      id: id[2],
      parent: padre,
      sort: cont
    });
    cont++;
  });
  updateitem();
  actualizarmenu();
}

function addcustommenu() {
  $('#spincustomu').show();

  $.ajax({
    data: {
      labelmenu: $('#custom-menu-item-name').val(),
      linkmenu: $('#custom-menu-item-url').val(),
      rolemenu: $('#custom-menu-item-role').val(),
      idmenu: $('#idmenu').val()
    },

    url: addcustommenur,
    type: 'POST',
    success: function() {
      window.location.reload();
    },
    complete: function() {
      $('#spincustomu').hide();
    }
  });
}

function updateitem(id = 0) {
  let data;

  if (id) {
    const label = $('#idlabelmenu_' + id).val();
    const clases = $('#clases_menu_' + id).val();
    const url = $('#url_menu_' + id).val();
    let role_id = 0;
    if ($('#role_menu_' + id).length) {
      role_id = $('#role_menu_' + id).val();
    }

    data = {
      label: label,
      clases: clases,
      url: url,
      role_id: role_id,
      id: id
    };
  } else {
    const arr_data = [];
    $('.menu-item-settings').each(function() {
      const id = $(this)
        .find('.edit-menu-item-id')
        .val();
      const label = $(this)
        .find('.edit-menu-item-title')
        .val();
      const clases = $(this)
        .find('.edit-menu-item-classes')
        .val();
      const url = $(this)
        .find('.edit-menu-item-url')
        .val();
      const role = $(this)
        .find('.edit-menu-item-role')
        .val();
      arr_data.push({
        id: id,
        label: label,
        class: clases,
        link: url,
        role_id: role
      });
    });

    data = { arraydata: arr_data };
  }
  $.ajax({
    data: data,
    url: updateitemr,
    type: 'POST',
    beforeSend: function() {
      if (id) {
        $('#spincustomu2').show();
      }
    },
    complete: function() {
      if (id) {
        $('#spincustomu2').hide();
      }
    }
  });
}

function actualizarmenu() {
  $.ajax({
    dataType: 'json',
    data: {
      arraydata: arraydata,
      menuname: $('#menu-name').val(),
      idmenu: $('#idmenu').val()
    },

    url: generatemenucontrolr,
    type: 'POST',
    beforeSend: function() {
      $('#spincustomu2').show();
    },
    complete: function() {
      $('#spincustomu2').hide();
    }
  });
}

function deleteitem(id) {
  $.ajax({
    dataType: 'json',
    data: {
      id: id
    },

    url: deleteitemmenur,
    type: 'POST'
  });
}

function deletemenu() {
  const translations = window.menuTranslations || {};
  const shouldDelete = confirm(translations.confirmDeleteMenu || '');
  if (shouldDelete === true) {
    $.ajax({
      dataType: 'json',

      data: {
        id: $('#idmenu').val()
      },

      url: deletemenugr,
      type: 'POST',
      beforeSend: function() {
        $('#spincustomu2').show();
      },
      success: function(response) {
        if (!response.error) {
          alert(response.resp);
          window.location = menuwr;
        } else {
          alert(response.resp);
        }
      },
      complete: function() {
        $('#spincustomu2').hide();
      }
    });
  } else {
    return false;
  }
}

function createnewmenu() {
  if ($('#menu-name').val()) {
    $.ajax({
      dataType: 'json',

      data: {
        menuname: $('#menu-name').val()
      },

      url: createnewmenur,
      type: 'POST',
      success: function(response) {
        window.location = menuwr + '?menu=' + response.resp;
      }
    });
  } else {
    const translations = window.menuTranslations || {};
    alert(translations.enterMenuName || '');
    $('#menu-name').trigger('focus');
    return false;
  }
}

function insertParam(key, value) {
  const params = new URLSearchParams(window.location.search);
  params.set(key, value);
  window.location.search = params.toString();
}

wpNavMenu.registerChange = function() {
  getmenus();
};
