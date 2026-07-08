<?php

declare(strict_types=1);

require_once __DIR__ . '/TestSetup.php';
require_once __DIR__ . '/ApiTestHelpers.php';

/**
 * API Test: File Upload Endpoint
 * Tests: /api/upload/
 *
 * Note: These tests validate API behavior without actual file uploads,
 * since move_uploaded_file() requires real HTTP multipart requests.
 */

echo "Testing file upload endpoint...\n";

const DATA_DIR = __DIR__ . '/../public/data';

// Test 1: POST method validation
echo "  - Validate POST method required... ";
$response = ApiTestHelpers::get('/api/upload/', ['path' => '']);
ApiTestHelpers::assertError($response, 405, 'GET method rejected');
echo "OK\n";

// Test 2: Path traversal rejection
echo "  - Reject path traversal... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => '../../../etc',
]);
ApiTestHelpers::assertError($response, 400, 'Path traversal rejected');
echo "OK\n";

// Test 3: Invalid path (null byte)
echo "  - Reject null byte in path... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => "test\x00path",
]);
ApiTestHelpers::assertError($response, 400, 'Null byte rejected');
echo "OK\n";

// Test 4: Missing file parameter
echo "  - Handle missing file... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => '',
]);
ApiTestHelpers::assertError($response, 400, 'Missing file parameter');
echo "OK\n";

// Test 5: Empty path handling
echo "  - Handle empty path (root directory)... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => '',
]);
// Should fail due to missing file, but path should be accepted
ApiTestHelpers::assertError($response, 400, 'Empty path accepted, missing file rejected');
echo "OK\n";

// Test 6: Valid subdirectory path
echo "  - Accept valid subdirectory path... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => 'directory',
]);
// Should fail due to missing file, but path should be accepted
ApiTestHelpers::assertError($response, 400, 'Valid path accepted, missing file rejected');
echo "OK\n";

// Test 7: Non-existent directory path
echo "  - Reject non-existent directory... ";
$response = ApiTestHelpers::post('/api/upload/', [
    'path' => 'nonexistent-directory-12345',
]);
ApiTestHelpers::assertError($response, 400, 'Non-existent directory rejected');
echo "OK\n";

// Test 8: Upload JPEG file (success)
echo "  - Upload JPEG file... ";
$jpegFile = ApiTestHelpers::createTempImage(100, 100, 'jpeg');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $jpegFile]);
ApiTestHelpers::assertSuccess($response, 'JPEG upload');
ApiTestHelpers::assertArrayHasKey('filename', $response['json'], 'Upload response has filename');
$storedName = $response['json']['filename'];
ApiTestHelpers::assertTrue(file_exists(DATA_DIR . '/' . $storedName), 'Stored file exists under returned filename');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $storedName);
echo "OK\n";

// Test 9: Upload PNG file (success)
echo "  - Upload PNG file... ";
$pngFile = ApiTestHelpers::createTempImage(100, 100, 'png');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $pngFile]);
ApiTestHelpers::assertSuccess($response, 'PNG upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 10: Upload PDF file (success — /upload accepts pdf, /upload-images does not)
echo "  - Upload PDF file... ";
$pdfFile = ApiTestHelpers::createTempPdf();
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $pdfFile]);
ApiTestHelpers::assertSuccess($response, 'PDF upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 11: Upload WebP file (success)
echo "  - Upload WebP file... ";
$webpFile = ApiTestHelpers::createTempWebp();
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $webpFile]);
ApiTestHelpers::assertSuccess($response, 'WebP upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 12: Upload MP4 file (success)
echo "  - Upload MP4 file... ";
$mp4File = ApiTestHelpers::createTempVideo('mp4');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $mp4File]);
ApiTestHelpers::assertSuccess($response, 'MP4 upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 13: Upload WebM file (success)
echo "  - Upload WebM file... ";
$webmFile = ApiTestHelpers::createTempVideo('webm');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $webmFile]);
ApiTestHelpers::assertSuccess($response, 'WebM upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 14: Upload MOV file (success)
echo "  - Upload MOV file... ";
$movFile = ApiTestHelpers::createTempVideo('mov');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $movFile]);
ApiTestHelpers::assertSuccess($response, 'MOV upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 15: Upload ZIP file (success)
echo "  - Upload ZIP file... ";
$zipFile = ApiTestHelpers::createTempZip();
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $zipFile]);
ApiTestHelpers::assertSuccess($response, 'ZIP upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 16: Upload MP3 file (success)
echo "  - Upload MP3 file... ";
$mp3File = ApiTestHelpers::createTempAudio('mp3');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $mp3File]);
ApiTestHelpers::assertSuccess($response, 'MP3 upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 17: Upload WAV file (success)
echo "  - Upload WAV file... ";
$wavFile = ApiTestHelpers::createTempAudio('wav');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $wavFile]);
ApiTestHelpers::assertSuccess($response, 'WAV upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 18: Upload OGG file (success)
echo "  - Upload OGG file... ";
$oggFile = ApiTestHelpers::createTempAudio('ogg');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $oggFile]);
ApiTestHelpers::assertSuccess($response, 'OGG upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 19: Upload M4A file (success)
echo "  - Upload M4A file... ";
$m4aFile = ApiTestHelpers::createTempAudio('m4a');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $m4aFile]);
ApiTestHelpers::assertSuccess($response, 'M4A upload');
ApiTestHelpers::registerUploadedFile(DATA_DIR, $response['json']['filename']);
echo "OK\n";

// Test 20: Reject wrong MIME type (text file disguised with .jpg extension)
echo "  - Reject wrong MIME type... ";
$fakeImage = ApiTestHelpers::createTempFile('This is plain text, not an image.', 'jpg');
$response = ApiTestHelpers::postMultipart('/api/upload/', ['path' => ''], ['file' => $fakeImage]);
ApiTestHelpers::assertError($response, 400, 'Wrong MIME type rejected');
echo "OK\n";

echo "All upload endpoint tests passed!\n";
