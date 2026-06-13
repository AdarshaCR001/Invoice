<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../htmlPdfConverter.php';

class HtmlPdfConverterTest extends TestCase
{
    public function testFileCreateSanitizesPreName()
    {
        $preName = "../../../etc/passwd";
        $html = "<html><body>Test</body></html>";

        // Prevent fileCreate from actually rendering and writing a pdf
        // Instead, we will simulate it by mocking file_put_contents or we can just let it create the file and assert its path
        // Since we are creating a test file, let's just observe the returned path

        $returnedPath = fileCreate($preName, $html);

        // the returned path should just be passwd + random code + .pdf
        $this->assertStringStartsWith('passwd', $returnedPath);
        $this->assertStringNotContainsString('../', $returnedPath);
        $this->assertStringNotContainsString('etc', $returnedPath);

        // clean up
        if (file_exists($returnedPath)) {
            unlink($returnedPath);
        }
    }

    public function testFileCreateSanitizesSlashes()
    {
        $preName = "valid/name\\here";
        $html = "<html><body>Test</body></html>";

        $returnedPath = fileCreate($preName, $html);

        // basename will reduce "valid/name\here" to "name\here" or "here" depending on OS, then we strip slashes
        // let's just assert no slashes are in the final name
        $this->assertStringNotContainsString('/', $returnedPath);
        $this->assertStringNotContainsString('\\', $returnedPath);

        // clean up
        if (file_exists($returnedPath)) {
            unlink($returnedPath);
        }
    }
}
