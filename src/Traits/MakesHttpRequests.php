<?php

declare(strict_types=1);


namespace Larkit\Kernel\Traits;

use Larkit\Kernel\Http\StreamResponse;
use Larkit\Kernel\Contracts\Encrypter;
use Larkit\Kernel\Encryption\DefaultEncrypter;
use Larkit\Kernel\Exceptions\DelegationException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Larkit\Kernel\LarkitConfig;

trait MakesHttpRequests
{
    use ResponseCastable;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * @var Encrypter
     */
    protected $encrypter;

    /**
     * @param string $endpoint
     * @param array $payload
     *
     * @return array|\Larkit\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \Larkit\Kernel\Exceptions\InvalidArgumentException
     * @throws \Larkit\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request($endpoint, array $payload)
    {
        $response = $this->getHttpClient()->request('POST', $endpoint, [
            'form_params' => $this->buildFormParams($payload),
        ]);

        $parsed = $this->parseResponse($response);

        return $this->detectAndCastResponseToType(
            $this->getEncrypter()->decrypt($parsed['response']),
            ($parsed['response_type'] === StreamResponse::class) ? 'raw' : $this->app['config']['response_type']
        );
    }

    /**
     * @param array $payload
     *
     * @return array
     */
    protected function buildFormParams($payload)
    {
        return [
            'encrypted' => $this->getEncrypter()->encrypt(json_encode($payload)),
        ];
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    protected function parseResponse($response)
    {
        $result = json_decode((string)$response->getBody(), true);

        if (isset($result['exception'])) {
            throw (new DelegationException($result['message']))->setException($result['exception']);
        }

        return $result;
    }

    /**
     * @return \GuzzleHttp\ClientInterface
     */
    protected function getHttpClient(): ClientInterface
    {
        return $this->httpClient ?: $this->httpClient = new Client([
            'base_uri' => $this->app['config']['delegation']['host'],
        ]);
    }

    /**
     * @return Encrypter|DefaultEncrypter
     */
    protected function getEncrypter()
    {
        return $this->encrypter ?: $this->encrypter = new DefaultEncrypter(
            LarkitConfig::getEncryptionKey()
        );
    }
}
