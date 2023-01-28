<?php

/**
 * @file
 *  Contains Drupal\multistep_form_showcase\Form
 */

namespace Drupal\multistep_form_showcase\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MultistepForm extends FormBase
{

    /**
     * {@inheritDoc}
     */
    public function getFormId()
    {
        return 'multistep_form_showcase';
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        //Set the page for the first time.
        if (!$form_state->has('page')) {
            $form_state->set('page', 1);
        }

        switch ($form_state->get('page')) {
            case 2:
                return $this->buildSecondPage($form, $form_state);
            case 3:
                return $this->buildThirdPage($form, $form_state);
            default:
                return $this->buildFirstPage($form, $form_state);
        }
    }

    /**
     * Validation handler for page 1.
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function formFirstNextValidate(array &$form, FormStateInterface $form_state)
    {
        $birth_year = $form_state->getValue('birth_year');

        if ($birth_year != '' && ($birth_year < 1900 || $birth_year > 2000)) {
            // Set an error for the form element with a key of "birth_year".
            $form_state->setErrorByName('birth_year', $this->t('Enter a year between 1900 and 2000.'));
        }
    }

    /**
     * Submission handler function for page 1.
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function formFirstNextSubmit(array &$form, FormStateInterface $form_state)
    {
        $form_state->set('stored_values', [
            'first_name' => $form_state->getValue('first_name'),
            'last_name' => $form_state->getValue('last_name'),
            'gender' => $form_state->getValue('gender'),
            'other_gender' => $form_state->getValue('other_gender'),
            'birthday' => $form_state->getValue('birthday'),
        ]);
        $form_state->set('page', 2);
        //Set the form to rebuild so the form shows the next page when using Ajax.
        $form_state->setRebuild(TRUE);
    }

    /**
     * Submission handler for back button of the page 2.
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function formSecondPageTwoBack(array &$form, FormStateInterface $form_state)
    {
        //Store the values the user already entered to populate the fields when the user comes back.
        $form_state->setValue('stored_values', array_merge(
            $form_state->getValue('stored_values'),
            [
                'city' => $form_state->getValue('city'),
                'phone' => $form_state->getValue('phone'),
                'address' => $form_state->getValue('address'),
            ]
        ));
        $form_state->setValues($form_state->get('stored_values'));
        $form_state->set('page', 1);
        $form_state->setRebuild(TRUE);
    }

    /**
     * Validation handler for page 2.
     *
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function formSecondNextValidate(array &$form, FormStateInterface $form_state)
    {
    }

    /**
     * Submission handler function for page 2.
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function formSecondNextSubmit(array &$form, FormStateInterface $form_state)
    {
        $form_values = array_merge(
            $form_state->getValues(),
            [
                'city' => $form_state->getValue('city'),
                'phone' => $form_state->getValue('phone'),
                'address' => $form_state->getValue('address'),
            ]
        );

        //TODO create the user with the data

        $form_state->set('page', 3);
        $form_state->setRebuild(TRUE);
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        //Rerturn to the first page
        $form_state->set('page', 1);
        $form_state->setRebuild(TRUE);
    }

    /**
     * Builds the first page of the form.
     *
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *
     * @return array
     *   The render array with the items on this page.
     */
    public function buildFirstPage(array &$form, FormStateInterface $form_state)
    {

        $form['description'] = [
            '#type' => 'item',
            '#title' => $this->t('A multistep form showcase (page 1)'),
        ];

        $form['first_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('First Name'),
            '#description' => $this->t('Enter your first name.'),
            '#default_value' => $form_state->getValue('first_name', ''),
            // '#required' => TRUE,
        ];

        $form['last_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Last Name'),
            '#default_value' => $form_state->getValue('last_name', ''),
            '#description' => $this->t('Enter your last name.'),
            // '#required' => TRUE,
        ];

        $form['gender'] = [
            '#type' => 'radios',
            '#title' => $this->t('Gender'),
            '#default_value' => $form_state->getValue('gender', ''),
            '#options' => [
                'male' => $this->t('Male'),
                'female' => $this->t('Female'),
                'no_respond' => $this->t('Prefer not to respond'),
                'other' => $this->t('Other'),
            ],
            // '#required' => TRUE,
        ];

        $form['other_gender'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Other Gender'),
            '#default_value' => $form_state->getValue('other_gender', ''),
            '#description' => $this->t('Enter the name of your gender'),
            '#states' => [
                // Only show this field when the 'other' radio button is enabled.
                'visible' => [
                    ':input[name="gender"]' => [
                        'value' => 'other',
                    ],
                ],
            ]
        ];

        $form['birthday'] = [
            '#type' => 'date',
            '#title' => $this->t('Date of birth'),
            '#description' => $this->t('Enter your birthday.'),
            // '#required' => TRUE,
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['next'] = [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => $this->t('Next'),
            '#submit' => ['::formFirstNextSubmit'],
            '#validate' => ['::formFirstNextSubmit'],
        ];

        return $form;
    }

    /**
     * Builds the second page of the form.
     *
     * @param array $form
     * @param FormStateInterface $form_state
     *
     * @return array
     *   The render array with the items on this page.
     */
    public function buildSecondPage(array &$form, FormStateInterface $form_state)
    {

        $form['description'] = [
            '#type' => 'item',
            '#title' => $this->t('A multistep form showcase (page 2)'),
        ];

        $form['city'] = [
            '#type' => 'textfield',
            '#title' => $this->t('City'),
            '#default_value' => $form_state->getValue('city', ''),
            // '#required' => TRUE,
        ];

        $form['phone'] = array(
            '#type' => 'tel',
            '#title' => $this->t('Phone number'),
            '#pattern' => '[^\\d]*',
        );

        $form['address'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Address'),
            '#default_value' => $form_state->getValue('address', ''),
        ];

        $form['back'] = [
            '#type' => 'submit',
            '#value' => $this->t('Back'),
            '#submit' => ['::fapiExamplePageTwoBack'],
            // Do not validate the fields since the user must come back to this form.
            '#limit_validation_errors' => [],
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => $this->t('Submit'),
            '#submit' => ['::formSecondNextSubmit'],
            '#validate' => ['::formSecondNextSubmit'],
        ];

        return $form;
    }


    /**
     * Builds the third page of the form.
     *
     * @param array $form
     * @param FormStateInterface $form_state
     *
     * @return array
     *   The render array with the items on this page.
     */
    public function buildThirdPage(array &$form, FormStateInterface $form_state)
    {

        $form['description'] = [
            '#type' => 'item',
            '#title' => $this->t('A multistep form showcase (page 3)'),
        ];

        $form['body'] = [
            '#type' => 'item',
            '#title' => $this->t('Confirm the form'),
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => $this->t('Return'),
        ];

        return $form;
    }

    private function getFormBar($step)
    {
        $build['wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['form-bar-wrapper'],
            ],
        ];

        $build['wrapper']['progressbar'] = [
            '#type' => 'html_tag',
            '#tag' => 'ul',
            '#attributes' => [
                'class' => ['progressbar'],
            ],
        ];

        $build['wrapper']['progressbar']['item1'] = [
            '#type' => 'html_tag',
            '#tag' => 'li',
            '#value' => 'Account',
            '#attributes' => [
                'class' => ['account'],
            ],
        ];

        $build['wrapper']['progressbar']['item2'] = [
            '#type' => 'html_tag',
            '#tag' => 'li',
            '#value' => 'Email',
            '#attributes' => [
                'class' => ['email'],
            ],
        ];

        $build['wrapper']['progressbar']['item3'] = [
            '#type' => 'html_tag',
            '#tag' => 'li',
            '#value' => 'Complete',
            '#attributes' => [
                'class' => ['complete'],
            ],
        ];

        //Add the active class to the current step
        $build['wrapper']['progressbar']['item' . $step]['#attributes']['çlass'][] = 'active';

        return $build;
    }
}
