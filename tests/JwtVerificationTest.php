<?php

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class JwtVerificationTest extends TestCase
{
  private const NOW = 1700000000;
  private const CLIENT_ID = 'test-client.apps.googleusercontent.com';

  private static OpenSSLAsymmetricKey $privateKey;
  private static array $certificate;
  private ?int $previousTimestamp;
  private int $previousLeeway;

  public static function setUpBeforeClass(): void
  {
    self::$privateKey = self::createKey();
    self::$certificate = self::certificate(self::$privateKey, 'google-key');
  }

  protected function setUp(): void
  {
    $this->previousTimestamp = JWT::$timestamp;
    $this->previousLeeway = JWT::$leeway;
    JWT::$timestamp = self::NOW;
  }

  protected function tearDown(): void
  {
    JWT::$timestamp = $this->previousTimestamp;
    JWT::$leeway = $this->previousLeeway;
  }

  /** @dataProvider validIssuers */
  public function testVerifiesGoogleIdTokens(string $issuer): void
  {
    $claims = $this->claims(['iss' => $issuer]);
    $token = JWT::encode($claims, self::$privateKey, 'RS256', 'google-key');

    self::assertSame($claims, $this->client()->verifyIdToken($token));
  }

  public static function validIssuers(): array
  {
    return [
      ['accounts.google.com'],
      ['https://accounts.google.com'],
    ];
  }

  /** @dataProvider invalidClaims */
  public function testRejectsInvalidClaims(array $overrides): void
  {
    $token = JWT::encode($this->claims($overrides), self::$privateKey, 'RS256', 'google-key');

    self::assertFalse($this->client()->verifyIdToken($token));
  }

  public static function invalidClaims(): array
  {
    return [
      'wrong audience' => [['aud' => 'another-client']],
      'wrong issuer' => [['iss' => 'https://example.test']],
      'expired token' => [['exp' => self::NOW - 60]],
    ];
  }

  public function testRejectsInvalidSignatures(): void
  {
    $token = JWT::encode($this->claims(), self::createKey(), 'RS256', 'google-key');

    self::assertFalse($this->client()->verifyIdToken($token));
  }

  public function testTriesTheNextRsaKeyDuringRotation(): void
  {
    $certificates = [
      self::certificate(self::createKey(), 'previous-key'),
      self::$certificate,
    ];
    $token = JWT::encode($this->claims(), self::$privateKey, 'RS256', 'google-key');

    self::assertSame($this->claims(), $this->client($certificates)->verifyIdToken($token));
  }

  public function testCachesGoogleCertificates(): void
  {
    $client = $this->client();
    $token = JWT::encode($this->claims(), self::$privateKey, 'RS256', 'google-key');

    self::assertSame($this->claims(), $client->verifyIdToken($token));
    self::assertSame($this->claims(), $client->verifyIdToken($token));
  }

  public function testRejectsAnHmacTokenUsingTheRsaPublicKeyAsItsSecret(): void
  {
    $details = openssl_pkey_get_details(self::$privateKey);
    self::assertIsArray($details);
    $token = JWT::encode($this->claims(), $details['key'], 'HS256', 'google-key');

    $this->expectException(UnexpectedValueException::class);
    $this->client()->verifyIdToken($token);
  }

  public function testRejectsRsaKeysBelow2048Bits(): void
  {
    $weakKey = self::createKey(1024);
    $header = JWT::urlsafeB64Encode(JWT::jsonEncode(['alg' => 'RS256', 'kid' => 'weak-key']));
    $payload = JWT::urlsafeB64Encode(JWT::jsonEncode($this->claims()));
    $input = $header . '.' . $payload;
    self::assertTrue(openssl_sign($input, $signature, $weakKey, OPENSSL_ALGO_SHA256));
    $token = $input . '.' . JWT::urlsafeB64Encode($signature);

    self::assertFalse($this->client([self::certificate($weakKey, 'weak-key')])->verifyIdToken($token));
  }

  public function testRejectsTokensThatAreNotYetValid(): void
  {
    $token = JWT::encode(
        $this->claims(['nbf' => self::NOW + 60]),
        self::$privateKey,
        'RS256',
        'google-key'
    );

    $this->expectException(BeforeValidException::class);
    $this->client()->verifyIdToken($token);
  }

  public function testRejectsEmptyTokens(): void
  {
    $this->expectException(LogicException::class);
    $this->client()->verifyIdToken('');
  }

  private function claims(array $overrides = []): array
  {
    return array_replace([
      'iss' => 'https://accounts.google.com',
      'aud' => self::CLIENT_ID,
      'sub' => 'test-user',
      'iat' => self::NOW,
      'exp' => self::NOW + 3600,
    ], $overrides);
  }

  private function client(?array $certificates = null): Google_Client
  {
    $mock = new MockHandler([
      new Response(200, [], json_encode(['keys' => $certificates ?? [self::$certificate]], JSON_THROW_ON_ERROR)),
    ]);
    $client = new Google_Client(['client_id' => self::CLIENT_ID]);
    $client->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

    return $client;
  }

  private static function createKey(int $bits = 2048): OpenSSLAsymmetricKey
  {
    $key = openssl_pkey_new(['private_key_bits' => $bits, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    if ($key === false) {
      throw new RuntimeException('Could not generate the test RSA key: ' . openssl_error_string());
    }

    return $key;
  }

  private static function certificate(OpenSSLAsymmetricKey $key, string $id): array
  {
    $details = openssl_pkey_get_details($key);
    if ($details === false) {
      throw new RuntimeException('Could not read the test RSA key: ' . openssl_error_string());
    }

    return [
      'kty' => 'RSA',
      'alg' => 'RS256',
      'kid' => $id,
      'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
      'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
    ];
  }
}
