<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\FormRenderer;

use Nette\Forms\Control;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\CheckboxList;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextBase;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Utils\Html;

class MaterialRenderer extends DefaultFormRenderer
{
    public array $wrappers = [
        'form' => [
            'container' => null,
        ],
        'error' => [
            'container' => 'div class="alert alert-danger"',
            'item' => 'p',
        ],
        'group' => [
            'container' => 'fieldset',
            'label' => 'legend',
            'description' => 'p',
        ],
        'controls' => [
            'container' => 'div class="col-sm-6"',
        ],
        'pair' => [
            'container' => 'div class="form-group fg-float m-b-30"',
            'inner-container' => 'div class="fg-line"',
            '.required' => 'required',
            '.optional' => null,
            '.odd' => null,
            '.error' => 'has-error',
        ],
        'control' => [
            'container' => 'div',
            '.odd' => null,
            'description' => 'span class=help-block',
            'requiredsuffix' => '',
            'errorcontainer' => 'span class=help-block',
            'erroritem' => '',
            '.required' => 'required',
            '.text' => 'text',
            '.password' => 'text',
            '.file' => 'text',
            '.submit' => 'button',
            '.image' => 'imagebutton',
            '.button' => 'button',
        ],
        'label' => [
            'container' => null,
            'suffix' => null,
            'requiredsuffix' => '',
        ],
        'hidden' => [
            'container' => 'div',
        ],
    ];

    /**
     * Provides complete form rendering.
     * @param Form $form
     * @param string|null $mode 'begin', 'errors', 'ownerrors', 'body', 'end' or empty to render all
     * @return string
     */
    public function render(Form $form, string $mode = null): string
    {
        foreach ($form->getControls() as $control) {
            if ($control instanceof Button) {
                if ($control->getControlPrototype()->getClass() === null || strpos($control->getControlPrototype()->getClass(), 'btn') === false) {
                    $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-info' : 'btn btn-default');
                    $usedPrimary = true;
                }
            } elseif ($control instanceof TextBase ||
                $control instanceof SelectBox ||
                $control instanceof MultiSelectBox) {
                $control->getControlPrototype()->addClass('form-control fg-input');
            } elseif ($control instanceof CheckboxList ||
                $control instanceof RadioList) {
                $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
            }
        }
        return parent::render($form, $mode);
    }

    public function renderControl(Control $control): Html
    {
        if ($control instanceof Checkbox) {
            $el = Html::el("div", [
                'class' => 'toggle-switch',
                'data-ts-color' => 'cyan',
            ]);
            $el->addHtml($control->getLabelPart()->addClass('ts-label'));
            $el->addHtml($control->getControlPart());
            $el->addHtml('<label for="' . $control->htmlId . '" class="ts-helper"></label>');
            return $el;
        }

        return parent::renderControl($control);
    }

    public function renderPair(Control $control): string
    {
        if (!$control instanceof BaseControl) {
            throw new \Exception('Unable to use MaterialRenderer, control needs to extend Nette\Forms\Controls\BaseControl');
        }
        $outer = $pair = $this->getWrapper('pair container');

        $isTextInput = $control instanceof TextInput;
        if ($isTextInput) {
            $inner = $this->getWrapper('pair inner-container');
            $pair->addHtml($inner);
            $pair = $inner;
        }

        $pair->addHtml($this->renderMaterialLabel($control, $isTextInput));
        $pair->addHtml($this->renderControl($control));

        $pair->class($this->getValue($control->isRequired() ? 'pair .required' : 'pair .optional'), true);
        $pair->class($control->hasErrors() ? $this->getValue('pair .error') : null, true);
        $pair->class($control->getOption('class'), true);
        if (++$this->counter % 2) {
            $pair->class($this->getValue('pair .odd'), true);
        }
        $pair->id = $control->getOption('id');

        return $outer->render(0);
    }

    public function renderMaterialLabel(BaseControl $control, bool $animatedLabel): Html
    {
        $label = $control->getLabel();
        if ($label instanceof Html) {
            if ($control->isRequired()) {
                $label->class($this->getValue('control .required'), true);
            }
            if ($animatedLabel) {
                $label->class('fg-label');
            }
        }
        return $this->getWrapper('label container')->setHtml($label);
    }
}
