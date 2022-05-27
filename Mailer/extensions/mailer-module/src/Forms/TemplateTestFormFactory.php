<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\Config\LocalizationConfig;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Models\Sender;

class TemplateTestFormFactory
{
    use SmartObject;

    private TemplatesRepository $templateRepository;

    private Sender $sender;

    private LocalizationConfig $localizationConfig;

    public $onSuccess;

    public function __construct(
        TemplatesRepository $templateRepository,
        Sender $sender,
        LocalizationConfig $localizationConfig
    ) {
        $this->templateRepository = $templateRepository;
        $this->sender = $sender;
        $this->localizationConfig = $localizationConfig;
    }

    public function create(int $id): Form
    {
        $form = new Form;
        $form->addProtection();

        $form->addText('email', 'Email')
            ->addRule(Form::EMAIL)
            ->setRequired("Field 'Email' is required.");

        if ($this->localizationConfig->getSecondaryLocales()) {
            $options = array_combine($this->localizationConfig->getAvailableLocales(), $this->localizationConfig->getAvailableLocales());
            $form->addSelect('locale', 'locale', $options);
            $form->setDefaults(['locale' => $this->localizationConfig->getDefaultLocale()]);
        }

        $form->addSubmit('save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Send');

        $template = $this->templateRepository->find($id);
        $form->addHidden('id', $template->id);

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $template = $this->templateRepository->find($values['id']);
        $sender = $this->sender->setTemplate($template)
            ->addRecipient($values['email']);

        if (isset($values['locale']) && $values['locale']) {
            $sender->setLocale($values['locale']);
        }

        $sender->send(false);

        ($this->onSuccess)($template);
    }
}
