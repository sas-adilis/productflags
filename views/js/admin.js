jscolor.presets.default = {
    format:'hexa'
};

$(document).ready(function () {
    $('#conditions').on('click', '[data-toggle="collapse"]', function (e) {
        e.preventDefault();

        // Gestion des classes actives
        var $clicked = $(this);
        var activeClass = 'is-active';
        $('#conditions [data-toggle="collapse"]').not($clicked).removeClass(activeClass);
        $clicked.toggleClass(activeClass);

        var target = $(this).attr('data-target');
        if (!target) {
            return;
        }

        // Option accordéon : fermer les autres
        var $parent = $($(this).data('parent'));
        if ($parent.length) {
            $parent.find('.collapse.in').not(target).collapse('hide');
        }

        $(target).collapse('toggle');
    });
});