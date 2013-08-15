<?php
/*
 Copyright 2013, Wenlin Wang <wangwenlin@hupu.com>
 */

namespace Hupu\Box;

/**
 * Box REST service client
 */
class Client {

  private $service;

  private $app;

  private $secret;

  private $client;

  public function __construct($opts) {
    $this->service = isset($opts['service']) ? $opts['service'] : 'http://localhost:3000';
    $this->app = isset($opts['app']) ? $opts['app'] : '_default';
    $this->secret = isset($opts['secret']) ? $opts['secret'] : '';
    $this->client = new \Guzzle\Http\Client(self::makeuri__($this->service, $this->app));
  }

  /**
   * List files
   */
  public function scan($bucket, $path = '/') {
    $res = $this->request__($bucket, $path, function ($client, $uri, $headers) {
      return $client->get($uri, $headers);
    });
    return @json_decode($res->getBody(true));
  }

  // TODO: need get image extra info by 'X-Box-Extra' header when authorized
  /**
   * Get file
   */
  public function get($bucket, $path, $save_to = null) {
    $res = $this->request__($bucket, $path, function ($client, $uri, $headers) use ($save_to) {
      if ($save_to) {
        return $client->get($uri, $headers, array('save_to' => $save_to));
      } else {
        return $client->get($uri, $headers);
      }
    });

    $arr = array(
      'content_type' => $res->getContentType(),
      'size' => $res->getContentLength(),
      );

    if ($save_to) {
      $arr['file'] = $save_to;
    } else {
      $arr['body'] = $res->getBody(true);
    }

    return $arr;
  }

  // TODO: need use 'HEAD' action
  // TODO: no auto create not exist transforms
  /**
   * Get file info
   */
  public function info($bucket, $path) {
    $res = $this->request__($bucket, $path, function ($client, $uri, $headers) {
      $req = $client->get($uri, $headers);
      $req->getQuery()->set('info', \Guzzle\Http\QueryString::BLANK);
      return $req;
    });
    return @json_decode($res->getBody(true));
  }

  // TODO: add 'X-Box-Touch' to auto create default transforms
  /**
   * Put file
   */
  public function put($bucket, $path, $mixed) {
    $body = null;
    $content_type = null;
    $addhdrs = array();

    if (!is_array($mixed)) {
      $mixed = array('body' => $mixed);
    }

    if (!empty($mixed['move_from'])) {
      self::addMoveFrom__($addhdrs, $mixed['move_from']);
    } else if (!empty($mixed['copy_from'])) {
      self::addCopyFrom__($addhdrs, $mixed['copy_from']);
    } else if (!empty($mixed['file'])) {
      $body = is_resource($mixed['file']) ? $mixed['file'] : fopen($mixed['file'], 'rb');
    } else if (!empty($mixed['body'])) {
      $body = $mixed['body'];
    }

    if (!empty($mixed['content_type'])) {
      $content_type = $mixed['content_type'];
    }

    if (!empty($mixed['transforms'])) {
      self::addTransforms__($addhdrs, $mixed['transforms']);
    }
    if (!empty($mixed['transgroups'])) {
      self::addTransGroups__($addhdrs, $mixed['transgroups']);
    }
    if (!empty($mixed['force_refresh'])) {
      self::forceRefresh__($addhdrs);
    }

    $res = $this->request__($bucket, $path, function ($client, $uri, $headers) use ($body, $content_type, $addhdrs) {
      $headers = array_merge($headers, $addhdrs);

      $req = $client->put($uri, $headers);

      if (!empty($body)) {
        if (!empty($content_type)) {
          $req->setBody($body, $content_type);
        } else {
          $req->setBody($body);
        }
      }

      return $req;
    });

    return @json_decode($res->getBody(true));
  }

  /**
   * Post files
   */
  public function post($bucket, $path, $files = array(), $opts = null) {
    $addhdrs = array();

    if (!empty($opts['transforms'])) {
      self::addTransforms__($addhdrs, $opts['transforms']);
    }
    if (!empty($opts['transgroups'])) {
      self::addTransGroups__($addhdrs, $opts['transgroups']);
    }
    if (!empty($opts['force_refresh'])) {
      self::forceRefresh__($addhdrs);
    }

    $res = $this->request__($bucket, $path, function ($client, $uri, $headers) use ($files, $addhdrs) {
      $headers = array_merge($headers, $addhdrs);

      $req = $client->post($uri, $headers);

      foreach ($files as $k => $v) {
        if (!is_array($v)) {
          $req->addPostFile($k, $v);
        } else if (!empty($v['content_type'])) {
          $req->addPostFile($k, $v['file'], $v['content_type']);
        } else {
          $req->addPostFile($k, $v['file']);
        }
      }

      return $req;
    });

    return @json_decode($res->getBody(true));
  }

  /**
   * Delete file
   */
  public function del($bucket, $path, $force_refresh = false) {
    $addhdrs = array();

    if ($force_refresh) {
      self::forceRefresh__($addhdrs);
    }

    $res = $this->request__($bucket, $path, function ($client, $uri, $headers) use ($addhdrs) {
      $headers = array_merge($headers, $addhdrs);
      return $client->delete($uri, $headers);
    });

    return @json_decode($res->getBody(true));
  }

  /**
   * Direct post form helper
   */
  public function form($bucket, $path, $backend) {
    $uri = self::makepath__($bucket, $path);
    $headers = array();

    if (!empty($backend)) {
      self::addBackend__($headers, $backend);
    }

    $req = $this->client->post($uri, $headers);
    $req->getQuery()->set('direct', \Guzzle\Http\QueryString::BLANK);

    self::authorize__($req, $this->app, $this->secret);

    return array(
      'action' => $req->getUrl(),
      'auth' => self::retrAuth__($req),
      'date' => self::retrDate__($req),
      'backend' => $backend,
      );
  }

  private function request__($bucket, $path, $callback) {
    $uri = self::makepath__($bucket, $path);
    $headers = array();

    $req = $callback($this->client, $uri, $headers);
    self::authorize__($req, $this->app, $this->secret);

    try {
      return $req->send();
    } catch (\Guzzle\Http\Exception\BadResponseException $e) {
      throw new RequestException($e->getMessage(), $e->getResponse()->getStatusCode(), $e);
    } catch (\Guzzle\Http\Exception\CurlException $e) {
      throw new RequestException($e->getMessage(), null, $e);
    }
  }

  private static function makeuri__($service, $app) {
    return ($service . '/' . $app);
  }

  private static function makepath__($bucket, $path) {
    return ($bucket . ($path[0] === '/' ? $path : '/' . $path));
  }

  private static function addMoveFrom__(&$headers, $move_from) {
    $headers['X-Box-Move'] = $move_from;
  }

  private static function addCopyFrom__(&$headers, $copy_from) {
    $headers['X-Box-Copy'] = $copy_from;
  }

  private static function addTransforms__(&$headers, $transforms) {
    $headers['X-Box-Trans'] = $transforms;
  }

  private static function addTransGroups__(&$headers, $transgroups) {
    $headers['X-Box-TransGroup'] = $transgroups;
  }

  private static function addBackend__(&$headers, $backend) {
    $headers['X-Box-Backend'] = $backend;
  }

  private static function forceRefresh__(&$headers) {
    $headers['X-Box-Refresh'] = 'force';
  }

  private static function retrAuth__($req) {
    return (string)($req->getHeader('Authorization'));
  }

  private static function retrDate__($req) {
    return (string)($req->getHeader('Date'));
  }

  private static function authorize__($req, $app, $secret) {
    $headers = array('request-line', 'date');
    $lines = array();
    $date = gmdate('D, d M Y H:i:s') . ' GMT';

    $lines[] = $req->getMethod()
               . ' ' . trim($req->getResource())
               . ' ' . 'HTTP/' . $req->getProtocolVersion();

    $lines[] = 'date: ' . $date;

    foreach ($req->getHeaders() as $k => $v) {
      if (stripos($k, 'X-Box-') === 0) {
        $kk = strtolower($k);
        $headers[] = $kk;
        $lines[] = $kk . ': ' . $v;
      }
    }

    $headers = implode(' ', $headers);
    $lines = implode("\n", $lines);

    $sign = base64_encode(hash_hmac('sha256', $lines, $secret, true));

    $auth = 'Signature'
            . ' keyId="' . $app .'"'
            . ',algorithm="hmac-sha256"'
            . ',headers="' . $headers . '"'
            . ',signature="' . $sign . '"';

    $req->setHeader('Authorization', $auth);

    $req->setHeader('Date', $date);
  }
}
