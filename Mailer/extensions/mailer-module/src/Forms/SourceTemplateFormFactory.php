<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Exception;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\GeneratorFactory;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;

class SourceTemplateFormFactory implements IFormFactory
{
    public $onUpdate;

    public $onSave;

    public function __construct(
        private readonly SourceTemplatesRepository $mailSourceTemplateRepository,
        private readonly GeneratorFactory $mailGeneratorFactory,
        private readonly EngineFactory $engineFactory,
        private readonly SnippetsRepository $snippetsRepository,
    ) {
    }

    public function create(?int $id = null): Form
    {
        $mailTemplate = null;
        $defaults = [];
        if ($id !== null) {
            $mailTemplate = $this->mailSourceTemplateRepository->find($id);
            $defaults = $mailTemplate->toArray();
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addText('code', 'Code')
            ->setRequired("Field 'Code' is required.");

        $items = $this->mailGeneratorFactory->pairs();
        $form->addSelect('generator', 'Generator', $items)
            ->setRequired("Field 'Generator' is required.");

        $orderOptions = [
            'begin' => 'At the beginning',
            'end' => 'At the end',
        ];
        $sortingPairs = $this->mailSourceTemplateRepository->getSortingPairs();
        if (count($sortingPairs) > 0) {
            $orderOptions['after'] = 'After';
        }

        $form->addRadioList('sorting', 'Order', $orderOptions)->setRequired("Field 'Order' is required.");

        if ($mailTemplate !== null) {
            $keys = array_keys($sortingPairs);
            if (reset($keys) === $mailTemplate->sorting) {
                $defaults['sorting'] = 'begin';
                unset($defaults['sorting_after']);
            } elseif (end($keys) === $mailTemplate->sorting) {
                $defaults['sorting'] = 'end';
                unset($defaults['sorting_after']);
            } else {
                $defaults['sorting'] = 'after';
                foreach ($sortingPairs as $sorting => $_) {
                    if ($mailTemplate->sorting <= $sorting) {
                        break;
                    }
                    $defaults['sorting_after'] = $sorting;
                }
            }

            unset($sortingPairs[$mailTemplate->sorting]);
        }

        $form->addSelect('sorting_after', null, $sortingPairs)
            ->setPrompt('Choose newsletter list');

        $form->addTextArea('content_text', 'Text')
            ->setHtmlAttribute('rows', 20)
            ->getControlPrototype()
            ->addAttributes(['class' => 'ace', 'data-lang' => 'text']);

        $form->addTextArea('content_html', 'HTML')
            ->setHtmlAttribute('rows', 60)
            ->getControlPrototype()
            ->addAttributes(['class' => 'ace', 'data-lang' => 'html']);

        $form->setDefaults($defaults);

        $form->addSubmit(self::FORM_ACTION_SAVE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-check"></i> Save');

        $form->addSubmit(self::FORM_ACTION_SAVE_CLOSE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save and close');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        // decide if user wants to save or save and leave
        $buttonSubmitted = self::FORM_ACTION_SAVE;
        /** @var SubmitButton $buttonSaveClose */
        $buttonSaveClose = $form[self::FORM_ACTION_SAVE_CLOSE];
        if ($buttonSaveClose->isSubmittedBy()) {
            $buttonSubmitted = self::FORM_ACTION_SAVE_CLOSE;
        }

        $template = null;
        if (isset($values['id'])) {
            $template = $this->mailSourceTemplateRepository->find($values['id']);
        }

        $templatesSortingPairs = $this->mailSourceTemplateRepository->getSortingPairs();

        try {
            $this->attemptToRender($values['content_html'], 'html');
            $this->attemptToRender($values['content_text'], 'text');
        } catch (Exception $e) {
            $form->addError($e->getMessage());
            return;
        }

        switch ($values['sorting']) {
            case 'begin':
                $first = reset($templatesSortingPairs);
                $values['sorting'] = $first ? array_key_first($templatesSortingPairs) - 1 : 1;
                break;

            case 'after':
                // fix missing form value because of dynamically loading select options
                // in ListPresenter->handleRenderSorting
                if ($values['sorting_after'] === null) {
                    $formHttpData = $form->getHttpData();

                    // + add validation
                    if (empty($formHttpData['sorting_after'])) {
                        $form->addError("Field 'Order' is required.");
                        return;
                    }
                    $values['sorting_after'] = $formHttpData['sorting_after'];
                }

                $values['sorting'] = $values['sorting_after'];

                if (!$template || ($template && $template->sorting > $values['sorting_after'])
                ) {
                    $values['sorting'] += 1;
                }
                break;
            default:
            case 'end':
                $last = end($templatesSortingPairs);
                $values['sorting'] = $last ? array_key_last($templatesSortingPairs) + 1 : 1;
                break;
        }

        $this->mailSourceTemplateRepository->updateSorting(
            $values['sorting'],
            $template->sorting ?? null
        );

        unset($values['sorting_after']);

        if ($template) {
            $this->mailSourceTemplateRepository->update($template, (array) $values);
            $template = $this->mailSourceTemplateRepository->find($template->id);
            $this->onUpdate->__invoke($form, $template, $buttonSubmitted);
        } else {
            $template = $this->mailSourceTemplateRepository->add(
                $values['title'],
                $values['code'],
                $values['generator'],
                $values['content_html'],
                $values['content_text'],
                $values['sorting']
            );

            $this->onSave->__invoke($form, $template, $buttonSubmitted);
        }
    }

    /**
     * @param string $template
     * @param string $version
     * @return void
     * @throws Exception
     */
    private function attemptToRender(string $template, string $version): void
    {
        $engine = $this->engineFactory->engine();
        $generatedTemplate = $engine->render(preg_replace('/{%[^%]+%}/m', '', $template));
        $engine->render($generatedTemplate, ['snippets' => $this->snippetsRepository->all()->fetchPairs('code', $version)]);
    }
}
