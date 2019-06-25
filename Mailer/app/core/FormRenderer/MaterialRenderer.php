<?php
namespace Remp\MailerModule\Form\Rendering;

use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Forms\Controls;
use Nette\Utils\Html;

class MaterialRenderer extends DefaultFormRenderer
{
    public $wrappers = [
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
     * @param  Form $form
     * @param  string 'begin', 'errors', 'ownerrors', 'body', 'end' or empty to render all $mode
     *
     * @return string
     */
    public function render(Form $form, $mode = null)
    {
        foreach ($form->getControls() as $control) {
            if ($control instanceof Controls\Button) {
                if (strpos($control->getControlPrototype()->getClass(), 'btn') === false) {
                    $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-info' : 'btn btn-default');
                    $usedPrimary = true;
                }
            } elseif ($control instanceof Controls\TextBase ||
                $control instanceof Controls\SelectBox ||
                $control instanceof Controls\MultiSelectBox) {
                $control->getControlPrototype()->addClass('form-control fg-input');
            } elseif ($control instanceof Controls\CheckboxList ||
                $control instanceof Controls\RadioList) {
                $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
            }
        }
        return parent::render($form, $mode);
    }

    public function renderControl(\Nette\Forms\IControl $control)
    {
        if ($control instanceof Nette\Forms\Controls\Checkbox) {
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

    /**
     * Renders single visual row.
     * @return string
     */
    public function renderPair(IControl $control)
    {
        $outer = $pair = $this->getWrapper('pair container');

        $isTextInput = $control instanceof Controls\TextInput;
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

    /**
     * Renders 'label' part of visual row of controls.
     * @return Html
     */
    public function renderMaterialLabel(IControl $control, bool $animatedLabel)
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
