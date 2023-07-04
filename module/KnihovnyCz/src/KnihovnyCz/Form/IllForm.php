<?php

/**
 * Class IllForm
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2023.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  KnihovnyCz\Form
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Form;

use KnihovnyCz\Date\Converter as DateConverter;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Callback;
use Laminas\Validator\Date;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Laminas\View\HelperPluginManager;
use VuFind\Validator\CsrfInterface;

/**
 * Class IllForm
 *
 * @category VuFind
 * @package  KnihovnyCz\Form
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class IllForm extends Form implements InputFilterProviderInterface
{
    /**
     * View helper manager.
     *
     * @var HelperPluginManager
     */
    protected $viewHelperManager;

    /**
     * View helper manager.
     *
     * @var DateConverter
     */
    protected $dateConverter;

    /**
     * CSRF validator
     *
     * @var CsrfInterface
     */
    protected $csrfValidator;

    /**
     * Filter
     *
     * @var array
     */
    protected $filterSpec = [];

    /**
     * Constructor
     *
     * @param HelperPluginManager $viewHelperManager View helper manager
     * @param DateConverter       $dateConverter     Date converter
     * @param CsrfInterface       $csrfValidator     CSRF validator
     */
    public function __construct(
        HelperPluginManager $viewHelperManager,
        DateConverter $dateConverter,
        CsrfInterface $csrfValidator
    ) {
        parent::__construct('illForm', []);
        $this->viewHelperManager = $viewHelperManager;
        $this->dateConverter = $dateConverter;
        $this->csrfValidator = $csrfValidator;
    }

    /**
     * This function is automatically called when creating element with factory. It
     * allows to perform various operations (add elements...)
     *
     * @param array $groups form configuration
     *
     * @return void
     */
    public function init($groups = []): void
    {
        parent::init();
        foreach ($groups as $id => $group) {
            $fields = $group['fields'] ?? [];
            foreach ($fields as $name => $field) {
                $type = $field['type'];
                $element = null;
                if ($type == 'text' || $type == 'date' || $type == 'future_date') {
                    $element = new Text($name);
                } elseif ($type == 'select') {
                    $element = new Select($name);
                    $options = $field['options'];
                    $element->setValueOptions($options);
                } elseif ($type == 'checkbox') {
                    $element = new Checkbox($name);
                } elseif ($type == 'hidden') {
                    $element = new Hidden($name);
                    $element->setValue($field['value'] ?? null);
                } elseif ($type == 'paragraph') {
                    $element = new Hidden($name);
                    $element->setOptions([
                        'header' => $field['header'] ?? null,
                        'text'   => $field['text'] ?? null,
                    ]);
                } else {
                    continue;
                }
                $element->setOption('type', $type);
                $label = $field['label'] ?? null;
                if ($label) {
                    $element->setOption('label', $label);
                }
                $this->add($element);
                $required = $field['required'] ?? false;
                $validators = [];
                if ($required) {
                    $element->setOption('required', true);
                    if ($type == 'checkbox') {
                        $validators[] = [
                            'name' => Identical::class,
                            'options' => [
                                'token' => '1',
                                'message' => $this->translate('required'),
                            ],
                        ];
                    } else {
                        $validators[] = [
                            'name' => NotEmpty::class,
                            'options' => [
                                'type' => 'string',
                                'message' => $this->translate('required'),
                            ],
                        ];
                    }
                }
                if ($type == 'date' || $type == 'future_date') {
                    $validators[] = [
                        'name' => Date::class,
                        'options' => [
                            'format' => $this->dateConverter->getDisplayDateFormat(),
                            'message' => $this->translate('ill_blank_form_invalid_date'),
                        ],
                    ];
                }
                if ($type == 'future_date') {
                    $validators[] = [
                        'name' => Callback::class,
                        'options' => [
                            'message' => 'ill_blank_form_invalid_date_in_the_past',
                            'callback' => function ($value, $context = []) {
                                $now = new \DateTime(date("d-m-Y"));
                                try {
                                    $date = new \DateTime($this->dateConverter
                                        ->convertFromDisplayDate('d-m-Y', $value));
                                } catch (\VuFind\Date\DateException $de) {
                                    return true; // invalid date format is already solved in date validator
                                }
                                $diff = $date->diff($now);
                                return $diff->invert == 1 && $diff->d >= 0;
                            },
                        ],
                    ];
                }
                if (!empty($validators)) {
                    $this->filterSpec[$name] = [
                        'required' => true,
                        'validators' => $validators,
                    ];
                }
            }
        }
        $this->add([
            'name' => 'csrf',
            'type' => Hidden::class,
            'attributes' => [
                'value' => $this->csrfValidator->getHash()
            ],
        ]);
        $this->filterSpec['csrf'] = [
            'required' => true,
            'validators' => [
                [
                    'name' => Callback::class,
                    'options' => [
                        'message' => 'csrf_validation_error',
                        'callback' => function ($value, $context = []) {
                            $valid = $this->csrfValidator->isValid($value, false);
                            // Reset CSRF token if invalid so form
                            // can be submitted again
                            if (!$valid) {
                                $this->get('csrf')
                                    ->setValue($this->csrfValidator->getHash());
                            }
                            return $valid;
                        },
                    ],
                ]
            ]
        ];
        $this->add([
            'name' => 'submit',
            'type' => 'Submit',
            'attributes' => [
                'value' => 'ill_blank_form_submit',
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    /**
     * Validate the form
     *
     * Typically, will proxy to the composed input filter.
     *
     * @return bool
     * @throws \Laminas\Form\Exception\DomainException
     */
    public function isValid(): bool
    {
        $isValid = parent::isValid();
        if ($isValid) {
            // make sure to remove CSRF token so it can't be used again
            $this->csrfValidator->isValid($this->get('csrf')->getValue());
        }
        return $isValid;
    }

    /**
     * Input filter specification for form validation
     *
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return $this->filterSpec;
    }

    /**
     * Get display string.
     *
     * @param string $translationKey Translation key
     *
     * @return string
     */
    protected function translate($translationKey): string
    {
        $helper = $this->viewHelperManager->get('translate');
        return $helper($translationKey);
    }
}
