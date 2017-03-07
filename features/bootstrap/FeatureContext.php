<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class FeatureContext extends MinkContext implements Context, KernelAwareContext
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Sets Kernel instance.
     *
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->container = $kernel->getContainer();
    }

    /**
     * @Given /^I am connected as "([^"]*)" with "([^"]*)"$/
     */
    public function iAmConnectedAs($username, $password)
    {
        $this->visit('/login');
        $this->fillField('_username', $username);
        $this->fillField('_password', $password);
        $this->pressButton('_submit');
    }

    /**
     * @When /^I check the "([^"]*)" radio button$/
     */
    public function iCheckTheRadioButton($labelText)
    {
        foreach ($this->getSession()->getPage()->findAll('css', 'label') as $label) {
            if ($labelText === $label->getText() && $label->has('css', 'input[type="radio"]')) {
                $this->fillField(
                    $label->find('css', 'input[type="radio"]')->getAttribute('name'),
                    $label->find('css', 'input[type="radio"]')->getAttribute('value')
                );

                return;
            }
        }

        throw new \Exception('Radio button not found');
    }

    /**
     * @Then /^I dump page$/
     */
    public function dump()
    {
        var_dump($this->getSession()->getPage()->getContent());
    }

    /**
     * @Then /^I have not access$/
     */
    public function iHaveNotAccess()
    {
        $this->assertResponseStatus(403);
    }

    /**
     * @param string $element
     *
     * @When /^I wait for "(?P<element>[^"]+)" to appear$/
     */
    public function iWaitForToAppear($element)
    {
        $element = addslashes($element);
        for($i=0;$i<30;$i++) {
           if ($this->getSession()->wait(1000, "$('$element').length !== 0") ) {
               break;
           }
        }
    }

    /**
     * @Then I should not see :text in table
     */
    public function iShouldNotSeeInTable($text)
    {
        if(!$this->findRowByText($text) === false){
            return;
        }
        throw new \Exception('Text given is found in a table');
    }

    /**
     * @Then I should see :text in table
     */
    public function iShouldSeeInTable($text)
    {
        if($this->findRowByText($text)){
            return;
        }
        throw new \Exception('Text given is not found in a table');
    }

    /**
     * Clicks link with specified id|title|alt|text in row table
     * Example: When I follow "Log In" in the "heroes_list" row
     * Example: And I follow "Log In" in the "heroes_list" row
     *
     * @When /^(?:|I )follow "(?P<linkText>(?:[^"]|\\")*)" in the "(?P<text>(?:[^"]|\\")*)" row$/
     */
    public function clickLinkInTheRow($linkText, $text)
    {
        $row = $this->findRowByText($text);
        PHPUnit_Framework_Assert::assertNotNull($row, 'Cannot find a table row with this text!');
        $link = $row->findLink($linkText);
        $link->click();
        //$this->getSession()->getPage()->clickLink($link);
    }

    /**
     * @param $text
     * @return NodeElement|mixed|null
     */
    private function findRowByText($text)
    {
        return $this->getSession()->getPage()->find('css', sprintf('tr:contains("%s")', $text));
    }

    /**
     * Take screen-shot when step fails. Works only with Selenium2Driver.
     *
     * @AfterStep
     * @param AfterStepScope $scope
     */
    public function takeScreenshotAfterFailedStep(AfterStepScope $scope)
    {
        $screenshotPath = 'app/cache/behat';

        if (TestResult::FAILED === $scope->getTestResult()->getResultCode()) {
            $driver = $this->getSession()->getDriver();

            if (! is_dir($screenshotPath)) {
                mkdir($screenshotPath, 0777, true);
            }

            // Remove space and special chars
            /** @var \Behat\Gherkin\Node\ScenarioNode $scenario */
            $scenario = current($scope->getFeature()->getScenarios());
            $scenarioTitle = str_replace(' ', '_', $scenario->getTitle());
            $scenarioTitle = str_replace(['"', "'"], '', $scenarioTitle);
            $stepText = str_replace(' ', '_', $scope->getStep()->getText());
            $stepText = str_replace(['"', "'"], '', $stepText);

            if ($driver instanceof Selenium2Driver) {

                $filename = sprintf(
                    '%s_%s_%s.%s',
                    date('md'),
                    $scenarioTitle,
                    $stepText,
                    'png'
                );

                $this->saveScreenshot($filename, $screenshotPath);

            } else {

                $filename = sprintf(
                    '%s_%s_%s.%s',
                    date('md'),
                    $scenarioTitle,
                    $stepText,
                    'html'
                );

                file_put_contents("$screenshotPath/$filename", $driver->getHtml('html'));
            }
        }
    }

    /**
     * Make sure the windows is maximized, otherwise Bootstrap menu may wrap and some elements disappear
     *
     * @BeforeScenario
     */
    public function maximizeWindow()
    {
        // Only Selenium2 supports this
        $driver = $this->getSession()->getDriver();

        if (! $driver instanceof \Behat\Mink\Driver\Selenium2Driver) {
            return;
        }
        $this->getSession()->maximizeWindow();
    }

    /**
     * @Then Element with id :id  should be active
     */
    public function ElementWithIdShouldBeActive($id)
    {
        $element = $this->getSession()->getPage()->findById($id);

        if (null === $element) {
            throw new \Exception('Could not find the element');
        }

        if ($element->hasClass('disabled')) {
            throw new \Exception('Element is not disabled...');
        }
    }

    /**
     * @Then Element with id :id should not be active
     */
    public function ElementWithIdShouldNotBeActive($id)
    {

        $element = $this->getSession()->getPage()->findById($id);

        if (null === $element) {
            throw new \Exception('Could not find the element');
        }

        if (!$element->hasClass('disabled')) {
            throw new \Exception('Element is disabled...');
        }
    }


    /**
     * @Then I fill in select2 input :field with :value
     */
    public function iFillInSelectInputWith($field, $value)
    {
        $page = $this->getSession()->getPage();
        if($this->getSession()->getDriver() instanceof Selenium2Driver) {

            $inputField = $page->find('css', $field);
            if (!$inputField) {
                throw new \Exception('No field found');
            }

            $choice = $inputField->getParent()->find('css', '.select2-input');
            if (!$choice) {
                throw new \Exception('No select2 choice found');
            }
            $choice->press();

            $select2Input = $page->find('css', '.select2-search-field');
            if (!$select2Input) {
                throw new \Exception('No input found');
            }
            $select2Input->setValue($value);
            $this->getSession()->wait(1000);
        }
    }

    /**
     * @Then I select :entry and hover over the element :selector
     */
    public function iSelectAndHoverOverTheElement($entry, $selector)
    {
        $session = $this->getSession(); // get the mink session
        $page = $session->getPage();

        $chosenResults = $page->findAll('css', '.select2-results li');
        foreach ($chosenResults as $result) {
            /** @var \Behat\Mink\Element\NodeElement $result */
            if ($result->getText() == $entry) {
                $element = $result->find('css', $selector);
                if (null === $element) {
                    throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $selector));
                }
                $element->mouseOver();
                $this->getSession()->wait(1000);
                break;
            }
        }
    }

    /**
     * @Then the title :selector should not contains :text
     */
    public function theTitleShouldNotContains($selector, $text)
    {
        $session = $this->getSession(); // get the mink session
        $element = $session->getPage()->find('css', $selector); // runs the actual query and returns the element

        // errors must not pass silently
        if (null === $element) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $selector));
        }
        // errors must not pass silently
        if ($element->getAttribute('title') != null && stripos($element->getAttribute('title'), $text)==false) {
            throw new \Exception($text .' found in the title');
        }
    }

    /**
     * @When the title :selector should contains :text
     */
    public function theTitleShouldContains($selector, $text)
    {
        $session = $this->getSession(); // get the mink session
        $element = $session->getPage()->find('css', $selector); // runs the actual query and returns the element

        // errors must not pass silently
        if (null === $element) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $selector));
        }
        // errors must not pass silently
        if ($element->getAttribute('title') != null &&  stripos($element->getAttribute('title'), $text)!=false) {
            throw new \Exception($text .' not found in the title');
        }

    }

    /**
     * @Given I click the :selector element
     */
    public function iClickTheElement($selector)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', $selector);

        if (empty($element)) {
            throw new Exception("No html element found for the selector ('$selector')");
        }
        $element->click();
    }

    /**
     * @Given I check the :selector element
     */
    public function iCheckTheElement($selector)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', $selector);

        if (empty($element)) {
            throw new Exception("No html element found for the selector ('$selector')");
        }
        $this->getSession()->wait(1000);
        $element->check();
    }

    /**
     * @When /^I click on the "(.*)" button$/
     */
    public function i_click_on_the_button($button) {
        // Simulates the user interaction (see Mink description below for more info)
        $this->getSession()->getPage()->pressButton($button);
    }

    /**
     * @Then /^I should see the css selector "([^"]*)"$/
     */
    public function iShouldSeeTheCssSelector($css_selector) {
        $element = $this->getSession()->getPage()->find("css", $css_selector);
        if (empty($element)) {
            throw new \Exception(sprintf("The page '%s' does not contain the css selector '%s'", $this->getSession()->getCurrentUrl(), $css_selector));
        }
    }

    /**
     * @Then /^I should not see the css selector "([^"]*)"$/
     * @Then /^I should not see the CSS selector "([^"]*)"$/
     */
    public function iShouldNotSeeAElementWithCssSelector($css_selector) {
        $element = $this->getSession()->getPage()->find("css", $css_selector);
        if (empty($element)) {
            throw new \Exception(sprintf("The page '%s' contains the css selector '%s'", $this->getSession()->getCurrentUrl(), $css_selector));
        }
    }

    //Full action for a select2

    /**
     * @When /^(?:|I )fill in select2 input "(?P<field>(?:[^"]|\\")*)" with "(?P<value>(?:[^"]|\\")*)" and select "(?P<entry>(?:[^"]|\\")*)" in "((?:[^"]|\\")*)"$/
     */
    public function fillInSelectInputWithAndSelect($field, $value, $entry, $in)
    {
        $page = $this->getSession()->getPage();
        $inputField = $page->find('css', $field);

        if (!$inputField) {
            throw new \Exception('No field found');
        }

        $choice = $inputField->getParent()->find('css', $field);
        if (!$choice) {
            throw new \Exception('No select2 choice found');
        }

        $choice->press();
        $select2Input = $page->find('css', '.select2-search-field');
        if (!$select2Input) {
            throw new \Exception('No input found');
        }

        $select2Input->setValue($value);
        $this->getSession()->wait(1000);
        $chosenResults = $page->findAll('css', $in);

        foreach ($chosenResults as $result) {
            if ($result->getText() == $entry) {
                $result->click();
                break;
            }
        }
    }
    /**
     * @Then /^I hide the symfony toolbar$/
     */
    public function iHideSymfonyToolbar()
    {
        $page = $this->getSession()->getPage();
        $toolbar = $page ->find('css', '.hide-button');
        if (!$toolbar) {
            throw new \Exception('Symfony toolbar is already hidden');
        }
        $toolbar->click();
    }

    /**
     * @Then /^I show the symfony toolbar$/
     */
    public function iShowSymfonyToolbar()
    {
        $page = $this->getSession()->getPage();
        $toolbar = $page ->find('css', '.sf-minitoolbar');
        if (!$toolbar) {
            throw new \Exception('Symfony toolbar is not already visible');
        }
        $toolbar->click();
    }

    /**
     * @Then /^I should write debug$/
     */
    public function iShouldWriteDebug()
    {
        $page = $this->getSession()->getPage();
        file_put_contents("page.log",$page->getHtml());
    }
}
