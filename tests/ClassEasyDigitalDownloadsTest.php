<?php

namespace SLCA\EasyDigitalDownloads;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use wpCloud\StatelessMedia\WPStatelessStub;

/**
 * Class ClassEasyDigitalDownloadsTest
 */

class ClassEasyDigitalDownloadsTest extends TestCase {
  const TEST_URL = 'https://test.test';
  const UPLOADS_URL = self::TEST_URL . '/uploads';
  const AVATAR_FILE = 'avatars/avatar.png';
  const AVATAR_SRC_URL = self::UPLOADS_URL . '/' . self::AVATAR_FILE;
  const AVATAR_DST_URL = WPStatelessStub::TEST_GS_HOST . '/' . self::AVATAR_FILE;
  const TEST_UPLOAD_DIR = [
    'baseurl' => self::UPLOADS_URL,
    'basedir' => '/var/www/uploads',
    'error'   => false,
  ];

  // Adds Mockery expectations to the PHPUnit assertions count.
  use MockeryPHPUnitIntegration;

  private static $headers = [];

  public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

    // WP mocks
    Functions\when('wp_get_upload_dir')->justReturn( self::TEST_UPLOAD_DIR );
        
    // WP_Stateless mocks
    Filters\expectApplied('wp_stateless_handle_root_dir')
      ->andReturn( 'uploads' );

    Functions\when('ud_get_stateless_media')->justReturn( WPStatelessStub::instance() );
  }
	
  public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

  public static function addHeader($header) {
    self::$headers[] = $header;
  }

  public function testShouldInitHooks() {
    $edd = new EasyDigitalDownloads();

    $edd->module_init([]);

    self::assertNotFalse( has_action('edd_process_download_headers', [ $edd, 'edd_download_method_support' ]) );
    self::assertNotFalse( has_filter('wp_get_attachment_url', [ $edd, 'wp_get_attachment_url' ]) );
    self::assertNotFalse( has_filter('upload_dir', [ $edd, 'upload_dir' ]) );
  }

  public function testShouldSupportDownloadMethod() {
    define('SLCA_RUNNING_TESTS', true);

    $edd = new EasyDigitalDownloads();

    self::$headers = [];

    Functions\when('edd_get_file_download_method')->justReturn( 'direct' );
    Functions\when('edd_is_local_file')->justReturn( false );
    Functions\when('edd_get_file_extension')->justReturn( 'png' );
    Functions\when('edd_get_file_ctype')->justReturn( 'image/png' );

    $edd->edd_download_method_support( self::AVATAR_DST_URL, null, null, null );

    $this->assertEquals( 4, count(self::$headers) );
  }

  public function testShouldGetAttachmentUrl() {
    $edd = new EasyDigitalDownloads();
 
    $GLOBALS['wp_current_filter'] = ['wp_ajax_fes_submit_profile_form'];

    Functions\when('wp_get_attachment_metadata')->justReturn( ['file' => self::AVATAR_FILE] );

    $this->assertEquals(
      self::AVATAR_SRC_URL,
      $edd->wp_get_attachment_url(self::AVATAR_SRC_URL, 15),
    );
  }
   
  public function testShouldHandleUploadsDir() {
    $edd = new EasyDigitalDownloads();
 
    $GLOBALS['wp_current_filter'] = ['wp_ajax_fes_submit_profile_form'];

    Functions\when('wp_get_attachment_metadata')->justReturn( ['file' => self::AVATAR_FILE] );

    $data = $edd->upload_dir(['baseurl', self::UPLOADS_URL]);

    $this->assertTrue(
      array_key_exists('baseurl', $data) && $data['baseurl'] === WPStatelessStub::TEST_GS_HOST . '/uploads'
    );
  }
}

function function_exists() {
  return true;
 }

function file_exists() {
  return true;
 }

function header($header) {
  ClassEasyDigitalDownloadsTest::addHeader($header);
}

function debug_backtrace() {
  return [
    '5' => [
      'function' => 'fes_get_attachment_id_from_url',
    ],
  ];
}
