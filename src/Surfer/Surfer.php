<?php

/**
 * @file Couch.php
 * @brief This file contains the Couch class.
 * @details
 * @author Filippo F. Fadda
 */

//! This is the main Elephant on Couch library namespace.
namespace Surfer;


use Surfer\Adapter\IClientAdapter;
use Surfer\Message\Request;
use Surfer\Message\Response;
use Surfer\Hook\IChunkHook;


/**
 * @brief The Surfer HTTP client. You need an instance of this class to make your HTTP requests.
 * @nosubgrouping
 * @todo Add Memcached support. Remember to use Memcached extension, not memcache.
 * @todo Add Post File support.
 * @todo Check ISO-8859-1 because CouchDB uses it, in particular utf8_encode().
 */
class Surfer {

  //! The user agent name.
  const USER_AGENT_NAME = "Surfer";

  /**
   * @var IClientAdapter $client
   */
  protected $client;


  /**
   * @brief Creates a Couch class instance.
   * @param IClientAdapter $adapter An instance of a class that implements the IClientAdapter interface.
   */
  public function __construct(IClientAdapter $adapter) {
    $this->client = $adapter;
  }


  /**
   * @brief This method is used to send a Request to CouchDB.
   * @details If you want call a not supported CouchDB API, you can use this function to send your request.\n
   * You can also provide an instance of a class that implements the IChunkHook interface, to deal with a chunked
   * response.
   * @param Request $request The Request object.
   * @param IChunkHook $chunkHook (optional) A class instance that implements the IChunkHook interface.
   * @return Response
   */
  public function send(Request $request, IChunkHook $chunkHook = NULL) {
    // Sets user agent information.
    $request->setHeaderField(Request::USER_AGENT_HF, self::USER_AGENT_NAME);

    // We accept JSON.
    $request->setHeaderField(Request::ACCEPT_HF, "application/json");

    // We close the connection after read the response.
    // NOTE: we don't use anymore the connection header field, because we use the same socket until the end of script.
    //$request->setHeaderField(Message::CONNECTION_HF, "close");

    $response = $this->client->send($request, $chunkHook);

    // 1xx - Informational Status Codes
    // 2xx - Success Status Codes
    // 3xx - Redirection Status Codes
    // 4xx - Client Error Status Codes
    // 5xx - Server Error Status Codes
    $statusCode = (int)$response->getStatusCode();

    switch ($statusCode) {
      case ($statusCode >= 200 && $statusCode < 300):
        break;
      case ($statusCode < 200):
        //$this->handleInformational($request, $response);
        break;
      case ($statusCode < 400):
        //$this->handleRedirection($request, $response);
        break;
      case ($statusCode < 500):
        throw new Exception\ClientErrorException($request, $response);
      case ($statusCode >= 500):
        throw new Exception\ServerErrorException($request, $response);
      default:
        throw new Exception\UnknownResponseException($request, $response);
    }

    return $response;
  }

}