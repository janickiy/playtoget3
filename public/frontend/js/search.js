function elementsByType(selector, type, scope) {
    return (scope ? scope.find(selector) : $(selector)).filter(function () {
        return $(this).attr('data-type') === type;
    });
}

function fieldScope(element) {
    const field = $(element);
    const scope = field.closest('.select-container-text, .form-group');

    return scope.length ? scope : field.parent();
}

function select_place(val, type, element) {
    const text = $.trim(val);
    let url = '';
    if (type.match("^search_sport")) {
        url = '/ajax/search_sport_types?sport_types=';
    } else {
        url = '/ajax/search_city?city=';
    }

    const scope = element ? fieldScope(element) : null;
    const dropdown = elementsByType('.select-place', type, scope);

    if (text != '')
        $.ajax({
            type: 'GET',
            url: url + encodeURIComponent(text),
            success: function (data) {
                $('.select-place').hide();
                dropdown.html('');
                if (data != null && data.item != null) {
                    for (let i = 0; i < data.item.length; i++) {
                        $('<div>')
                            .addClass('place-item')
                            .attr('data-item', data.item[i].id)
                            .text(data.item[i].name)
                            .appendTo(dropdown);
                    }
                } else {
                    dropdown.hide();
                }
                dropdown.toggle(dropdown.children().length > 0);
            }
        })
    else {
        dropdown.hide();
    }
}

$(document).ready(function () {
    $(document).on('keyup', '.text-place', function () {
        const text = $(this).val();
        const type = $(this).attr('data-type');
        select_place(text, type, this);

    })

    $(document).mouseup(function (e) {
        const div = $('.select-place');
        if (!$('.text-place').is(e.target) && !div.is(e.target) && div.has(e.target).length === 0) {
            div.hide();
        }
    })
    $(document).on('focus', '.text-place', function () {
        const text = $(this).val();
        const type = $(this).attr('data-type');
        if (text == 'Нет') {
            $(this).val('');
        } else {
            select_place(text, type, this);
        }
    })

    $(document).on('click', '.place-item', function () {
        const text = $(this).text();
        const dropdown = $(this).closest('.select-place');
        const type = dropdown.attr('data-type');
        const item = $(this).attr('data-item');
        const scope = fieldScope(dropdown);

        elementsByType('.text-place', type, scope).val(text);
        elementsByType('.id_place', type, scope).val(item);
        $('.select-place').hide();

    })
})
