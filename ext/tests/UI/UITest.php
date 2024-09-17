<?php

namespace Tests\Custom\Ui;

use Tests\TestCase;

class UITest extends TestCase
{
    protected $browser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->browser = new \App\Kstych\Browser\KChrome();
    }

    public function testLogin()
    {
        $this->browser->visit('http://localhost/');
        $this->browser->runjs(
            '$("#loginusername").val("admin");' .
            '$("#loginpassword").val("yb9738z");' .
            '$(".btn.bg-blue.btn-block.btn-ladda.btn-ladda-progress.ladda-button").click();'
        );

        sleep(2);
        
        // After login, assert that the home page loads
        $this->browser->visit('http://localhost/flighttracking/home');
        sleep(1);

        // Check if the home page loaded by taking a screenshot
        $png = $this->browser->screenshot();
        $this->assertNotEmpty($png, "FAILURE: Unable to load the flight tracking home page");

        // Assert that the track form is visible after login
        $trackForm = $this->browser->findElement('track_form', 'id');
        $this->assertTrue($trackForm->isDisplayed(), "FAILURE: Track form is not visible");
    }
}
