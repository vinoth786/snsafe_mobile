function checkInput() {
    $('input,textarea').each(function () {
        if ($(this).val() === '') {
            $('label').removeClass('active');
        } else {
            $('label').addClass('active');
        }
    });
}


