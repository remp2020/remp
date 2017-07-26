<?php
namespace Remp\MailerModule\Form\Rendering;

use Nette\Forms\Form;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Forms\Controls;

class MaterialRenderer extends DefaultFormRenderer
{
    public $wrappers = array(
        'form' => array(
            'container' => null,
        ),
        'error' => array(
            'container' => 'div class="alert alert-danger"',
            'item' => 'p',
        ),
        'group' => array(
            'container' => 'fieldset',
            'label' => 'legend',
            'description' => 'p',
        ),
        'controls' => array(
            'container' => 'div',
        ),
        'pair' => array(
            'container' => 'div class="form-group fg-float m-b-30"',
            '.required' => 'required',
            '.optional' => null,
            '.odd' => null,
            '.error' => 'has-error',
        ),
        'control' => array(
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
        ),
        'label' => array(
            'container' => 'div class=fg-label',
            'suffix' => null,
            'requiredsuffix' => '',
        ),
        'hidden' => array(
            'container' => 'div',
        ),
    );
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
            if ($control instanceof  Controls\Button) {
                if (strpos($control->getControlPrototype()->getClass(), 'btn') === false) {
                    $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
                    $usedPrimary = true;
                }
            } elseif ($control instanceof Controls\TextBase ||
                $control instanceof Controls\SelectBox ||
                $control instanceof Controls\MultiSelectBox) {
                $control->getControlPrototype()->addClass('form-control fg-input');
            } elseif ($control instanceof Controls\Checkbox ||
                $control instanceof Controls\CheckboxList ||
                $control instanceof Controls\RadioList) {
                $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
            }
        }
        return parent::render($form, $mode);
    }
}
