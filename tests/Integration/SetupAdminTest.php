<?php

declare(strict_types=1);

namespace Tests\Integration;

use Api\Controller\SetupController;
use Application\Handler\Admin\SetupAdminHandler;
use Domain\Identity\Repository\UserRepositoryInterface;
use Infrastructure\Service\TotpService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Infrastructure\Container\ContainerBuilder;

class SetupAdminTest extends TestCase
{
    private $container;
    private $userRepository;
    private $setupController;

    protected function setUp(): void
    {
        // Reset database
        DatabaseTestHelper::resetDatabase();
        
        $this->container = ContainerBuilder::build();
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->setupController = new SetupController(
            $this->userRepository,
            $this->container->get(SetupAdminHandler::class),
            $this->container->get(TotpService::class)
        );
    }

    public function testSetupFlow(): void
    {
        // 1. Check Status - Should be required
        $request = $this->createRequest('GET', '/api/v1/setup/status');
        $response = $this->setupController->status();
        $body = json_decode((string)$response->getBody(), true);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($body['data']['is_setup_required']);

        // 2. Init Setup - Should return secret
        $request = $this->createRequest('POST', '/api/v1/setup/init');
        $response = $this->setupController->init();
        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('secret', $body['data']);
        $secret = $body['data']['secret'];

        // 3. Complete Setup
        // Generate valid OTP code
        // We can reuse the TotpService or implement a simple generator here since the logic is standard
        $totpService = new TotpService();
        $code = $this->generateCode($secret); 

        $payload = [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'SecurePass123!',
            'otp_secret' => $secret,
            'otp_code' => $code
        ];

        $request = $this->createRequest('POST', '/api/v1/setup/complete')
            ->withParsedBody($payload);
        
        $response = $this->setupController->complete($request);
        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('admin', $body['data']['username']);

        // 4. Verify Admin Exists
        $this->assertTrue($this->userRepository->hasAnyAdmin());

        // 5. Check Status - Should NOT be required
        $request = $this->createRequest('GET', '/api/v1/setup/status');
        $response = $this->setupController->status();
        $body = json_decode((string)$response->getBody(), true);
        
        $this->assertFalse($body['data']['is_setup_required']);

        // 6. Try Init Again - Should fail
        $request = $this->createRequest('POST', '/api/v1/setup/init');
        $response = $this->setupController->init();
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    private function createRequest(string $method, string $uriStr): ServerRequestInterface
    {
        $uri = new \Api\Request\Uri();
        $uri = $uri->withPath($uriStr);
        
        $request = new \Api\Request\ServerRequest();
        return $request->withMethod($method)->withUri($uri);
    }

    private function generateCode(string $secret): string
    {
        // Simple TOTP generation for test
        $timeSlice = floor(time() / 30);
        $secretKey = $this->base32Decode($secret);
        $timestamp = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $timestamp, $secretKey, true);
        $offset = ord($hash[19]) & 0xf;
        $otp = (
            ((ord($hash[$offset+0]) & 0x7f) << 24 ) |
            ((ord($hash[$offset+1]) & 0xff) << 16 ) |
            ((ord($hash[$offset+2]) & 0xff) << 8 ) |
            (ord($hash[$offset+3]) & 0xff)
        ) % 1000000;
        
        return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        if (!preg_match('/^[A-Z2-7]+$/', $base32)) {
            throw new \InvalidArgumentException('Invalid Base32 characters');
        }

        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        $buffer = 0;
        $bufferSize = 0;

        for ($i = 0; $i < strlen($base32); $i++) {
            $buffer = ($buffer << 5) | strpos($map, $base32[$i]);
            $bufferSize += 5;

            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $binary .= chr(($buffer >> $bufferSize) & 0xFF);
            }
        }

        return $binary;
    }
}
