<?php

/**
 * @file BadResponseException.php
 * @brief This file contains the BadResponseException class.
 * @details
 * @author Filippo F. Fadda
 */


//! The CouchDB's errors namespace
namespace Surfer\Exception;


use Surfer\Message\Request;
use Surfer\Message\Response;


/**
 * @brief Exception thrown when a bad Response is received.
 */
class BadResponseException extends \RuntimeException {
  private $humanReadableError;

  protected $request;
  protected $response;

  protected $info = [];


  /**
   * @brief Creates a BadResponseException class instance.
   * @param Request $request An instance of the Request class.
   * @param Response $response An instance of the Response class.
   */
  public function __construct(Request $request, Response $response) {
    $this->request = $request;
    $this->response = $response;

    $this->humanReadableError = $response->getSupportedStatusCodes()[$response->getStatusCode()];

    parent::__construct($this->humanReadableError, $response->getStatusCode());
  }


  /**
   * @brief Returns the request.
   * @retval EoC::Message::Request
   */
  public final function getRequest() {
    return $this->request;
  }


  /**
   * @brief Returns the response.
   * @retval EoC::Message::Response
   */
  public final function getResponse() {
    return $this->response;
  }


  /**
   * @brief Overrides the magic method to get all the information about the error.
   */
  public function __toString() {
    $error = $this->response->getBodyAsArray();

    $this->info[] = "[Error Code] ".$error["error"];
    $this->info[] = "[Error Reason] ".$error["reason"];

    $statusCode = (int)$this->response->getStatusCode();

    // 4xx - Client Error Status Codes
    // 5xx - Server Error Status Codes
    // 6xx - Unknown Error Status Codes
    switch ($statusCode) {
      case ($statusCode < 500):
        $this->info[] = "[Error Type] Client Error";
        break;
      case ($statusCode >= 500):
        $this->info[] = "[Error Type] Server Error";
        break;
      default:
        $this->info[] = "[Error Type] Unknown Error";
        break;
    }

    $this->info[] = "[Error Code] ".$this->response->getStatusCode();
    $this->info[] = "[Error Message] ".$this->humanReadableError;
    $this->info[] = "[Request]";
    $this->info[] = sprintf('%s', $this->request);
    $this->info[] = "[Response]";
    $this->info[] = sprintf('%s', $this->response);

    return implode(PHP_EOL, $this->info);
  }

}