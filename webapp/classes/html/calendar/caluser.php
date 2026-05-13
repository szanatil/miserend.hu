<?php

namespace html\calendar;


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class caluser extends \Html\Calendar\CalendarApi {

    public function __construct($path) {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'OPTIONS':
                http_response_code(200);
                exit;

            case 'GET':
                $this->getUser();
                break;

            default:
                $this->sendJsonError('Method not allowed', 405);
        }
    }

    private function getUser(): void {
        global $user;

        // Visszatérés a szükséges adatokkal
        $this->content = json_encode([
            'uid' => $user->uid,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'name' => $user->name ?? null,
            'email' => $user->email ?? null,
        ]);
    }

}
