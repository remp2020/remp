<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\InvalidStateException;
use Remp\MailerModule\Models\FormRenderer\MaterialRenderer;
use Remp\MailerModule\Models\Generators\GeneratorFactory;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;

class MailGeneratorFormFactory
{
    private bool $allowCrossOrigin = false;

    public function __construct(
        private readonly SourceTemplatesRepository $sourceTemplatesRepository,
        private readonly GeneratorFactory $mailGeneratorFactory
    ) {
    }

    /**
     * You could theoretically use external system (CMS) to generate the data and let people submit this form.
     * If CMS is running on separate domain, cross-origin check would block this submission if it's not allowed.
     */
    public function allowCrossOrigin(): void
    {
        $this->allowCrossOrigin = true;
    }

    public function create($sourceTemplateId, callable $onSubmit, callable $link = null)
    {
        $form = new Form;
        $form->setRenderer(new MaterialRenderer());
        $form->addProtection();

        if ($this->allowCrossOrigin) {
            $form->allowCrossOrigin();
        }

        $keys = $this->mailGeneratorFactory->keys();
        $pairs = $this->sourceTemplatesRepository->all()
            ->where(['generator' => $keys])
            ->fetchPairs('id', 'title');

        $form->addSelect('source_template_id', 'Generator', $pairs)
            ->setRequired("Field 'Generator' is required.")
            ->setHtmlAttribute('class', 'form-control selectpicker')
            ->setHtmlAttribute('data-live-search', 'true')
            ->setHtmlAttribute('data-live-search-normalize', 'true');

        $generator = $template = null;
        if ($sourceTemplateId) {
            $template = $this->sourceTemplatesRepository->find($sourceTemplateId);
            $generator = $template->generator;
        } else {
            $tmpl = $this->sourceTemplatesRepository->all()
                ->fetch();
            if ($tmpl) {
                $template = $tmpl;
                $generator = $tmpl->generator;
            }
        }

        if ($generator && $template) {
            $formGenerator = $this->mailGeneratorFactory->get($generator);
            $formGenerator->generateForm($form);
            $formGenerator->onSubmit($onSubmit);
        }

        try {
            $form->addSubmit('send')
                ->getControlPrototype()
                ->setName('button')
                ->setHtml('<i class="fa fa-cogs"></i> Generate');
        } catch (InvalidStateException $e) {
            // this is fine, submit was added by the generator
        }

        $form->setDefaults([
            'source_template_id' => $template->id ?? null,
        ]);
        return $form;
    }
}
