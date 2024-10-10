# Installation
## Install authenticator via composer
````
composer require wildcats1369/auth
````
## Add the middleware in app/Http/Kernel.php
````
protected $routeMiddleware = [
    // Other middleware
    'jwt.auth' => \wildcats1369\auth\Http\Middleware\JWTMiddleware::class,
    'sig.auth' => \wildcats1369\auth\Http\Middleware\SignatureMiddleware::class,
];
````

## Make Filament Login generate access token
### Publish Filament Config File 
````
php artisan vendor:publish --tag=filament-config
````
### Publish Migration
````
php artisan vendor:publish --tag=migration
````
### Update auth setting
`config/filament.php`
````
'auth' => [
    'pages' => [
        'login' => \wildcats1369\auth\Http\Controllers\Login::class,
    ],
],
````

# Usage
## Signature Generation (Signature Auth)
1. Sort the keys of the request Ascending and concat the values in that order
2. Make sure timestamp field is present and is not above 10 seconds ago.
3. Add the private_key at the end of the signature string
4. Apply SHA32 encryption and send as a header x-signature
5. add your public_key to your x-api-key header

Ex:
````
POST https://devtech.local/auth/login
Network
Request Headers
x-signature: 397fcdb1bab47bc1bdf3527fbba4306424108ddbb1c5a63093ead799ec05a38b
Content-Type: application/json
x-api-key: 456
User-Agent: PostmanRuntime/7.42.0
Accept: */*
Postman-Token: 5819a93d-a76b-45fc-81f6-bc403175aac3
Host: devtech.local
Accept-Encoding: gzip, deflate, br
Connection: keep-alive
Content-Length: 60
Request Body
{"username":"admin","password":"ABC","timestamp":1727840876}
Private Key: 123
Public Key: 456
````
### In this case the signature string will be 
Signature String: ABC1727840339admin123

x-signature: 397fcdb1bab47bc1bdf3527fbba4306424108ddbb1c5a63093ead799ec05a38b

x-api-key: 456


## Postman Script for testing Signature Auth Api
````
// Import the crypto-js library
const CryptoJS = require('crypto-js');

// Get the request data
let requestData = pm.request.body.raw;
let jsonData = JSON.parse(requestData);
jsonData['timestamp'] = Math.floor(Date.now() / 1000);
// Update the request body with the timestamp
pm.request.body.update(JSON.stringify(jsonData));

// Sort the data by key
let sortedKeys = Object.keys(jsonData).sort();
pm.globals.get("variable_key");
let sortedData = {};
sortedKeys.forEach(key => {
    sortedData[key] = jsonData[key];
});

// Create the signature string
let sigStr = '';
sortedKeys.forEach(key => {
    sigStr += sortedData[key];
});
sigStr += pm.environment.get('public_key');
sigStr += pm.environment.get('private_key'); // Assuming you have stored your private key in an environment variable
console.log(sigStr)
// Generate the hash
let hash = CryptoJS.SHA256(sigStr).toString(CryptoJS.enc.Hex);

// Set the hash to an environment variable
pm.environment.set('signature', hash);
````