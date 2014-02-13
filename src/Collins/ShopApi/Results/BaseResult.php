<?php
namespace Collins\ShopApi\Results;

use Collins\ShopApi;
use Collins\ShopApi\Exception as Exception;

/**
 * Provides functionality to initialize a result object.
 * A result object, that stands for an API result, must extend this class.
 *
 * @author Antevorte GmbH
 */
abstract class BaseResult
{
    /**
     * Root key of the JSON API result
     * @var string
     */
    protected $resultKey;

    /**
     * Page hash fo the API result
     * @var string
     */
    public $pageHash = null;

    /** @var ShopApi */
    protected $api;

    /**
     * Parses the passed API response object and calls the initialization method of this result object.
     *
     * @param \Guzzle\Http\Message\Response $response response object of the API request
     *
     * @throws \Collins\ShopApi\CollinsException if API result contains error message an exception will be thrown
     *
     * @see \Collins\ShopApi\Collins::getResponse()
     */
    public final function __construct(\Guzzle\Http\Message\Response $response = null, ShopApi $api = null)
    {
        if (!$this->resultKey) {
            throw new Exception\IncompleteImplementationException('Result classes need to overwrite the $resultKey attribute.');
        }

        if (!$response) {
            return;
        }
        
        $data = $response->json();

        if (isset($data[0]) && isset($data[0][$this->resultKey])) {
            $result = $data[0][$this->resultKey];

            if (isset($result['error_code'])) {
                $message = implode(PHP_EOL, isset($result['error_message']) ? $result['error_message'] : '');
                $code = isset($result['error_code']) ? $result['error_code'] : 400;

                throw new Exception\ApiErrorException($message, $code);
            }

            $this->init($result);
        } else {
            $message = 'Unexpected result:' . PHP_EOL . print_r($data, true);
            $code = 400;

            throw new Exception\UnexpectedResultException($message, $code);
        }
    }

    /**
     * Initializes this result object.
     * This means, the object attributes will be filled with
     * the data given from the API response.
     * By default all the result attributes will be matches to the
     * class attributes. This method can be overwritten of custom
     * data operations need to be done.
     *
     * @param array $result
     */
    protected function init(array $result)
    {
        foreach ($result as $attribute => $value) {
            if (property_exists($this, $attribute)) {
                $this->$attribute = $value;
            }
        }
    }
}