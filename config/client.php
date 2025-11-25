<?php

return [
    'document_request_base_url' => rtrim(
        env('FRONTEND_DOCUMENT_REQUEST_URL', env('FRONTEND_URL', env('APP_URL'))),
        '/'
    ),
];
