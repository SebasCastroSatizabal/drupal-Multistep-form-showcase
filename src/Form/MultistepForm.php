<?php

/**
 * @file
 *  Contains Drupal\multistep_form_showcase\Form
 */

namespace Drupal\multistep_form_showcase\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class MultistepForm extends FormBase
{
    /**
     * The max lenght property of several text fields in the form
     * @var int
     */
    private const TEXTFIELD_MAXLENGHT = 32;

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

        //Empty container to display the error messges.
        $form['messages'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'form-errors'
            ],
        ];

        $form['progress'] = $this->buildProgressBar();

        switch ($form_state->get('page')) {
            case 2:
                $form['content'] = $this->buildSecondPage($form, $form_state);
                break;
            case 3:
                $form['content'] = $this->buildThirdPage($form, $form_state);
                break;
            default:
                $form['content'] = $this->buildFirstPage($form, $form_state);
                break;
        }

        //Define al the fields inside a container to be able to replace it with Ajax.
        $form['content']['#type'] = 'container';
        $form['content']['#attributes']['id'] = 'form-content';

        //Attach JS and CSS library
        $form['#attached']['library'][] = 'multistep_form_showcase/global';

        return $form;
    }

    /**
     * Validation handler for page 1.
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function formFirstNextValidate(array &$form, FormStateInterface $form_state)
    {
        $gender = $form_state->getValue('gender');
        if ($gender != '' && $gender === 'other') {
            $other_gender = $form_state->getValue('other_gender');

            if (empty($other_gender)) {
                // Set an error when selecting 'other' gender and not filling the other gender field
                $form_state->setErrorByName(
                    'other_gender',
                    $this->t('When the "Other" gender is selected the name of the gender is required.')
                );
            }
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
        $form_state->set(
            'stored_values',
            array_merge(
                $form_state->get('stored_values') ?? [],
                [
                    'first_name' => $form_state->getValue('first_name'),
                    'last_name' => $form_state->getValue('last_name'),
                    'gender' => $form_state->getValue('gender'),
                    'other_gender' => $form_state->getValue('other_gender'),
                    'birthday' => $form_state->getValue('birthday'),
                ]
            )
        );
        $form_state->setValues($form_state->get('stored_values'));
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
        $form_user_inputs = $form_state->getUserInput();
        $form_state->set('stored_values', array_merge(
            $form_state->get('stored_values') ?? [],
            [
                'city' => $form_user_inputs['city'],
                'phone' => $form_user_inputs['phone'],
                'full_phone' => $form_user_inputs['full_phone'],
                'address' => $form_user_inputs['address'],
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
        //Simple validation for phone number in format E.164
        if (!preg_match("/^\+?[1-9][0-9]{7,14}$/", $form_state->getValue('full_phone'))) {
            $form_state->setErrorByName('phone', 'The phone number is not in the correct format.');
        }
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
            $form_state->get('stored_values') ?? [],
            [
                'city' => $form_state->getValue('city'),
                'phone' => $form_state->getValue('phone'),
                'full_phone' => $form_state->getValue('full_phone'),
                'address' => $form_state->getValue('address'),
            ]
        );

        //Create the user
        $values = $this->mapFormValues($form_values);
        $form_state->set('user_saved', $this->createUserFromFormValues($values));

        $form_state->set('page', 3);
        $form_state->setRebuild(TRUE);
    }

    /**
     * Callback function to handler the ajax behavior of the buttons.
     * 
     * @param array $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse with the commands to be executed by the Drupal Ajax API. 
     */
    public function formAjaxChangePage(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();

        //Dsiplay the form error messages if it has any.
        if ($form_state->hasAnyErrors()) {
            $messages = \Drupal::messenger()->deleteAll();
            $form['messages']['content'] = [
                '#theme'        => 'status_messages',
                '#message_list' => $messages,
            ];
        }

        $response->addCommand(new ReplaceCommand('#form-content', $form['content']));
        $response->addCommand(new ReplaceCommand('#form-errors', $form['messages']));
        $response->addCommand(new InvokeCommand(NULL, 'updateProgressBar', [$form_state->get('page')]));

        return $response;
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
    private function buildFirstPage(array &$form, FormStateInterface $form_state)
    {

        $build['description'] = [
            '#type' => 'item',
            '#title' => $this->t('A multistep form showcase (page 1)'),
        ];

        $build['first_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('First Name'),
            '#description' => $this->t('Enter your first name.'),
            '#default_value' => $form_state->getValue('first_name', ''),
            '#maxlength' => $this::TEXTFIELD_MAXLENGHT,
            // '#required' => TRUE,
        ];

        $build['last_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Last Name'),
            '#default_value' => $form_state->getValue('last_name', ''),
            '#description' => $this->t('Enter your last name.'),
            '#maxlength' => $this::TEXTFIELD_MAXLENGHT,
            // '#required' => TRUE,
        ];

        $build['gender'] = [
            '#type' => 'radios',
            '#title' => $this->t('Gender'),
            '#default_value' => $form_state->getValue('gender', ''),
            '#options' => [
                'Male' => $this->t('Male'),
                'Female' => $this->t('Female'),
                'no_respond' => $this->t('Prefer not to respond'),
                'other' => $this->t('Other'),
            ],
            // '#required' => TRUE,
        ];

        $build['other_gender'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Other Gender'),
            '#default_value' => $form_state->getValue('other_gender', ''),
            '#description' => $this->t('Enter the name of your gender'),
            '#maxlength' => $this::TEXTFIELD_MAXLENGHT,
            '#states' => [
                // Only show this field when the 'other' radio button is enabled.
                'visible' => [
                    ':input[name="gender"]' => [
                        'value' => 'other',
                    ],
                ],
            ]
        ];

        $build['birthday'] = [
            '#type' => 'date',
            '#title' => $this->t('Date of birth'),
            '#description' => $this->t('Enter your birthday.'),
            // '#required' => TRUE,
        ];

        $build['actions'] = [
            '#type' => 'actions',
        ];

        $build['actions']['next'] = [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => $this->t('Next'),
            '#submit' => ['::formFirstNextSubmit'],
            '#validate' => ['::formFirstNextValidate'],
            '#ajax' => [
                'callback' => '::formAjaxChangePage',
                'event' => 'click',
                'progress' => [
                    'type' => 'fullScreen',
                ],
            ],
        ];

        return $build;
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
    private function buildSecondPage(array &$form, FormStateInterface $form_state)
    {

        $build['description'] = [
            '#type' => 'item',
            '#title' => $this->t('A multistep form showcase (page 2)'),
        ];

        $build['city'] = [
            '#type' => 'textfield',
            '#title' => $this->t('City'),
            '#default_value' => $form_state->getValue('city', ''),
            '#description' => $this->t('Enter the city you live in.'),
            // '#required' => TRUE,
        ];

        $build['phone'] = array(
            '#type' => 'tel',
            '#title' => $this->t('Phone number'),
            '#id' => 'phoneNumber',
            '#default_value' => $form_state->getValue('phone', ''),
            '#description' => $this->t('Enter your phone number.'),
            '#attributes' => [
                'id' => 'form-phone',
            ],
        );

        $build['full_phone'] = [
            '#type' => 'hidden',
            '#default_value' => $form_state->getValue('full_phone', ''),
            '#attributes' => [
                'id' => 'form-full-phone',
            ],
        ];

        $build['address'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Address'),
            '#default_value' => $form_state->getValue('address', ''),
            '#description' => $this->t('Enter your address.'),
        ];

        $build['back'] = [
            '#type' => 'submit',
            '#value' => $this->t('Back'),
            '#submit' => ['::formSecondPageTwoBack'],
            // Do not validate the fields since the user must come back to this form.
            '#limit_validation_errors' => [],
            '#ajax' => [
                'callback' => '::formAjaxChangePage',
                'event' => 'click',
                'progress' => [
                    'type' => 'fullScreen',
                ],
            ],
        ];

        $build['submit'] = [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => $this->t('Submit'),
            '#submit' => ['::formSecondNextSubmit'],
            '#validate' => ['::formSecondNextValidate'],
            '#ajax' => [
                'callback' => '::formAjaxChangePage',
                'event' => 'click',
                'progress' => [
                    'type' => 'fullScreen',
                ],
            ],
        ];

        return $build;
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
    private function buildThirdPage(array &$form, FormStateInterface $form_state)
    {

        $build['description'] = [
            '#type' => 'item',
            '#title' => $this->t('A multistep form showcase (page 3)'),
        ];

        $message = $form_state->get('user_saved') ?
            'The user was successfully created.' :
            'There was a error creating the user, please try again.';

        $build['body'] = [
            '#type' => 'item',
            '#title' => $this->t($message),
        ];

        $build['submit'] = [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => $this->t('Return'),
        ];

        return $build;
    }

    /**
     * Builds the progress bar of the form.
     * 
     * @return array render array with teh markup for the progress bar.
     */
    private function buildProgressBar()
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
            '#value' => 'First Page',
            '#attributes' => [
                'class' => [
                    'first-page-item',
                    'active' //Inicial active item.
                ],
            ],
        ];

        $build['wrapper']['progressbar']['item2'] = [
            '#type' => 'html_tag',
            '#tag' => 'li',
            '#value' => 'Second Page',
            '#attributes' => [
                'class' => ['second-page-item'],
            ],
        ];

        $build['wrapper']['progressbar']['item3'] = [
            '#type' => 'html_tag',
            '#tag' => 'li',
            '#value' => 'Complete',
            '#attributes' => [
                'class' => ['third-page-item'],
            ],
        ];

        return $build;
    }

    /**
     * Trim and map the form values to by save in the user fields.
     * 
     * @param array $form_values array with the values passed by the form.
     * @return array array of mapped values.
     */
    private function mapFormValues(array $form_values)
    {
        $values['first_name'] = trim($form_values['first_name']);
        $values['last_name'] = trim($form_values['last_name']);
        $values['city'] = trim($form_values['city']);
        $values['address'] = trim($form_values['address']);
        $values['phone'] = $form_values['full_phone'];
        $values['birthday'] = $form_values['birthday'];

        //Assign gender
        switch ($form_values['gender']) {
            case 'other':
                $values['gender'] = $form_values['other_gender'];
                break;
            case 'no_respond':
                $values['gender'] = '';
                break;
            default:
                $values['gender'] = $form_values['gender'];
        }

        return $values;
    }

    /**
     * Save a new Drupal user with the given values.
     * 
     * @param array $values array with the values to set.
     * @return bool True if the user was successfully created. False otherwise.
     */
    private function createUserFromFormValues(array $values)
    {
        $username = $this->generateUsername('sebas', 'castro');

        $user = User::create();
        $user->setUsername($username);
        $user->set('field_multistep_first_name', $values['first_name']);
        $user->set('field_multistep_last_name', $values['last_name']);
        $user->set('field_multistep_gender', $values['gender']);
        $user->set('field_multistep_birthday', $values['birthday']);
        $user->set('field_multistep_phone', $values['phone']);
        $user->set('field_multistep_address', $values['address']);
        $user->activate();

        try {
            $user->save();
        } catch (EntityStorageException $e) {
            return false;
        }

        return true;
    }

    /**
     * Helper function to create a unique username from the first name and last name.
     * 
     * @param string $first_name first name already sanitized.
     * @param string $last_name last name already sanitized.
     * @return string the username.
     */
    private function generateUsername(string $first_name, string $last_name)
    {
        //Trim and remove spaces
        $first_name = trim($first_name);
        $first_name = preg_replace('/[ ]+/', ' ', $first_name);
        $last_name = trim($last_name);
        $last_name = preg_replace('/[ ]+/', '', $last_name);

        //Combine the first name and last name to get the username
        $base_username = substr($first_name, 0, 1) . $last_name;
        $i = 0;

        //Check if a user with that username already exists and generate a unique one.
        do {
            $username = ($i == 0) ? $base_username : $base_username . $i;
            $user_exists = user_load_by_name($username);
            $i++;
        } while ($user_exists);

        return $username;
    }
}
