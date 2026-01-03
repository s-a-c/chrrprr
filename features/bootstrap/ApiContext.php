<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Driver\GoutteDriver;
use Behat\MinkExtension\Context\MinkContext;
use Exception;
use PHPUnit\Framework\Assert;

/**
 * API Context for Behat tests.
 *
 * Provides HTTP API testing functionality.
 */
class ApiContext extends MinkContext implements Context
{
    protected array $requestHeaders = [];

    protected ?string $requestBody = null;

    protected ?string $responseBody = null;

    protected int $responseStatus = 0;

    /**
     * Set request header.
     *
     * @Given I set header :header to :value
     */
    public function iSetHeaderTo(string $header, string $value): void
    {
        $this->requestHeaders[$header] = $value;
    }

    /**
     * Set request body.
     *
     * @Given I set request body to:
     */
    public function iSetRequestBodyTo(PyStringNode $body): void
    {
        $this->requestBody = $body->getRaw();
    }

    /**
     * Send HTTP request.
     *
     * @When I send a :method request to :url
     */
    public function iSendARequestTo(string $method, string $url): void
    {
        $session = $this->getSession();
        $driver = $session->getDriver();

        if (! ($driver instanceof GoutteDriver)) {
            throw new Exception('API context requires Goutte driver');
        }

        $client = $driver->getClient();

        // Set headers
        foreach ($this->requestHeaders as $header => $value) {
            $client->setHeader($header, $value);
        }

        // Send request
        $client->request($method, $url, [], [], [], $this->requestBody);

        $this->responseStatus = $client->getResponse()->getStatus();
        $this->responseBody = $client->getResponse()->getContent();

        // Reset for next request
        $this->requestHeaders = [];
        $this->requestBody = null;
    }

    /**
     * Assert response status.
     *
     * @Then the response status should be :status
     */
    public function theResponseStatusShouldBe(int $status): void
    {
        Assert::assertEquals($status, $this->responseStatus, "Expected status {$status}, got {$this->responseStatus}");
    }

    /**
     * Assert response contains JSON.
     *
     * @Then the response should contain JSON:
     */
    public function theResponseShouldContainJson(PyStringNode $json): void
    {
        $expected = json_decode($json->getRaw(), true);
        $actual = json_decode($this->responseBody, true);

        Assert::assertNotNull($actual, 'Response is not valid JSON');
        Assert::assertEquals($expected, $actual, 'Response JSON does not match expected');
    }

    /**
     * Assert response contains header.
     *
     * @Then the response should contain header :header with value :value
     */
    public function theResponseShouldContainHeaderWithValue(string $header, string $value): void
    {
        $session = $this->getSession();
        $driver = $session->getDriver();

        if (! ($driver instanceof GoutteDriver)) {
            throw new Exception('API context requires Goutte driver');
        }

        $client = $driver->getClient();
        $responseHeaders = $client->getResponse()->getHeaders();

        $headerValue = $responseHeaders[$header] ?? null;
        Assert::assertEquals($value, $headerValue, "Header {$header} does not have expected value");
    }

    /**
     * Assert response body contains text.
     *
     * @Then the response body should contain :text
     */
    public function theResponseBodyShouldContain(string $text): void
    {
        Assert::assertStringContainsString($text, $this->responseBody, "Response body does not contain '{$text}'");
    }
}
