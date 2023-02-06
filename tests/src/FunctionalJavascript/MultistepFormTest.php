<?php

namespace Drupal\Tests\multistep_form_showcase\FunctionalJavascript;

use Behat\Mink\Element\DocumentElement;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Multistep Form.
 *
 * @group multistep_form_showcase
 */
class MultistepFormTest extends WebDriverTestBase
{

    /**
     * {@inheritdoc}
     */
    protected $defaultTheme = 'bartik';

    /**
     * {@inheritdoc}
     */
    public static $modules = [
        'multistep_form_showcase',
        'user',
        'field',
        'datetime',
    ];

    /**
     * Test The user creation path of the multistep form.
     */
    public function testMultistepForm()
    {
        $assert = $this->assertSession();

        // Check the form loads.
        $this->drupalGet(Url::fromRoute('multistep_form_showcase.form'));
        $page1 = $this->checkPage(1);

        // Fill the first page.
        $page1->fillField('first_name', 'Tom');
        $page1->fillField('last_name', 'Smith');
        $page1->fillField('gender', 'other');
        $page1->fillField('other_gender', 'No-binary');
        $page1->fillField('birthday', '07-11-1990');
        $page1->pressButton('Next');
        $assert->assertWaitOnAjaxRequest();

        // Check if we are on the page 2.
        $page2 = $this->checkPage(2);

        //Fill the second page.
        $page2->fillField('city', 'Huston');
        //Select Us as the country for the phone number.
        $page2->find('css', '.iti__flag-container')->click();
        $page2->find('css', '#iti-0__item-us-preferred')->click();
        $page2->fillField('phone', '2015550123');
        $page2->fillField('address', '10751 Meadowglen Lane Houston, TX 77042 Commons at Westchase');
        $page2->pressButton('Submit');
        $assert->assertWaitOnAjaxRequest();

        // Check if we are on the page 3.
        $page3 = $this->checkPage(3);

        // Check the success user created message.
        $body = $page3->find('css', 'p[data-drupal-selector="edit-body"]');
        $body_text = $body->getHtml();
        $pattern = '/The user \"(\w+)\" was successfully created./';
        $this->assertTrue((bool)preg_match($pattern, $body_text, $matches));

        // Check if the user was created
        $user = user_load_by_name($matches[1]);
        $this->assertEquals('Tom', $user->field_multistep_first_name->value);
        $this->assertEquals('Smith', $user->field_multistep_last_name->value);
        $this->assertEquals('No-binary', $user->field_multistep_gender->value);
        $this->assertEquals('1990-07-11', $user->field_multistep_birthday->value);
        $this->assertEquals('+12015550123', $user->field_multistep_phone->value);
        $this->assertEquals('Huston', $user->field_multistep_city->value);
        $this->assertEquals(
            '10751 Meadowglen Lane Houston, TX 77042 Commons at Westchase',
            $user->field_multistep_address->value
        );

        //Check the return to the first page
        $page3->pressButton('Return');
        $assert->assertWaitOnAjaxRequest();

        // Check we are now in the first page the fields are empty
        $this->checkPage(1);
        $first_name = $page1->findField('first_name')->getValue();
        $this->assertEmpty($first_name);
        $second_name = $page1->findField('last_name')->getValue();
        $this->assertEmpty($second_name);
        $gender = $page1->findField('gender')->getValue();
        $this->assertEquals('Male', $gender);
        $other_gender = $page1->findField('other_gender')->getValue();
        $this->assertEmpty($other_gender);
        $birthday = $page1->findField('birthday')->getValue();
        $this->assertEmpty($birthday);
    }

    /**
     * Test the error messages of the multistep form.
     * @return void
     */
    public function testMultistepFormErrors()
    {
        $assert = $this->assertSession();

        // Check the form loads.
        $this->drupalGet(Url::fromRoute('multistep_form_showcase.form'));
        $page1 = $this->checkPage(1);

        // Fill the gender to check the "Other gender" field error.
        $page1->fillField('gender', 'other');
        $page1->pressButton('Next');
        $assert->assertWaitOnAjaxRequest();

        // Check if we are still on the page 1.
        $page1 = $this->checkPage(1);

        // Check the error messages
        $messages = $page1->find('css', 'ul.messages__list');
        $message_text = $messages->getHtml();
        $this->assertStringContainsString('First Name', $message_text);
        $this->assertStringContainsString('Last Name', $message_text);
        $this->assertStringContainsString('the name of the gender is required', $message_text);
        $this->assertStringContainsString('Date of birth', $message_text);

        // Pass to the secong page.
        $page1->fillField('first_name', 'Tom');
        $page1->fillField('last_name', 'Smith');
        $page1->fillField('gender', 'other');
        $page1->fillField('other_gender', 'No-binary');
        $page1->fillField('birthday', '07-11-1990');
        $page1->pressButton('Next');
        $assert->assertWaitOnAjaxRequest();

        // Check if we are on the page 2.
        $page2 = $this->checkPage(2);

        // Submit an empty city and a bad phone.
        $page2->fillField('phone', '123');
        $page2->pressButton('Submit');
        $assert->assertWaitOnAjaxRequest();

        // Check if we are still on the page 2.
        $page2 = $this->checkPage(2);

        // Check the error messages
        $messages = $page2->find('css', 'ul.messages__list');
        $message_text = $messages->getHtml();
        $this->assertStringContainsString('City field', $message_text);
        $this->assertStringContainsString('The phone number', $message_text);
    }

    /**
     * Test the back bbutoon functionality of the multistep form.
     * @return void
     */
    public function testMultistepFormBack()
    {
        $assert = $this->assertSession();

        // Check the form loads.
        $this->drupalGet(Url::fromRoute('multistep_form_showcase.form'));
        $page1 = $this->checkPage(1);

        // Pass to the secong page.
        $page1->fillField('first_name', 'Tom');
        $page1->fillField('last_name', 'Smith');
        $page1->fillField('gender', 'other');
        $page1->fillField('other_gender', 'No-binary');
        $page1->fillField('birthday', '07-11-1990');
        $page1->pressButton('Next');
        $assert->assertWaitOnAjaxRequest();

        $page2 = $this->checkPage(2);

        // Fill the fields of the second form 
        // to check they are filled out when we are back.
        $page2->fillField('city', 'Huston');
        //Select Us as the country for the phone number.
        $page2->find('css', '.iti__flag-container')->click();
        $page2->find('css', '#iti-0__item-us-preferred')->click();
        $page2->fillField('phone', '2015550123');
        $page2->fillField(
            'address',
            '10751 Meadowglen Lane Houston, TX 77042 Commons at Westchase'
        );

        // Try the back button.
        $page2->pressButton('Back');
        $assert->assertWaitOnAjaxRequest();

        // Check if the form still filled out.
        $page1 = $this->checkPage(1);
        $first_name = $page1->findField('first_name')->getValue();
        $this->assertEquals('Tom', $first_name);
        $second_name = $page1->findField('last_name')->getValue();
        $this->assertEquals('Smith', $second_name);
        $gender = $page1->findField('gender')->getValue();
        $this->assertEquals('other', $gender);
        $other_gender = $page1->findField('other_gender')->getValue();
        $this->assertEquals('No-binary', $other_gender);
        $birthday = $page1->findField('birthday')->getValue();
        $this->assertEquals('1990-07-11', $birthday);

        // Back to the second page.
        $page1->pressButton('Next');
        $assert->assertWaitOnAjaxRequest();

        // Check the form is still filled out.
        $page2 = $this->checkPage(2);
        $city = $page2->findField('city')->getValue();
        $this->assertEquals('Huston', $city);
        // Check selected flag on the phone field.
        $this->assertSession()->elementAttributeContains(
            'css',
            '.iti__selected-flag',
            'aria-activedescendant',
            'iti-1__item-us-preferred'
        );
        $phone = $page2->findField('phone')->getValue();
        $this->assertEquals('(201) 555-0123', $phone);
        $address = $page2->findField('address')->getValue();
        $this->assertEquals(
            '10751 Meadowglen Lane Houston, TX 77042 Commons at Westchase',
            $address
        );
    }

    /**
     * Vefiry we are in the specified page.
     * 
     * @param int $page the numer of the page to check.
     * @return DocumentElement the page element.
     */
    private function checkPage(int $page)
    {
        $html_page = $this->getSession()->getPage();
        $description = $html_page->find('css', '#multistep-form-showcase [id^=edit-description] label');

        switch ($page) {
            case 1:
                $this->assertStringContainsString('Personal information', $description->getText());
                break;
            case 2:
                $this->assertStringContainsString('Contact and location', $description->getText());
                break;
            case 3:
                $this->assertStringContainsString('Complete!', $description->getText());
                break;
        }
        return $html_page;
    }
}
