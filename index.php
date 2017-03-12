<?php

require 'vendor/autoload.php';
date_default_timezone_set("Europe/Zurich");

//Instantiate a new object
$app = new \Slim\Slim(
    array (
    //We are passing an option to send the view class using an array
        //we are overriding the view class and using's twig
    'view' => new \Slim\Views\Twig()
    )
);

//Add middleware to our app object, session cookie
$app->add(new \Slim\Middleware\SessionCookie());

//this sets the view options up
$view = $app->view();
$view->parserOptions = array(
    'debug' => true
);

//This gives you extra helpers, these include urlFor, siteUrl, baseUrl and currentUrl.
$view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);

//Home route
//Use the object get method to do function when the url is set as such
//We have to pass in the object app into our route function using "use($app)"
$app->get('/', function() use($app){
    //point the app object to the method render for the view side of the MVC.
    //this automatically looks inside the templates folder for the file given
    $app->render('about.twig');
})->name("home"); //this allows us to use url for

//this is our contact page
$app->get('/contact', function()use($app){
    $app->render('contact.twig');
})->name("contact"); //this allows us to use url for;

//this is for our contact form
$app->post('/contact', function()use($app){
    //get the form data using post
    $name = $app->request->post("name");
    $email = $app->request->post("email");
    $message = $app->request->post("message");

    //if all are not empty
    if(!empty($name) && !empty($email) && !empty($message)) {
        //then sanitize information
        $cleanName = filter_var($name, FILTER_SANITIZE_STRING);
        $cleanEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
        $cleanMsg = filter_var($message, FILTER_SANITIZE_STRING);


    } else {
        //Send message to user that message failed to send
        //access by using {{ flash["error" }} where error is key
        //this last as long as a session
        $app->flash('fail','All fields are required');

        //Message the user there was a problem
        $app->redirect("/portfolio/contact");
    }

    //set up Swift mail
    $transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
    $mailer = \Swift_Mailer::newInstance($transport);

    //compose the message
    $message = \Swift_Message::newInstance();
    $message->setSubject("Email from our website");
    $message->setFrom(array(
        $cleanEmail => $cleanName
        ));
    $message->setTo(array('yourmail@mail.com'));
    $message->setBody($cleanMsg);

    //send the message
    //result will be 0 = No Message Sent or 1 and more = Message Sent to this many addresses
    $result = $mailer->send($message);

    //do the correct redirection
    if($result > 0) {
        //send a success message and thank you
        $app->flash('success','Thank you for the email');
        $app->redirect('/');
    } else {

        //log that there was an error
        $app->flash('fail','Something went wrong, please try again later');
        $app->redirect('/portfolio/contact');
    }

});

$app->run();