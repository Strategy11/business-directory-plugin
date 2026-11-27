<?php
/**
 * @package WPBDP\Tests\Plugin
 */

/**
 * Unit tests for Utils class.
 */
class UtilsTest extends \Codeception\Test\Unit {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Files created during a test, removed in tearDown.
	 *
	 * @var string[]
	 */
	private $temp_files = array();

	/**
	 * Remove files created during the test.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}

		$this->temp_files = array();
		parent::tearDown();
	}

	public function testSortByProperty() {
		$data = array(
			'a' => array(
				'order' => 5,
			),
			'b' => array(
				'order' => 2,
			),
			'c' => array(
				'order' => 10,
			),
		);

		WPBDP_Utils::sort_by_property( $data, 'order' );
		$this->tester->wantToTest( 'Utils Sort Order' );
		$this->assertEquals( array( 'b', 'a', 'c' ), array_keys( $data ) );
	}

	/**
	 * A real image passes the image upload check.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	public function testImageUploadAcceptsRealImage() {
		$png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' );

		$error  = '';
		$result = $this->upload_image( 'pixel.png', $png, $error );

		$this->assertIsArray( $result, $error );
		$this->temp_files[] = $result['file'];
		$this->assertSame( 'image/png', $result['type'] );
	}

	/**
	 * Audio files are rejected by image uploads, even though WordPress allows them.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	public function testImageUploadRejectsAudioFile() {
		// ID3v2 tag with a title frame, followed by MPEG-1 Layer III frames.
		$id3   = 'ID3' . "\x03\x00\x00" . "\x00\x00\x00\x15" . 'TIT2' . "\x00\x00\x00\x0B\x00\x00\x00" . 'Song Title';
		$frame = "\xFF\xFB\x90\x64" . str_repeat( "\x00", 413 );
		$mp3   = $id3 . str_repeat( $frame, 10 );

		$error = '';
		$this->assertFalse( $this->upload_image( 'song.mp3', $mp3, $error ) );
		$this->assertNotEmpty( $error );
	}

	/**
	 * A text file with an image extension is rejected.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	public function testImageUploadRejectsTextFileNamedAsImage() {
		$error = '';
		$this->assertFalse( $this->upload_image( 'fake.png', 'not an image', $error ) );
		$this->assertNotEmpty( $error );
	}

	/**
	 * Write a temp file and send it through an image upload, as the image fields do.
	 *
	 * @since x.x
	 *
	 * @param string $name     File name.
	 * @param string $contents File contents.
	 * @param string $error    Receives the error message.
	 *
	 * @return array|false The upload data, or false if rejected.
	 */
	private function upload_image( $name, $contents, &$error ) {
		$path = get_temp_dir() . uniqid( 'wpbdp-' ) . '-' . $name;
		file_put_contents( $path, $contents );
		$this->temp_files[] = $path;

		// Skip the media library so no attachment is created.
		return WPBDP_Utils::media_upload( $path, false, true, array( 'image' => true ), $error );
	}
}
