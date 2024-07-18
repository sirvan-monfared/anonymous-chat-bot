<?php
// ngrok http http://cryptocurrency.test/
// https://api.telegram.org/bot7341111656:AAF9VoAqXtw6t7FHBPK5qHF3y3beIsB4f90/setWebhook?url=https://f21d-37-98-114-49.ngrok-free.app
use App\Http\Controllers\AnonymousChatBotController;

$router->post('/', [AnonymousChatBotController::class, 'inbounce'], 'telegram.inbounce');
$router->get('/', [AnonymousChatBotController::class, 'inbounce']);
