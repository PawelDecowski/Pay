$(function() {
    if ($('body').width() > 320) {
        $('input[type=number]').prop('type', 'text');
    }

    $('#lookup form').submit(function(event) {
        event.preventDefault();
        window.location = '/' + $('#booking_number').val().toUpperCase();
    });

    function show_error(text)
    {
        var $form = $('form');

        if ($form.find('.payment-errors').length === 0) {
            $form.find('.submit').prepend('<strong class="payment-errors"></strong>');
            $('.payment-errors').hide().slideDown();
        }

        $form.find('.payment-errors').text(text);
    }

    $('#payment form').submit(function(event) {
        var $form = $(this);

        event.preventDefault();

        if (!$('#terms').prop('checked')) {
            show_error('You have to agree to the Terms and Conditions');
            return;
        }

        $('body').css('cursor', 'wait');
        $form.find('button').prop('disabled', true);

        Stripe.card.createToken($form, stripeResponseHandler);
    });

    var stripeResponseHandler = function(status, response) {
        var $form = $('form');

        if (response.error) {
            show_error(response.error.message);
            $('body').css('cursor', '');
            $form.find('button').prop('disabled', false);
        } else {
            var token = response.id;
            $form.append($('<input type="hidden" name="stripeToken" />').val(token));
            $form.get(0).submit();
        }
    };
});