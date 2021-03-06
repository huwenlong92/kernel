<?php

namespace Larkit\Kernel;

use Larkit\Kernel\Contracts\MessageInterface;
use Larkit\Kernel\Exceptions\BadRequestException;
use Larkit\Kernel\Exceptions\InvalidArgumentException;
use Larkit\Kernel\Messages\Message;
use Larkit\Kernel\Messages\News;
use Larkit\Kernel\Messages\NewsItem;
use Larkit\Kernel\Messages\Raw as RawMessage;
use Larkit\Kernel\Messages\Text;
use Larkit\Kernel\Support\XML;
use Larkit\Kernel\Traits\Observable;
use Larkit\Kernel\Traits\ResponseCastable;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ServerGuard.
 *
 * 1. url 里的 signature 只是将 token+nonce+timestamp 得到的签名，只是用于验证当前请求的，在公众号环境下一直有
 * 2. 企业号消息发送时是没有的，因为固定为完全模式，所以 url 里不会存在 signature, 只有 msg_signature 用于解密消息的
 *
 * @author overtrue <i@overtrue.me>
 */
class ServerGuard
{
    use Observable;
    use ResponseCastable;

    /**
     * @var bool
     */
    protected $alwaysValidate = false;

    /**
     * Empty string.
     */
    public const SUCCESS_EMPTY_RESPONSE = 'success';

    /**
     * @var array
     */
    public const MESSAGE_TYPE_MAPPING = [
        'text' => Message::TEXT,
        'image' => Message::IMAGE,
        'voice' => Message::VOICE,
        'video' => Message::VIDEO,
        'shortvideo' => Message::SHORT_VIDEO,
        'location' => Message::LOCATION,
        'link' => Message::LINK,
        'device_event' => Message::DEVICE_EVENT,
        'device_text' => Message::DEVICE_TEXT,
        'event' => Message::EVENT,
        'file' => Message::FILE,
        'miniprogrampage' => Message::MINIPROGRAM_PAGE,
    ];

    /**
     * @var \Larkit\Kernel\ServiceContainer
     */
    protected $app;

    /**
     * Constructor.
     *
     * @codeCoverageIgnore
     *
     * @param \Larkit\Kernel\ServiceContainer $app
     */
    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;

        foreach ($this->app->extension->observers() as $observer) {
            call_user_func_array([$this, 'push'], $observer);
        }
    }

    /**
     * Handle and return response.
     *
     * @throws BadRequestException
     * @throws \Larkit\Kernel\Exceptions\InvalidArgumentException
     * @throws \Larkit\Kernel\Exceptions\InvalidConfigException
     */
    public function serve(): Response
    {
        $this->app['logger']->debug('Request received:', [
            'method' => $this->app['request']->getMethod(),
            'uri' => $this->app['request']->getUri(),
            'content-type' => $this->app['request']->getContentType(),
            'content' => $this->app['request']->getContent(),
        ]);

        $response = $this->validate()->resolve();

        $this->app['logger']->debug('Server response created:', ['content' => $response->getContent()]);

        return $response;
    }

    /**
     * @return $this
     *
     * @throws \Larkit\Kernel\Exceptions\BadRequestException
     */
    public function validate()
    {
        if (!$this->alwaysValidate && !$this->isSafeMode()) {
            return $this;
        }

        if ($this->app['request']->get('signature') !== $this->signature([
                $this->getToken(),
                $this->app['request']->get('timestamp'),
                $this->app['request']->get('nonce'),
            ])) {
            throw new BadRequestException('Invalid request signature.', 400);
        }

        return $this;
    }

    /**
     * Force validate request.
     *
     * @return $this
     */
    public function forceValidate()
    {
        $this->alwaysValidate = true;

        return $this;
    }

    /**
     * Get request message.
     *
     * @return array|\Larkit\Kernel\Support\Collection|object|string
     *
     * @throws BadRequestException
     * @throws \Larkit\Kernel\Exceptions\InvalidArgumentException
     * @throws \Larkit\Kernel\Exceptions\InvalidConfigException
     */
    public function getMessage()
    {
        $message = $this->parseMessage($this->app['request']->getContent(false));

        if (!is_array($message) || empty($message)) {
            throw new BadRequestException('No message received.');
        }

        if ($this->isSafeMode() && !empty($message['Encrypt'])) {
            $message = $this->decryptMessage($message);

            // Handle JSON format.
            $dataSet = json_decode($message, true);

            if ($dataSet && (JSON_ERROR_NONE === json_last_error())) {
                return $dataSet;
            }

            $message = XML::parse($message);
        }

        return $this->detectAndCastResponseToType($message, $this->app->config->get('response_type'));
    }

    /**
     * Resolve server request and return the response.
     *
     * @throws \Larkit\Kernel\Exceptions\BadRequestException
     * @throws \Larkit\Kernel\Exceptions\InvalidArgumentException
     * @throws \Larkit\Kernel\Exceptions\InvalidConfigException
     */
    protected function resolve(): Response
    {
        $result = $this->handleRequest();

        if ($this->shouldReturnRawResponse()) {
            $response = new Response($result['response']);
        } else {
            $response = new Response(
                $this->buildResponse($result['to'], $result['from'], $result['response']),
                200,
                ['Content-Type' => 'application/xml']
            );
        }

        $this->app->events->dispatch(new Events\ServerGuardResponseCreated($response));

        return $response;
    }

    /**
     * @return string|null
     */
    protected function getToken()
    {
        return $this->app['config']['token'];
    }

    /**
     * @param \Larkit\Kernel\Contracts\MessageInterface|string|int $message
     *
     * @return string
     *
     * @throws \Larkit\Kernel\Exceptions\InvalidArgumentException
     */
    public function buildResponse(string $to, string $from, $message)
    {
        if (empty($message) || self::SUCCESS_EMPTY_RESPONSE === $message) {
            return self::SUCCESS_EMPTY_RESPONSE;
        }

        if ($message instanceof RawMessage) {
            return $message->get('content', self::SUCCESS_EMPTY_RESPONSE);
        }

        if (is_string($message) || is_numeric($message)) {
            $message = new Text((string) $message);
        }

        if (is_array($message) && reset($message) instanceof NewsItem) {
            $message = new News($message);
        }

        if (!($message instanceof Message)) {
            throw new InvalidArgumentException(sprintf('Invalid Messages type "%s".', gettype($message)));
        }

        return $this->buildReply($to, $from, $message);
    }

    /**
     * Handle request.
     *
     * @throws \Larkit\Kernel\Exceptions\BadRequestException
     * @throws \Larkit\Kernel\Exceptions\InvalidArgumentException
     * @throws \Larkit\Kernel\Exceptions\InvalidConfigException
     */
    protected function handleRequest(): array
    {
        $castedMessage = $this->getMessage();

        $messageArray = $this->detectAndCastResponseToType($castedMessage, 'array');

        $response = $this->dispatch(self::MESSAGE_TYPE_MAPPING[$messageArray['MsgType'] ?? $messageArray['msg_type'] ?? 'text'], $castedMessage);

        return [
            'to' => $messageArray['FromUserName'] ?? '',
            'from' => $messageArray['ToUserName'] ?? '',
            'response' => $response,
        ];
    }

    /**
     * Build reply XML.
     */
    protected function buildReply(string $to, string $from, MessageInterface $message): string
    {
        $prepends = [
            'ToUserName' => $to,
            'FromUserName' => $from,
            'CreateTime' => time(),
            'MsgType' => $message->getType(),
        ];

        $response = $message->transformToXml($prepends);

        if ($this->isSafeMode()) {
            $this->app['logger']->debug('Messages safe mode is enabled.');
            $response = $this->app['encryptor']->encrypt($response);
        }

        return $response;
    }

    /**
     * @return string
     */
    protected function signature(array $params)
    {
        sort($params, SORT_STRING);

        return sha1(implode($params));
    }

    /**
     * Parse message array from raw php input.
     *
     * @param string $content
     *
     * @return array
     *
     * @throws \Larkit\Kernel\Exceptions\BadRequestException
     */
    protected function parseMessage($content)
    {
        try {
            if (0 === stripos($content, '<')) {
                $content = XML::parse($content);
            } else {
                // Handle JSON format.
                $dataSet = json_decode($content, true);
                if ($dataSet && (JSON_ERROR_NONE === json_last_error())) {
                    $content = $dataSet;
                }
            }

            return (array) $content;
        } catch (\Exception $e) {
            throw new BadRequestException(sprintf('Invalid message content:(%s) %s', $e->getCode(), $e->getMessage()), $e->getCode());
        }
    }

    /**
     * Check the request message safe mode.
     */
    protected function isSafeMode(): bool
    {
        return $this->app['request']->get('signature') && 'aes' === $this->app['request']->get('encrypt_type');
    }

    protected function shouldReturnRawResponse(): bool
    {
        return false;
    }

    /**
     * @return mixed
     */
    protected function decryptMessage(array $message)
    {
        return $message = $this->app['encryptor']->decrypt(
            $message['Encrypt'],
            $this->app['request']->get('msg_signature'),
            $this->app['request']->get('nonce'),
            $this->app['request']->get('timestamp')
        );
    }
}
