<?php

// Hardcode Firebase configuration to avoid .env influence for FCM.
// Falls back to embedded service account JSON if the file is missing (e.g., in prod).
$credentialsPath = storage_path('app/firebase/service-account.json');

$embeddedServiceAccount = [
    'type' => 'service_account',
    
    'project_id' => 'snoutiqapp',
    'private_key_id' => 'c50422ed11c3555e6bc682fe13fd08f9b3aae853',
    'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDcJdcys0rajfhn\n7ORX4nsKJ2XmrS8GJP/VUchi+RzhtZE9UXi3F+QCOmIB3pn62QEFNLxiD/Mb8R2G\nFWSoQcrbTrt/y8Emk5OwymDJS2/1OFuvBaVrTPY/PHEMTlN53za8H2GHjp9cebnS\nvUf4f6WP+9Af/0Gw+GURTJwhUuuclJxkFho68mlJ6HTH5YIEH89EpZQfOY4Q7qnr\nCwGXSsGL0YSqdCircYbmGshjgTpy8ZtnJT+ckDk0xECAjpJjCbHBACbXj6TZamqx\nhNxkA82Iu4P0HEVGtKfld6Bw0n7zB9DibjRh8KbsUURk5LcZO9H43fZ3qvoyynZO\nnnn7ZKGLAgMBAAECggEAZKuFMfEdLL45NsT0UmAF5cnIZmkRlNy8hL8GRIQoOeq+\nhOzJp+hr4rzx7mrtvPEY71mDP9CNWlyzJIqJ4gtcCVeClFkHdW2M3w5cnhA6HUft\nXSbW0y95d8da5sa2k4eITxSGk+ebZj1fxUe7Lp6ohiQfs1IVpyifhv6icvapOYs+\nImlFgef3AVvMp0f53+oZOtfCVcQzVwx3gOnKp4xlcXmdrTvG5kvw75hvLzK5v6GE\nfcAgZ1FBWjSJHdGXdKagSVj+JbIeTK2NxJfGuDNtaPz1MQ0Y1DA8WLeXlslcGHn7\nhZAwSHsIOib2AdCkkJePVBl3k8H6GyO7hLwIG/nCYQKBgQD+19EAYTssxUpIy66q\nm9NCRW2KNtxH7dEYpS2ht56MnBCrreoX6Dd2ppUdOLMNHKKGD/j2GZhpCsb5+lzs\nuNfl8vTirwlp0d5ijwMuPBclkbqNr/KiOVIzZM+qtSCXQGREMT9LPWO7ZAt++h5L\n6SMHhpw6WW5Lkuc5pxKw4cxX8QKBgQDdJbNjtobNmGc2IfK3ZHAty4n3DPaCyM0G\n+wqb41Y64grqVj7GE+87bsYyruTBJcu0MmBrLCX+sHGkgIQRbj60L55kZimMVeNu\nbQhCZZSbWmN+h92G/SU4ZnvCmLlJWDpchtnMRenYGxolHeg73u/MM0tUmAA0bY8B\nb6z0wkctOwKBgDlSJ9OTCzFdywCmt8nuNM2COkpNXqzbJB4MAUCPwZzU+bbz7mSk\nOd15SK8C8tsvJqtK6m/IgAyYfPr7Qm2Igh9Zz5UxU8e2ifPXQRrkLzynE7QM8GFm\nzUN8GG3IQeVjeWoRPbBZxZX/wco0zh26+cMWlwtU1Ecxasr/9mdM0p6xAoGAOsWP\nNDvI/ZC4NUm2YIi/y1vhcZevV7iXzHghLKaxPvrd3cNH8YpQtOHOqJ+UScSKq3wL\n5c+Y1WP8/7Pr5VoALhDNrm78McCNrcYqQMMQSG0wLetbs0lJgAC0eVXvQA/Dit2H\numMGL5mcTCrzkh3Aauti5Lt0qnpHXCFavL14/wsCgYEA8vac9hSsR4mz/usbySxT\nL5SGOAr3H05rPLM7R7FTpjklLmLODX6q6RvCBD2VIDynIQnfLtvDArM/49Auw8+r\nkMceIZmqwLPfxDkRRv3euqgnCO0L5SZS55QKZWf2qte9kUxxwC4j9B2wcD3ONtUJ\nd8HsX8m8QGJ5abSMha3OZBI=\n-----END PRIVATE KEY-----\n",
    'client_email' => 'firebase-adminsdk-fbsvc@snoutiqapp.iam.gserviceaccount.com',
    'client_id' => '109877641802932135853',
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
    'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc@snoutiqapp.iam.gserviceaccount.com',
    'universe_domain' => 'googleapis.com',
];

$credentialsValue = file_exists($credentialsPath) ? ['file' => $credentialsPath] : $embeddedServiceAccount;

return [
    'default' => 'app',

    'projects' => [
        'app' => [
            'credentials' => $credentialsValue,
            'project_id' => 'snoutiqapp',
            'database' => [
                'url' => 'https://snoutiqapp-default-rtdb.firebaseio.com',
            ],
            'storage' => [
                'default_bucket' => 'snoutiqapp.firebasestorage.app',
            ],
        ],
    ],
];
