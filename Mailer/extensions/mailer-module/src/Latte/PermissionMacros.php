<?php
declare(strict_types=1);

namespace Remp\MailerModule\Latte;

use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;

class PermissionMacros extends MacroSet
{
    public static function install(Compiler $compiler): void
    {
        $macros = new self($compiler);
        $macros->addMacro('ifAllowed', [$macros, 'ifAllowed'], '}');
    }

    public function ifAllowed(MacroNode $node, PhpWriter $writer)
    {
        $scope = $node->tokenizer->fetchWord();
        $resource = $node->tokenizer->fetchWord();
        $action = $node->tokenizer->fetchWord();

        if ($node->tokenizer->fetchWord() !== null) {
            throw new \Exception("Macro ifAllowed accepts only 3 arguments.");
        }

        return 'if ($this->global->permissionManager->isAllowed('
            . $scope . ', '
            . $writer->formatWord($resource) . ', '
            . $writer->formatWord($action) . ')) {';
    }
}
