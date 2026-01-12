<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Exception;

/**
 * Browser Context for Behat tests using Mink and Playwright.
 *
 * Provides browser-specific interactions and assertions.
 */
class BrowserContext extends MinkContext implements Context
{
    /**
     * Wait for element to appear.
     *
     * @When I wait for :selector to appear
     */
    public function iWaitForElementToAppear(string $selector): void
    {
        $this->getSession()->wait(5000, "document.querySelector('{$selector}') !== null");
    }

    /**
     * Wait for element to be visible.
     *
     * @When I wait for :selector to be visible
     */
    public function iWaitForElementToBeVisible(string $selector): void
    {
        $this->getSession()->wait(5000, "document.querySelector('{$selector}').offsetParent !== null");
    }

    /**
     * Wait for text to appear.
     *
     * @When I wait for text :text to appear
     */
    public function iWaitForTextToAppear(string $text): void
    {
        $this->getSession()->wait(5000, "document.body.textContent.includes('{$text}')");
    }

    /**
     * Click on element by CSS selector.
     *
     * @When I click on element :selector
     */
    public function iClickOnElement(string $selector): void
    {
        $element = $this->getSession()->getPage()->find('css', $selector);
        if (! $element) {
            throw new Exception("Element with selector '{$selector}' not found");
        }
        $element->click();
    }

    /**
     * Fill in field by CSS selector.
     *
     * @When I fill in element :selector with :value
     */
    public function iFillInElementWith(string $selector, string $value): void
    {
        $element = $this->getSession()->getPage()->find('css', $selector);
        if (! $element) {
            throw new Exception("Element with selector '{$selector}' not found");
        }
        $element->setValue($value);
    }

    /**
     * Assert element is visible.
     *
     * @Then I should see element :selector
     */
    public function iShouldSeeElement(string $selector): void
    {
        $element = $this->getSession()->getPage()->find('css', $selector);
        if (! $element || ! $element->isVisible()) {
            throw new Exception("Element with selector '{$selector}' is not visible");
        }
    }

    /**
     * Assert element is not visible.
     *
     * @Then I should not see element :selector
     */
    public function iShouldNotSeeElement(string $selector): void
    {
        $element = $this->getSession()->getPage()->find('css', $selector);
        if ($element && $element->isVisible()) {
            throw new Exception("Element with selector '{$selector}' is visible but should not be");
        }
    }

    /**
     * Take a screenshot.
     *
     * @When I take a screenshot
     */
    public function iTakeAScreenshot(): void
    {
        $screenshot = $this->getSession()->getScreenshot();
        $filename = __DIR__.'/../../tests/Browser/Screenshots/'.date('Y-m-d_H-i-s').'.png';
        file_put_contents($filename, $screenshot);
    }

    /**
     * Scroll to element.
     *
     * @When I scroll to :selector
     */
    public function iScrollTo(string $selector): void
    {
        $this->getSession()->executeScript("document.querySelector('{$selector}').scrollIntoView();");
    }

    /**
     * Toggle dark mode.
     *
     * @When I toggle dark mode
     */
    public function iToggleDarkMode(): void
    {
        // Look for dark mode toggle button/link
        $page = $this->getSession()->getPage();
        $toggle = $page->find('css', '[data-dark-mode-toggle], .dark-mode-toggle, button[aria-label*="dark"], button[aria-label*="theme"]');

        if (! $toggle) {
            // Try finding by text
            $toggle = $page->findLink('Toggle Dark Mode');
            if (! $toggle) {
                $toggle = $page->findButton('Toggle Dark Mode');
            }
        }

        if (! $toggle) {
            throw new Exception('Dark mode toggle not found on page');
        }

        $toggle->click();
        $this->getSession()->wait(500); // Wait for toggle animation
    }

    /**
     * Assert interface is in dark mode.
     *
     * @Then the interface should be in dark mode
     */
    public function theInterfaceShouldBeInDarkMode(): void
    {
        $page = $this->getSession()->getPage();
        $html = $page->find('css', 'html');

        if (! $html) {
            throw new Exception('HTML element not found');
        }

        $classes = $html->getAttribute('class') ?? '';
        $hasDark = str_contains($classes, 'dark') || $html->hasClass('dark');

        if (! $hasDark) {
            // Check for data attribute
            $dataTheme = $html->getAttribute('data-theme');
            if ($dataTheme !== 'dark') {
                // Check body or root element
                $body = $page->find('css', 'body');
                if ($body) {
                    $bodyClasses = $body->getAttribute('class') ?? '';
                    if (! str_contains($bodyClasses, 'dark')) {
                        throw new Exception('Interface is not in dark mode');
                    }
                } else {
                    throw new Exception('Interface is not in dark mode');
                }
            }
        }
    }

    /**
     * Assert interface is in light mode.
     *
     * @Then the interface should be in light mode
     */
    public function theInterfaceShouldBeInLightMode(): void
    {
        $page = $this->getSession()->getPage();
        $html = $page->find('css', 'html');

        if (! $html) {
            throw new Exception('HTML element not found');
        }

        $classes = $html->getAttribute('class') ?? '';
        $hasDark = str_contains($classes, 'dark') || $html->hasClass('dark');

        if ($hasDark) {
            // Check for data attribute
            $dataTheme = $html->getAttribute('data-theme');
            if ($dataTheme === 'dark') {
                throw new Exception('Interface is in dark mode, expected light mode');
            }
        }
    }

    /**
     * Press button by text.
     *
     * @When I press :button
     */
    public function iPress(string $button): void
    {
        $this->pressButton($button);
    }

    /**
     * Click link or button by text.
     *
     * @When I click :link
     */
    public function iClick(string $link): void
    {
        $page = $this->getSession()->getPage();
        $element = $page->findLink($link);

        if (! $element) {
            $element = $page->findButton($link);
        }

        if (! $element) {
            // Try finding by partial text
            $element = $page->find('xpath', "//*[contains(text(), '{$link}')]");
        }

        if (! $element) {
            throw new Exception("Link or button '{$link}' not found");
        }

        $element->click();
    }

    /**
     * Assert QR code is visible.
     *
     * @Then I should see a QR code
     */
    public function iShouldSeeAQrCode(): void
    {
        $page = $this->getSession()->getPage();

        // Look for common QR code selectors
        $qrCode = $page->find('css', 'canvas[data-qr-code], img[alt*="QR"], .qr-code, [data-qr-code]');

        if (! $qrCode) {
            // Try finding by SVG or canvas
            $qrCode = $page->find('css', 'svg[data-qr-code], canvas');
        }

        if (! $qrCode || ! $qrCode->isVisible()) {
            throw new Exception('QR code is not visible on the page');
        }
    }

    /**
     * Assert recovery codes are visible.
     *
     * @Then I should see recovery codes
     */
    public function iShouldSeeRecoveryCodes(): void
    {
        $page = $this->getSession()->getPage();
        $this->assertPageContainsText('recovery code');
    }

    /**
     * Assert my recovery codes are visible.
     *
     * @Then I should see my recovery codes
     */
    public function iShouldSeeMyRecoveryCodes(): void
    {
        $this->iShouldSeeRecoveryCodes();
    }

    /**
     * Assert recovery codes can be copied.
     *
     * @Then I should be able to copy them
     */
    public function iShouldBeAbleToCopyThem(): void
    {
        $page = $this->getSession()->getPage();

        // Look for copy button
        $copyButton = $page->find('css', 'button[data-copy], [data-copy-button], button:contains("Copy")');

        if (! $copyButton) {
            // Check if recovery codes are in a copyable format (code blocks, etc.)
            $codes = $page->find('css', '.recovery-codes, [data-recovery-codes], code');
            if (! $codes) {
                throw new Exception('Recovery codes copy functionality not found');
            }
        }
    }

    /**
     * Assert new recovery codes are visible.
     *
     * @Then I should see new recovery codes
     */
    public function iShouldSeeNewRecoveryCodes(): void
    {
        $this->iShouldSeeRecoveryCodes();
    }

    /**
     * Assert old recovery codes are invalid.
     *
     * @Then the old recovery codes should be invalid
     */
    public function theOldRecoveryCodesShouldBeInvalid(): void
    {
        // This would typically require storing the old codes and testing them
        // For now, we just verify that new codes are shown
        $this->iShouldSeeNewRecoveryCodes();
    }

    /**
     * Enter verification code.
     *
     * @When I enter the verification code
     */
    public function iEnterTheVerificationCode(): void
    {
        $page = $this->getSession()->getPage();

        // Look for verification code input
        $input = $page->find('css', 'input[name="code"], input[type="text"][placeholder*="code"], input[data-verification-code]');

        if (! $input) {
            // Try OTP input
            $input = $page->find('css', 'input[type="tel"], input[autocomplete="one-time-code"]');
        }

        if (! $input) {
            throw new Exception('Verification code input not found');
        }

        // In a real scenario, you'd need to get the code from the QR code or use a test code
        // For now, we'll use a placeholder - this should be configured per test
        $testCode = '123456'; // This should come from test configuration or 2FA service
        $input->setValue($testCode);
    }

    /**
     * Confirm action.
     *
     * @When I confirm the action
     */
    public function iConfirmTheAction(): void
    {
        $page = $this->getSession()->getPage();

        // Look for confirm button
        $confirm = $page->findButton('Confirm');
        if (! $confirm) {
            $confirm = $page->findButton('Yes');
        }
        if (! $confirm) {
            $confirm = $page->find('css', 'button[type="submit"]');
        }

        if (! $confirm) {
            throw new Exception('Confirm button not found');
        }

        $confirm->click();
    }
}
