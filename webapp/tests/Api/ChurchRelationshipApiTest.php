<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ChurchRelationshipApiTest extends TestCase {

    private string $baseUrl = 'http://miserend:8000';

    protected function setUp(): void {
        parent::setUp();
        $_REQUEST = [];
    }

    protected function tearDown(): void {
        $_REQUEST = [];
        parent::tearDown();
    }

    private function apiRequest(string $path, array $payload): array {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError   = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            $this->fail('Curl error: ' . $curlError);
        }

        $this->assertEquals(200, $httpCode, "Expected HTTP 200, got {$httpCode}");

        $response = json_decode($rawResponse, true);
        $this->assertIsArray($response, 'Response is not valid JSON. Raw: ' . substr((string) $rawResponse, 0, 500));

        return $response;
    }

    /**
     * Az endpoint letezik es JSON-t ad vissza.
     * Az id=1 biztosan letezik a fixture adatokban.
     */
    public function testRelationshipsEndpointReturnsJson(): void {
        $response = $this->apiRequest('/api/v4/churchrelationships', ['id' => 1]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals(0, $response['error']);
    }

    /**
     * Az endpoint ancestors es descendants kulcsokat tartalmaz.
     */
    public function testRelationshipsEndpointReturnsAncestorsAndDescendants(): void {
        $response = $this->apiRequest('/api/v4/churchrelationships', ['id' => 1]);

        $this->assertArrayHasKey('ancestors', $response);
        $this->assertArrayHasKey('descendants', $response);
        $this->assertIsArray($response['ancestors']);
        $this->assertIsArray($response['descendants']);
    }

    /**
     * Nem letező id esetén hibát ad vissza.
     */
    public function testRelationshipsEndpointReturnsErrorForNonExistentChurch(): void {
        $response = $this->apiRequest('/api/v4/churchrelationships', ['id' => 999999]);

        $this->assertEquals(1, $response['error']);
    }

    /**
     * A hierarchia endpoint letezik es JSON-t ad vissza.
     */
    public function testHierarchiaEndpointReturnsFlatIdList(): void {
        $ch = curl_init($this->baseUrl . '/templom/1/hierarchia');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError   = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            $this->fail('Curl error: ' . $curlError);
        }

        $this->assertEquals(200, $httpCode);

        $response = json_decode($rawResponse, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('ids', $response);
        $this->assertIsArray($response['ids']);
    }

    /**
     * A hierarchia endpoint tartalmazza a sajat id-t.
     */
    public function testHierarchiaEndpointIncludesSelfId(): void {
        $ch = curl_init($this->baseUrl . '/templom/1/hierarchia');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $rawResponse = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($rawResponse, true);
        $this->assertContains(1, $response['ids'], 'A hierarchia ID listának tartalmaznia kell a saját ID-t.');
    }
}
