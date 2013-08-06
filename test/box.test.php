<?php

require_once __DIR__.'/../src/box.php';

//define('BOX_SERVICE', 'http://192.168.9.144:8080/pic-box-web');
define('BOX_APP', 'ap4');
define('BOX_SECRET', '72c0dc58dcf344bcd2d939637fb642dd');

define('MY_BUCKET', 'bk1');
define('MY_PATH', '/abc');
define('MY_FILE', '13.jpg');
define('MY_FILE2', 'file2.txt');
define('MY_FILE3', 'xxxx.jpg');
define('MY_FILE4', 'xxxx2.jpg');
define('MY_FILE_PATH', MY_PATH.'/'.MY_FILE);
define('MY_FILE2_PATH', MY_PATH.'/'.MY_FILE2);
define('MY_FILE3_PATH', MY_PATH.'/'.MY_FILE3);
define('MY_FILE4_PATH', MY_PATH.'/'.MY_FILE4);

$box = new HupuBox(array(
//  'service' => BOX_SERVICE,
  'app' => BOX_APP,
  'secret' => BOX_SECRET,
));

// Scan files
function testScan() {
  global $box;
  $res = $box->scan(MY_BUCKET, MY_PATH);
  return !empty($res);
}
assert('testScan()');

// Get file
function testGet() {
  global $box;
  $res = $box->get(MY_BUCKET, MY_FILE_PATH);
  return !empty($res['content_type'])
      && !empty($res['size'])
      && !empty($res['body']);
}
assert('testGet()');

// Get file 2
function testGet2() {
  global $box;
  $file = '/tmp/'.MY_FILE;
  $res = $box->get(MY_BUCKET, MY_FILE_PATH, $file);
  if (!file_exists($file)) return false;
  @unlink($file);
  return !empty($res['content_type'])
      && !empty($res['size'])
      && !empty($res['file']);
}
assert('testGet2()');

// Info File
function testInfo() {
  global $box;
  $res = $box->info(MY_BUCKET, MY_FILE_PATH);
  return !empty($res);
}
assert('testInfo()');

// Put File
function testPut() {
  global $box;
  $res = $box->put(MY_BUCKET, MY_FILE2_PATH, 'hello, world!');
  return !empty($res);
}
assert('testPut()');

// Put File 2
function testPut2() {
  global $box;
  $file2 = '/tmp/'.MY_FILE2;
  file_put_contents($file2, 'hello, world --- xxx!');
  $res = $box->put(MY_BUCKET, MY_FILE2_PATH, array(
      'file' => $file2,
      'content_type' => 'text/plain',
    ));
  @unlink($file2);
  return !empty($res);
}
assert('testPut2()');

// Put File 3
function testPut3() {
  global $box;
  $file3 = '/tmp/'.MY_FILE3;
  $box->get(MY_BUCKET, MY_FILE_PATH, $file3);
  $res = $box->put(MY_BUCKET, MY_FILE3_PATH, array(
      'file' => $file3,
      'content_type' => 'image/jpeg',
      'transforms' => 'tr5',
      'transgroups' => '',
    ));
  @unlink($file3);
  return !empty($res);
}
assert('testPut3()');

// Move File
function testMove() {
  global $box;
  $res = $box->put(MY_BUCKET, MY_FILE4_PATH, array(
      'move_from' => MY_FILE3_PATH,
      'transforms' => 'tr5',
    ));
  return !empty($res);
}
assert('testMove()');
exit;

// Copy File
function testCopy() {
  global $box;
  $res = $box->put(MY_BUCKET, MY_FILE3_PATH, array(
      'copy_from' => MY_FILE4_PATH,
      'transforms' => 'tr5',
    ));
  return !empty($res);
}
assert('testCopy()');

// Post Files
function testPost() {
  global $box;

  $file2 = '/tmp/'.MY_FILE2;
  file_put_contents($file2, 'hello, world --- post!');
  $file3 = '/tmp/'.MY_FILE3;
  $box->get(MY_BUCKET, MY_FILE_PATH, $file3);

  $res = $box->post(MY_BUCKET, MY_PATH, array(
    MY_FILE2 => array(
      'file' => $file2,
      'content_type' => 'text/plain'
    ),
    MY_FILE3 => array(
      'file' => $file3,
      'content_type' => 'image/jpeg'
    ),
  ), array(
    'transforms' => 'tr5',
    'transgroups' => '',
  ));

  @unlink($file2);
  @unlink($file3);

  return !empty($res);
}
assert('testPost()');

// Delete File
function testDel() {
  global $box;
  $res = $box->del(MY_BUCKET, MY_FILE2_PATH, true);
  return !empty($res);
}
assert('testDel()');

// Direct post form
function testDirectPost() {
  global $box;
  $res = $box->form(MY_BUCKET, MY_PATH, 'http://localhost:3000/notific');
  return !empty($res['action'])
      && !empty($res['auth'])
      && !empty($res['date'])
      && !empty($res['backend']);
}
assert('testDirectPost()');
