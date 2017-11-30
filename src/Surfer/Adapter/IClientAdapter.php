<?php

/**
 * @file IClientAdapter.php
 * @brief This file contains the IClientAdapter class.
 * @details
 * @author Filippo F. Fadda
 */


//! The adaptors.
namespace Surfer\Adapter;


use Surfer\Message\Request;
use Surfer\Message\Response;
use Surfer\Hook\IChunkHook;


/**
 * @brief An HTTP client adapter interface
 * @details To create your own adapter you must implement this interface (or easily inherit from AbstractAdapter).
 */
interface IClientAdapter {


  /**
   * @brief This method is used to send an HTTP Request.
   * @details The method takes two parameter: the first one `$request` is mandatory; the second one is optional.\n
   * @param Request $request The Request object.
   * @param IChunkHook $chunkHook (optional) A class instance that implements the IChunkHook interface.
   * @return Response
   * @attention The method must return an instance of a Response class.
   */
  function send(Request $request, IChunkHook $chunkHook = NULL);

} 