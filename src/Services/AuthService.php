<?php
namespace wildcats1369\auth\Services;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Parser;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Http\Request;

class AuthService
{
    private $config;

    public function __construct()
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(config('auth.passphrase')),
        );

    }

    public function generateTokens($user)
    {
        $now = new DateTimeImmutable();
        $salt = bin2hex(random_bytes(32));

        // Generate Access Token
        $accessToken = $this->config->builder()
            ->issuedBy('bss-system')
            ->permittedFor($user->name)
            ->identifiedBy($salt, true)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+5 minutes'))
            ->withClaim('uid', $user->id)
            ->getToken($this->config->signer(), $this->config->signingKey());

        // Generate Refresh Token
        $refreshToken = $this->config->builder()
            ->issuedBy('bss-system')
            ->permittedFor($user->name)
            ->identifiedBy($salt, true)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+30 days'))
            ->withClaim('uid', $user->id)
            ->getToken($this->config->signer(), $this->config->signingKey());

        Log::info('Generating tokens');

        return [
            'access_token' => $accessToken->toString(),
            'refresh_token' => $refreshToken->toString(),
        ];
    }

    public function verifyToken($token)
    {
        $token = $this->config->parser()->parse($token);
        $constraints = $this->config->validationConstraints();

        if (! $this->config->validator()->validate($token, ...$constraints)) {
            return false;
        }

        return true;
    }

    public function refreshToken($expiredToken)
    {
        // Parse the expired token
        $token = $this->config->parser()->parse($expiredToken);

        // Check if the token is expired
        $now = new DateTimeImmutable();
        $userData = $token->claims()->get('uid');
        $user = User::find($userData);
        if ($token->isExpired($now)) {
            // Extract the user data from the expired token

            // Generate a new token
            $newToken = $this->generateTokens($user);

            return $newToken;
        }
        // return $this->generateToken($user);
        //If the token is not expired, validate it
        $constraints = $this->config->validationConstraints();
        if (! $this->config->validator()->validate($token, ...$constraints)) {
            // Token is invalid or tampered with
            var_dump('Token is invalid or tampered with');

            throw new Exception('Invalid token');
        }

        return $this->generateTokens($user);
    }

    public function generateSignature(Request $request)
    {

        $apiKey = $request->header('x-api-key');
        if (! $apiKey) {
            return response()->json(['error' => 'Invalid Request'], 401);
        }

        // Validate the API key against the Users model
        $user = User::where('public_key', $apiKey)->first();
        if (! $user) {
            return response()->json(['error' => 'Invalid Request'], 401);
        }

        // Convert request data to an array
        $data = $request->all();
        // Check if timestamp exists
        if (! isset($data['timestamp'])) {
            return response()->json(['error' => 'Invalid Request'], 401);
        }

        // Validate the timestamp
        $currentTimestamp = time();
        if (($currentTimestamp - $data['timestamp']) > 10) {
            return response()->json(['error' => 'Signature is expired.'], 401);
        }

        // Sort the array by key
        ksort($data);
        $sig_str = '';

        foreach ($data as $key => $value) {
            $sig_str .= $value;
        }

        $sig_str .= $user->public_key;
        $sig_str .= $user->private_key;
        $sig_str = mb_convert_encoding($sig_str, 'UTF-8');
        $sig = hash('sha256', $sig_str);
        return $sig;
    }
}