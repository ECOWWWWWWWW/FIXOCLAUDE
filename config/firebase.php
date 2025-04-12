<?php

return [
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS'),
    ],
    'database_url' => env('FIREBASE_DATABASE_URL', null),
    'dynamic_links' => [
        'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN', null)
    ],
    'default_auth_domain' => env('FIREBASE_DEFAULT_AUTH_DOMAIN', null),
    'default_storage_bucket' => env('FIREBASE_DEFAULT_STORAGE_BUCKET', null),
    'project_id' => env('FIREBASE_PROJECT_ID', null),
    
    // Add to your .env file
    // FIREBASE_CREDENTIALS=resources/credentials/firebase_credentials.json
    // FIREBASE_DATABASE_URL=https://your-project-id.firebaseio.com
    // FIREBASE_PROJECT_ID=your-project-id
];