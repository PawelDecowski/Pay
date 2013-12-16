<?php
require 'vendor/autoload.php';
use RedBean_Facade as R;

$settings = include 'settings.php';

session_cache_limiter(false);
session_start();

R::setup('mysql:host=' . $settings->db_host . ';dbname=' . $settings->db_name, $settings->db_user, $settings->db_pass);

$app = new \Slim\Slim(array(
                        'debug' => $settings->debug,
                        'view' => new \Slim\Views\Twig(),
                        'templates.path' => $settings->templates_path
                    ));

$app->get('/', function() use ($app) {
    $app->render('index.html');
});

$app->get('/success', function() use ($app) {
    $app->render('success.html');
});

$app->post('/booking', function () use ($app, $settings) {
    if ($app->request->post('username') != $settings->username
        || $app->request->post('password') != $settings->password) {
        $app->response->setStatus(401);

        return;
    }

    $booking = R::dispense('booking');

    $date = new DateTime($app->request->post('date'));

    $booking->number = strtoupper(base_convert($date->format('Hidm'), 10, 36));
    $booking->date = $app->request->post('date');
    $booking->first_name = $app->request->post('first_name');
    $booking->job_type = $app->request->post('job_type');
    $booking->amount = $app->request->post('amount');
    $booking->amount_paid = 0;

    if ($id = R::store($booking)) {
        echo "Done. Booking number: {$booking->number}";
    };
});

$app->get('/:booking_number', function ($booking_number) use ($app, $settings) {
    if (!preg_match('/^[A-Z0-9]+$/', $booking_number)) {
        $app->redirect('/' . strtoupper($booking_number));
    }

    $booking = R::findOne('booking', ' number = ? ORDER BY date', array($booking_number));

    if (!$booking) {
        $app->render('not_found.html', compact('booking_number'), 404);
        return;
    }

    $app->render('booking.html', compact('booking', 'settings'));
})->conditions(array('booking_number' => '[A-Za-z0-9]+'));;

$app->post('/:booking_number', function ($booking_number) use ($app, $settings) {
    $booking = R::findOne('booking', ' number = ? ORDER BY date', array($booking_number));

    if (!$booking) {
        $app->redirect('/' . $booking_number);
    }

    if (!$app->request->post('terms')) {
        $app->flash('error', 'You have to agree to the Terms & Conditions');
        
        $app->redirect('/' . $booking_number);
    }

    Stripe::setApiKey($settings->stripe_api_key);

    $token = $app->request->post('stripeToken');

    try {
        $charge = Stripe_Charge::create(array(
            "amount" => $booking->amount * 100,
            "currency" => "gbp",
            "card" => $token,
            "description" => $booking->first_name . ' ' . $booking->job_type)
        );
    } catch(Stripe_CardError $e) {
        $body = $e->getJsonBody();

        $app->flash('error', $body['error']['message']);

        $app->redirect('/' . $booking_number);
    }

    $booking->amount_paid = $booking->amount;
    $booking->terms = $app->request->get('terms');

    R::store($booking);

    $app->flash('booking', $booking);

    $app->redirect('/success');
});

$app->notFound(function () use ($app) {
    $app->render('404.html');
});

$app->run();