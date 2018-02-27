<?php

/**
 * @file SocketAdapter.php
 * @brief This file contains the SocketAdapter class.
 * @details
 * @author Filippo F. Fadda
 */


namespace Surfer\Adapter;


use Surfer\Message\Message;
use Surfer\Message\Request;
use Surfer\Message\Response;
use Surfer\Hook\IChunkHook;


/**
 * @brief An HTTP 1.1 client using raw sockets.
 * @details This client is using HTTP/1.1 version.\n
 * Encoding is made according RFC 3986, using rawurlencode().\n
 * It supports 100-continue, chunked responses, persistent connections, etc.
 * @nosubgrouping
 */
class SocketAdapter extends AbstractAdapter {

  //! HTTP protocol version.
  const HTTP_VERSION = "HTTP/1.1";

  //! Buffer dimension.
  const BUFFER_LENGTH = 8192;

  //! Maximum period to wait before the response is sent.
  const DEFAULT_TIMEOUT = 60000;

  protected static $defaultSocketTimeout;

  // Socket connection timeout in seconds, specified by a float.
  protected $timeout;

  // Socket handle.
  protected $handle;


  /**
   * @copydoc AbstractAdapter::__construct()
   * @param bool $persistent (optional) When `true` the client uses a persistent connection.
  */
  public function __construct($server = parent::DEFAULT_SERVER, $userName = "", $password = "", $persistent = TRUE) {
    $this->initialize();

    parent::__construct($server, $userName, $password);

    $this->timeout = static::$defaultSocketTimeout;

    // Establishes a connection within the server.
    if ($persistent)
      $this->handle = @pfsockopen($this->scheme.$this->host, $this->port, $errno, $errstr, $this->timeout);
    else
      $this->handle = @fsockopen($this->scheme.$this->host, $this->port, $errno, $errstr, $this->timeout);

    if (!is_resource($this->handle))
      throw new \ErrorException($errstr, $errno);
  }


  /**
   * @brief Closes the file pointer.
   */
  public function __destruct() {
    //@fclose($this->handle);
  }


  /**
   * @copydoc AbstractAdapter::initialize()
   */
  public function initialize() {

    if (!static::$initialized) {
      static::$initialized = TRUE;

      // If PHP is not properly recognizing the line endings when reading files either on or created by a Macintosh
      // computer, enabling the auto_detect_line_endings run-time configuration option may help resolve the problem.
      ini_set("auto_detect_line_endings", TRUE);

      // By default the default_socket_timeout php.ini setting is used.
      static::$defaultSocketTimeout = ini_get("default_socket_timeout");
    }
  }


  /**
   * @brief Writes the entire request over the socket.
   * @param[in] Request $request A request.
   */
  protected function writeRequest(Request $request) {
    $command = $request->getMethod()." ".$request->getPath().$request->getQueryString()." ".self::HTTP_VERSION;

    // Writes the request over the socket.
    fputs($this->handle, $command.Message::CRLF);
    fputs($this->handle, $request->getHeaderAsString().Message::CRLF);
    fputs($this->handle, Message::CRLF);
    fputs($this->handle, $request->getBody());
    fputs($this->handle, Message::CRLF);
  }


  /**
   * @brief Reads the the status code and the header of the response.
   * @return string
   */
  protected function readResponseStatusCodeAndHeader() {
    $statusCodeAndHeader = "";

    while (!feof($this->handle)) {
      // We use fgets() because it stops reading at first newline or buffer length, depends which one is reached first.
      $buffer = fgets($this->handle, self::BUFFER_LENGTH);

      // Adds the buffer to the header.
      $statusCodeAndHeader .= $buffer;

      // The header is separated from the body by a newline, so we break when we read it.
      if ($buffer == Message::CRLF)
        break;
    }

    return $statusCodeAndHeader;
  }

  /**
   * @brief Reads the entity-body of a chunked response.
   * @see http://www.jmarshall.com/easy/http/#http1.1c2
   * @param IChunkHook $chunkHook (optional) The chunk's hook.
   * @return string
   */
  protected function readChunkedResponseBody($chunkHook) {
    $body = "";

    while (!feof($this->handle)) {
      // Gets the line which has the length of this chunk.
      $line = fgets($this->handle, self::BUFFER_LENGTH);

      // If it's only a newline, this normally means it's read the total amount of data requested minus the newline
      // continue to next loop to make sure we're done.
      if ($line == Message::CRLF)
        continue;

      // The length of the block is expressed in hexadecimal.
      $length = hexdec($line);

      if (!is_int($length))
        throw new \RuntimeException("The response doesn't seem chunk encoded.");

      // Zero is sent when at the end of the chunks or the end of the stream.
      if ($length < 1)
        break;

      // Reads the chunk.
      // When reading from network streams or pipes, such as those returned when reading remote files or from popen()
      // and proc_open(), reading will stop after a new packet is available. This means that we must collect the data
      // together in chunks. So, we can't pass to the fread() the entire length because it could return less data than
      // expected. We have to read, instead, the standard buffer length, and concatenate the read chunks.
      $buffer = "";

      while ($length > 0) {
        $size = min(self::BUFFER_LENGTH, $length);
        $data = fread($this->handle, $size);

        if (strlen($data) == 0)
          break; // EOF

        $buffer .= $data;
        $length -= strlen($data);
      }

      // If a function has been hooked, calls it, else just adds the buffer to the body.
      if (is_null($chunkHook))
        $body .= $buffer;
      else
        $chunkHook->process($buffer);
    }

    // A chunk response might have some footer, but CouchDB doesn't use them, so we simply ignore them.
    while (!feof($this->handle)) {
      // We use fgets() because it stops reading at first newline or buffer length, depends which one is reached first.
      $buffer = fgets($this->handle, self::BUFFER_LENGTH);

      // The chunk response ends with a newline, so we break when we read it.
      if ($buffer == Message::CRLF)
        break;
    }

    return $body;
  }


  /**
   * @brief Reads the entity-body of a standard response.
   * @param Response $response The response.
   * @return string
   */
  protected function readStandardResponseBody(Response $response) {
    $body = "";

    // Retrieves the body length from the header.
    $length = (int)$response->getHeaderFieldValue(Response::CONTENT_LENGTH_HF);

    // The response should have a body, if not we have finished.
    if ($length > 0) {
      $bytes = 0;

      while (!feof($this->handle)) {
        $buffer = fgets($this->handle);
        $body .= $buffer;
        $bytes += strlen($buffer);

        if ($bytes >= $length)
          break;
      }
    }

    return $body;
  }


  /**
   * @brief Reads the entity-body.
   * @param Response $response The response.
   * @param IChunkHook $chunkHook (optional) The chunk's hook.
   * @return string
   */
  protected function readResponseBody(Response $response, $chunkHook) {
    if ($response->getHeaderFieldValue(Response::TRANSFER_ENCODING_HF) == "chunked")
      return $this->readChunkedResponseBody($chunkHook);
    else
      return $this->readStandardResponseBody($response);
  }


  /**
   * @copydoc AbstractAdapter::setTimeout()
   */
  public function setTimeout($seconds) {
    return stream_set_timeout($this->handle, $seconds);
  }


  /**
   * @copydoc AbstractAdapter::send()
   */
  public function send(Request $request, IChunkHook $chunkHook = NULL) {
    $request->setHeaderField(Request::HOST_HF, $this->host.":".$this->port);

    if (!empty($this->userName))
      $request->setBasicAuth($this->userName, $this->password);

    // Sets the Content-Length header only when the given request has a message body.
    if ($request->hasBody())
      $request->setHeaderField(Message::CONTENT_LENGTH_HF, $request->getBodyLength());

    // Writes the request over the socket.
    $this->writeRequest($request);

    // Creates the Response object.
    $response = new Response($this->readResponseStatusCodeAndHeader());

    // The Content-Length entity-header field indicates the size of the entity-body, in decimal number of OCTETs, sent
    // to the recipient or, in the case of the HEAD method, the size of the entity-body that would have been sent had
    // the request been a GET.
    if ($request->getMethod() != Request::HEAD_METHOD) {
      // Assigns the body to the response, if any is present.
      $response->setBody($this->readResponseBody($response, $chunkHook));
    }

    return $response;
  }

} 