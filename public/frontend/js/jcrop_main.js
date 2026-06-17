$(function () {
    // Example 1.
    $('#cropbox1').Jcrop({ // Bind JCrop to the image with id=cropbox1.
        aspectRatio: 0,
        onChange: updateCoords,
        onSelect: updateCoords
    });

    // Example 2.
    const api = $.Jcrop('#cropbox2', { // Bind JCrop to the image with id=cropbox2.
        setSelect: [100, 100, 200, 200]
    });
    let i;

    // Handler that cancels the action.
    function nothing(e) {
        e.stopPropagation();
        e.preventDefault();
        return false;
    }

    // Event handler for starting animation.
    function anim_handler(ac) {
        return function (e) {
            api.animateTo(ac);
            return nothing(e);
        };
    }

    // Set area coordinates for animation.
    const ac = {
        anim1: [0, 0, 40, 600],
        anim2: [115, 100, 210, 215],
        anim3: [80, 10, 760, 585],
        anim4: [105, 215, 665, 575],
        anim5: [495, 150, 570, 235]
    };

    // Bind the matching event handler.
    for (i in ac) jQuery('#' + i).on('click', anim_handler(ac[i]));

    // Example 3.
    $('#cropbox3').Jcrop({ // Bind JCrop to the image with id=cropbox3.
        setSelect: [20, 130, 480, 230],
        addClass: 'jcrop_custom',
        bgColor: 'blue',
        bgOpacity: .5,
        sideHandles: false,
        minSize: [50, 50]
    });
});

function updateCoords(c) {
    $('#x').val(c.x);
    $('#y').val(c.y);
    $('#w').val(c.w);
    $('#h').val(c.h);

    $('#x2').val(c.x2);
    $('#y2').val(c.y2);


    const rx = 200 / c.w; // 200 is the preview window size.
    const ry = 200 / c.h;

    $('#preview').css({
        width: Math.round(rx * 800) + 'px',
        height: Math.round(ry * 600) + 'px',
        marginLeft: '-' + Math.round(rx * c.x) + 'px',
        marginTop: '-' + Math.round(ry * c.y) + 'px'
    });
};

jQuery(window).on('load', function () {
    $("#accordion").accordion({autoHeight: false, navigation: true});
});

function checkCoords() {
    if (parseInt($('#w').val())) return true;
    alert('Please select an area to crop.');
    return false;
};
