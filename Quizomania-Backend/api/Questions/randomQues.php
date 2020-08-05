<?php

    // This module generates random questions.

    header('Content-Type: application/json');
    header('Access-Control-Allow-Methods: GET');

    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../models/Question.php';
    require_once __DIR__ . '/../../models/Subject.php';
    require_once __DIR__ . '/../../auth/Validation/validate.php';

    // Checking the request method type.
    if($_SERVER['REQUEST_METHOD'] !== 'GET')
    {
        // ! Invalid HTTP Request Method
        header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed", true, 405);
        throwError($error = '405 Method not Allowed');
    }

    /**
     *  Validating the token.
     * This will only return if the token is successfully verified.
     * The returned value are the details about the verified user as associative array.
     */
    $tokenUser = validateAccessToken();

    // Creating a new database connection...
    $database = new Database();
    $dbConn = $database->connect();

    // Creating new Question object
    $question = new Question($dbConn);

    $data = json_decode(file_get_contents('php://input'), true);

    // Checking the received data.

    // Validating the keys.
    validateKeys($data, ['Count', 'SubID']);

    // Validating the received data.
    validateData('Count', $data['Count'], 'NUMERIC');
    validateData('SubID', $data['SubID'], 'NUMERIC');

    // Checking whether subject with this id exist.
    $subject = new Subject($dbConn);

    $count = (int)$dbConn->real_escape_string($data['Count']);
    $subject->id = (int)$dbConn->real_escape_string($data['SubID']);

    $chkSub = $subject->checkID();

    if(!$chkSub)
    {
        throwError($error = 'Invalid Subject ID');
    }

    $question->subID = $subject->id;

    // Getting all questions of a particular subject.
    $result = $question->getQuestions();

    if($result)
    {
        // Question Found

        // Checking if there are enough questions.
        $numOfRows = $result->num_rows;

        if($count > $numOfRows)
        {
            // There are not enough questions to provide.
            throwError($error = 'Not enough questions', $message = 'Try less ammount of questions.');
        }

        $data = array();
        $random = array();
        for ($i=0; $i < $numOfRows; $i++)
            array_push($data, $result->fetch_assoc());

        // Generating random keys.
        $randomQNos = array_rand($data, $count);

        // Pushing random questions.
        foreach ($randomQNos as $x)
            array_push($random, $data[$x]);

        returnResponse($message = 'Random Questions Generated.', $data = $random);
    }
    else
    {
        // Question with this id could not be found.
        throwError($message = 'No Questions Found!');
    }




?>