<?php
/**
 * Created by PhpStorm.
 * User: XomiaK
 * Date: 18.05.2017
 * Time: 17:32
 */
include "includes/GuzzleHttp/ClientInterface.php";
include "includes/GuzzleHttp/Client.php";
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
class Translate
{
    /**
     * @var TranslateClient Because nobody cares about singletons
     */
    private static $staticInstance;

    private $httpClient;

    /**
     * @var string Source language - from where the string should be translated
     */
    private $sourceLanguage;

    /**
     * @var string Target language - to which language string should be translated
     */
    private $targetLanguage;

    /**
     * @var string|bool Last detected source language
     */
    private static $lastDetectedSource;

    /**
     * @var string Google Translate URL base
     */
    private $urlBase = 'https://translate.google.com/translate_a/single';

    /**
     * @var array Dynamic guzzleHTTP client options
     */
    private $httpOptions = [];

    /**
     * @var array URL Parameters
     */
    private $urlParams = [
        'client'   => 't',
        'hl'       => 'en',
        'dt'       => 't',
        'sl'       => null, // Source language
        'tl'       => null, // Target language
        'q'        => null, // String to translate
        'ie'       => 'UTF-8', // Input encoding
        'oe'       => 'UTF-8', // Output encoding
        'multires' => 1,
        'otf'      => 0,
        'pc'       => 1,
        'trs'      => 1,
        'ssel'     => 0,
        'tsel'     => 0,
        'kc'       => 1,
        'tk'       => null,
    ];

    /**
     * @var array Regex key-value patterns to replace on response data
     */
    private $resultRegexes = [
        '/,+/'  => ',',
        '/\[,/' => '[',
    ];

    /**
     * @var TokenProviderInterface
     */
    private $tokenProvider;

    /**
     * @var string Default token generator class name
     */
    private $defaultTokenProvider = GoogleTokenGenerator::class;

    /**
     * Class constructor.
     *
     * For more information about HTTP client configuration options, visit
     * "Creating a client" section of GuzzleHttp docs.
     * 5.x - http://guzzle.readthedocs.org/en/5.3/clients.html#creating-a-client
     *
     * @param string $source  Source language (Optional)
     * @param string $target  Target language (Optional)
     * @param array  $options Associative array of http client configuration options (Optional)
     *
     * @throws Exception If token provider does not implement TokenProviderInterface
     */
    public function __construct($source = null, $target = 'en', $options = [], TokenProviderInterface $tokener = null)
    {
        $this->httpClient = new GuzzleHttpClient($options); // Create HTTP client
        $this->setSource($source)->setTarget($target); // Set languages
        $this::$lastDetectedSource = false;

        if (!isset($tokener)) {
            $tokener = $this->defaultTokenProvider;
        }

        $tokenProviderReflection = new ReflectionClass($tokener);

        if ($tokenProviderReflection->implementsInterface(TokenProviderInterface::class)) {
            $this->tokenProvider = $tokenProviderReflection->newInstance();
        } else {
            throw new Exception('Token provider should implement TokenProviderInterface');
        }
    }

    /**
     * Override translate method for static call.
     *
     * @throws BadMethodCallException   If calling nonexistent method
     * @throws InvalidArgumentException If parameters are passed incorrectly
     * @throws InvalidArgumentException If the provided argument is not of type 'string'
     * @throws ErrorException           If the HTTP request fails
     * @throws UnexpectedValueException If received data cannot be decoded
     */
    public static function __callStatic($name, $args)
    {
        switch ($name) {
            case 'translate':
                if (count($args) < 3) {
                    throw new InvalidArgumentException('Expecting 3 parameters');
                }
                try {
                    $result = self::staticTranslate($args[0], $args[1], $args[2]);
                } catch (Exception $e) {
                    throw $e;
                }

                return $result;
            case 'getLastDetectedSource':
                return self::staticGetLastDetectedSource();
            default:
                throw new BadMethodCallException("Method [{$name}] does not exist");
        }
    }

    /**
     * Override translate method for instance call.
     *
     * @throws BadMethodCallException   If calling nonexistent method
     * @throws InvalidArgumentException If parameters are passed incorrectly
     * @throws InvalidArgumentException If the provided argument is not of type 'string'
     * @throws ErrorException           If the HTTP request fails
     * @throws UnexpectedValueException If received data cannot be decoded
     */
    public function __call($name, $args)
    {
        switch ($name) {
            case 'translate':
                if (count($args) < 1) {
                    throw new InvalidArgumentException('Expecting 1 parameter');
                }
                try {
                    $result = $this->instanceTranslate($args[0]);
                } catch (Exception $e) {
                    throw $e;
                }

                return $result;
            case 'getLastDetectedSource':
                return $this::staticGetLastDetectedSource();
            case 'getResponse':
                // getResponse is available for instanse calls only.
                return $this->getResponse($args[0]);
            default:
                throw new BadMethodCallException("Method [{$name}] does not exist");
        }
    }

    /**
     * Check if static instance exists and instantiate if not.
     *
     * @return void
     */
    private static function checkStaticInstance()
    {
        if (!isset(self::$staticInstance)) {
            self::$staticInstance = new self();
        }
    }

    /**
     * Set the api we are used to translete.
     *
     * @param string $source Google translate api, default is https://translate.google.com/translate_a/single
     *
     * @return TranslateClient
     */
    public function setApi($api = null)
    {
        if ($api) {
            $this->urlBase = $api;
        }

        return $this;
    }

    /**
     * Set source language we are translating from.
     *
     * @param string $source Language code
     *
     * @return TranslateClient
     */
    public function setSource($source = null)
    {
        $this->sourceLanguage = is_null($source) ? 'auto' : $source;

        return $this;
    }

    /**
     * Set translation language we are translating to.
     *
     * @param string $target Language code
     *
     * @return TranslateClient
     */
    public function setTarget($target)
    {
        $this->targetLanguage = $target;

        return $this;
    }

    /**
     * Set Google Translate URL base
     *
     * @param string $urlBase  Google Translate URL base
     *
     * @return TranslateClient
     */
    public function setUrlBase($urlBase)
    {
        $this->urlBase = $urlBase;

        return $this;
    }

    /**
     * Set guzzleHttp client options.
     *
     * @param array $options guzzleHttp client options.
     *
     * @return TranslateClient
     */
    public function setHttpOption(array $options)
    {
        $this->httpOptions = $options;

        return $this;
    }

    /**
     * Get response array.
     *
     * @param string|array $data String or array of strings to translate
     *
     * @throws InvalidArgumentException If the provided argument is not of type 'string'
     * @throws ErrorException           If the HTTP request fails
     * @throws UnexpectedValueException If received data cannot be decoded
     *
     * @return array Response
     */
    private function getResponse($data)
    {
        if (!is_string($data) && !is_array($data)) {
            throw new InvalidArgumentException('Invalid argument provided');
        }

        $tokenData = is_array($data) ? implode('', $data) : $data;

        $queryArray = array_merge($this->urlParams, [
            'sl'   => $this->sourceLanguage,
            'tl'   => $this->targetLanguage,
            'tk'   => $this->tokenProvider->generateToken($this->sourceLanguage, $this->targetLanguage, $tokenData),
        ]);

        $queryUrl = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', http_build_query($queryArray));

        $queryBodyArray = [
            'q' => $data,
        ];

        $queryBodyEncoded = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', http_build_query($queryBodyArray));

        try {
            $response = $this->httpClient->post($this->urlBase, [
                    'query' => $queryUrl,
                    'body'  => $queryBodyEncoded,
                ] + $this->httpOptions);
        } catch (GuzzleRequestException $e) {
            throw new ErrorException($e->getMessage());
        }

        $body = $response->getBody(); // Get response body

        // Modify body to avoid json errors
        $bodyJson = preg_replace(array_keys($this->resultRegexes), array_values($this->resultRegexes), $body);

        // Decode JSON data
        if (($bodyArray = json_decode($bodyJson, true)) === null) {
            throw new UnexpectedValueException('Data cannot be decoded or it\'s deeper than the recursion limit');
        }

        return $bodyArray;
    }

    /**
     * Translate text.
     *
     * This can be called from instance method translate() using __call() magic method.
     * Use $instance->translate($string) instead.
     *
     * @param string|array $data Text or array of texts to translate
     *
     * @throws InvalidArgumentException If the provided argument is not of type 'string'
     * @throws ErrorException           If the HTTP request fails
     * @throws UnexpectedValueException If received data cannot be decoded
     *
     * @return string|bool Translated text
     */
    private function instanceTranslate($data)
    {
        // Whether or not is the data an array
        $isArray = is_array($data);

        // Rethrow exceptions
        try {
            $responseArray = $this->getResponse($data);
        } catch (Exception $e) {
            throw $e;
        }

        // if response in text and the content has zero the empty returns true, lets check
        // if response is string and not empty and create array for further logic
        if (is_string($responseArray) && $responseArray != '') {
            $responseArray = [$responseArray];
        }

        // Check if translation exists
        if (!isset($responseArray[0]) || empty($responseArray[0])) {
            return false;
        }

        // Detect languages
        $detectedLanguages = [];

        // the response contains only single translation, don't create loop that will end with
        // invalid foreach and warning
        if ($isArray || !is_string($responseArray)) {
            $responseArrayForLanguages = ($isArray) ? $responseArray[0] : [$responseArray];
            foreach ($responseArrayForLanguages as $itemArray) {
                foreach ($itemArray as $item) {
                    if (is_string($item)) {
                        $detectedLanguages[] = $item;
                    }
                }
            }
        }

        // Another case of detected language
        if (isset($responseArray[count($responseArray) - 2][0][0])) {
            $detectedLanguages[] = $responseArray[count($responseArray) - 2][0][0];
        }

        // Set initial detected language to null
        $this::$lastDetectedSource = false;

        // Iterate and set last detected language
        foreach ($detectedLanguages as $lang) {
            if ($this->isValidLocale($lang)) {
                $this::$lastDetectedSource = $lang;
                break;
            }
        }

        // Reduce array to generate translated sentenece
        if ($isArray) {
            $carry = [];
            foreach ($responseArray[0] as $item) {
                $carry[] = $item[0][0][0];
            }

            return $carry;
        }
        // the response can be sometimes an translated string.
        elseif (is_string($responseArray)) {
            return $responseArray;
        } else {
            if (is_array($responseArray[0])) {
                return array_reduce($responseArray[0], function ($carry, $item) {
                    $carry .= $item[0];

                    return $carry;
                });
            } else {
                return $responseArray[0];
            }
        }
    }

    /**
     * Translate text statically.
     *
     * This can be called from static method translate() using __callStatic() magic method.
     * Use TranslateClient::translate($source, $target, $string) instead.
     *
     * @param string $source Source language
     * @param string $target Target language
     * @param string $string Text to translate
     *
     * @throws InvalidArgumentException If the provided argument is not of type 'string'
     * @throws ErrorException           If the HTTP request fails
     * @throws UnexpectedValueException If received data cannot be decoded
     *
     * @return string|bool Translated text
     */
    private static function staticTranslate($source, $target, $string)
    {
        self::checkStaticInstance();
        try {
            $result = self::$staticInstance
                ->setSource($source)
                ->setTarget($target)
                ->translate($string);
        } catch (Exception $e) {
            throw $e;
        }

        return $result;
    }

    /**
     * Get last detected language.
     *
     * @return string|bool Last detected language or boolean FALSE
     */
    private static function staticGetLastDetectedSource()
    {
        return self::$lastDetectedSource;
    }

    /**
     * Check if given locale is valid.
     *
     * @param string $lang Langauge code to verify
     *
     * @return bool
     */
    private function isValidLocale($lang)
    {
        return (bool) preg_match('/^([a-z]{2})(-[A-Z]{2})?$/', $lang);
    }
}

interface TokenProviderInterface
{
    /**
     * Generate and return a token.
     *
     * @param string $source Source language
     * @param string $target Target language
     * @param string $text   Text to translate
     *
     * @return mixed A token
     */
    public function generateToken($source, $target, $text);
}

class GoogleTokenGenerator implements TokenProviderInterface
{
    /**
     * Generate and return a token.
     *
     * @param string $source Source language
     * @param string $target Target language
     * @param string $text   Text to translate
     *
     * @return mixed A token
     */
    public function generateToken($source, $target, $text)
    {
        return $this->TL($text);
    }

    /**
     * Generate a valid Google Translate request token.
     *
     * @param string $a text to translate
     *
     * @return string
     */
    private function TL($a)
    {
        $tkk = $this->TKK();
        $b = $tkk[0];

        for ($d = [], $e = 0, $f = 0; $f < mb_strlen($a, 'UTF-8'); $f++) {
            $g = $this->charCodeAt($a, $f);
            if (128 > $g) {
                $d[$e++] = $g;
            } else {
                if (2048 > $g) {
                    $d[$e++] = $g >> 6 | 192;
                } else {
                    if (55296 == ($g & 64512) && $f + 1 < mb_strlen($a, 'UTF-8') && 56320 == ($this->charCodeAt($a, $f + 1) & 64512)) {
                        $g = 65536 + (($g & 1023) << 10) + ($this->charCodeAt($a, ++$f) & 1023);
                        $d[$e++] = $g >> 18 | 240;
                        $d[$e++] = $g >> 12 & 63 | 128;
                    } else {
                        $d[$e++] = $g >> 12 | 224;
                    }
                    $d[$e++] = $g >> 6 & 63 | 128;
                }
                $d[$e++] = $g & 63 | 128;
            }
        }
        $a = $b;
        for ($e = 0; $e < count($d); $e++) {
            $a += $d[$e];
            $a = $this->RL($a, '+-a^+6');
        }
        $a = $this->RL($a, '+-3^+b+-f');
        $a ^= $tkk[1] ? $tkk[1] + 0 : 0;
        if (0 > $a) {
            $a = ($a & 2147483647) + 2147483648;
        }
        $a = fmod($a, pow(10, 6));

        return $a.'.'.($a ^ $b);
    }

    /**
     * @return array
     */
    private function TKK()
    {
        return ['406398', (561666268 + 1526272306)];
    }

    /**
     * Process token data by applying multiple operations.
     * (Params are safe, no need for multibyte functions)
     *
     * @param int $a
     * @param string $b
     *
     * @return int
     */
    private function RL($a, $b)
    {
        for ($c = 0; $c < strlen($b) - 2; $c += 3) {
            $d = $b[$c + 2];
            $d = 'a' <= $d ? ord($d[0]) - 87 : intval($d);
            $d = '+' == $b[$c + 1] ? $this->unsignedRightShift($a, $d) : $a << $d;
            $a = '+' == $b[$c] ? ($a + $d & 4294967295) : $a ^ $d;
        }

        return $a;
    }

    /**
     * Unsigned right shift implementation
     * https://msdn.microsoft.com/en-us/library/342xfs5s(v=vs.94).aspx
     * http://stackoverflow.com/a/43359819/2953830
     *
     * @param $a
     * @param $b
     *
     * @return number
     */
    private function unsignedRightShift($a, $b)
    {
        if ($b >= 32 || $b < -32) {
            $m = (int)($b / 32);
            $b = $b - ($m * 32);
        }

        if ($b < 0) {
            $b = 32 + $b;
        }

        if ($b == 0) {
            return (($a >> 1) & 0x7fffffff) * 2 + (($a >> $b) & 1);
        }

        if ($a < 0) {
            $a = ($a >> 1);
            $a &= 2147483647;
            $a |= 0x40000000;
            $a = ($a >> ($b - 1));
        } else {
            $a = ($a >> $b);
        }

        return $a;
    }

    /**
     * Get the Unicode of the character at the specified index in a string.
     *
     * @param string $str
     * @param int    $index
     *
     * @return null|number
     */
    private function charCodeAt($str, $index)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');
        if (mb_check_encoding($char, 'UTF-8')) {
            $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
            $result = hexdec(bin2hex($ret));

            return $result;
        }

        return;
    }
}

class SampleTokenGenerator implements TokenProviderInterface
{
    /**
     * Generate a fake token just as an example.
     */
    public function generateToken($source, $target, $text)
    {
        return sprintf('%d.%d', rand(10000, 99999), rand(10000, 99999));
    }
}