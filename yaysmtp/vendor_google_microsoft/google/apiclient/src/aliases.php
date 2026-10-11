<?php

namespace YaySMTP;

if (\class_exists('YaySMTP\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['YaySMTP\Google\Client' => 'YaySMTP\Google_Client', 'YaySMTP\Google\Service' => 'YaySMTP\Google_Service', 'YaySMTP\Google\AccessToken\Revoke' => 'YaySMTP\Google_AccessToken_Revoke', 'YaySMTP\Google\AccessToken\Verify' => 'YaySMTP\Google_AccessToken_Verify', 'YaySMTP\Google\Model' => 'YaySMTP\Google_Model', 'YaySMTP\Google\Utils\UriTemplate' => 'YaySMTP\Google_Utils_UriTemplate', 'YaySMTP\Google\AuthHandler\Guzzle6AuthHandler' => 'YaySMTP\Google_AuthHandler_Guzzle6AuthHandler', 'YaySMTP\Google\AuthHandler\Guzzle7AuthHandler' => 'YaySMTP\Google_AuthHandler_Guzzle7AuthHandler', 'YaySMTP\Google\AuthHandler\Guzzle5AuthHandler' => 'YaySMTP\Google_AuthHandler_Guzzle5AuthHandler', 'YaySMTP\Google\AuthHandler\AuthHandlerFactory' => 'YaySMTP\Google_AuthHandler_AuthHandlerFactory', 'YaySMTP\Google\Http\Batch' => 'YaySMTP\Google_Http_Batch', 'YaySMTP\Google\Http\MediaFileUpload' => 'YaySMTP\Google_Http_MediaFileUpload', 'YaySMTP\Google\Http\REST' => 'YaySMTP\Google_Http_REST', 'YaySMTP\Google\Task\Retryable' => 'YaySMTP\Google_Task_Retryable', 'YaySMTP\Google\Task\Exception' => 'YaySMTP\Google_Task_Exception', 'YaySMTP\Google\Task\Runner' => 'YaySMTP\Google_Task_Runner', 'YaySMTP\Google\Collection' => 'YaySMTP\Google_Collection', 'YaySMTP\Google\Service\Exception' => 'YaySMTP\Google_Service_Exception', 'YaySMTP\Google\Service\Resource' => 'YaySMTP\Google_Service_Resource', 'YaySMTP\Google\Exception' => 'YaySMTP\Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \YaySMTP\Google\Task\Composer
{
}
if (\false) {
    class Google_AccessToken_Revoke extends \YaySMTP\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \YaySMTP\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \YaySMTP\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle5AuthHandler extends \YaySMTP\Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \YaySMTP\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \YaySMTP\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \YaySMTP\Google\Client
    {
    }
    class Google_Collection extends \YaySMTP\Google\Collection
    {
    }
    class Google_Exception extends \YaySMTP\Google\Exception
    {
    }
    class Google_Http_Batch extends \YaySMTP\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \YaySMTP\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \YaySMTP\Google\Http\REST
    {
    }
    class Google_Model extends \YaySMTP\Google\Model
    {
    }
    class Google_Service extends \YaySMTP\Google\Service
    {
    }
    class Google_Service_Exception extends \YaySMTP\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \YaySMTP\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \YaySMTP\Google\Task\Exception
    {
    }
    class Google_Task_Retryable extends \YaySMTP\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \YaySMTP\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \YaySMTP\Google\Utils\UriTemplate
    {
    }
}
