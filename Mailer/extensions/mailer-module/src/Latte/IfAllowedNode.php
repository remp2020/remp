<?php
declare(strict_types=1);

namespace Remp\MailerModule\Latte;

use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

class IfAllowedNode extends StatementNode
{
    public AreaNode $content;
    public ArrayNode $args;

    public static function create(Tag $tag): \Generator
    {
        $tag->expectArguments();

        $node = new self;
        $node->args = $tag->parser->parseArguments();

        [$node->content, $endTag] = yield;

        return $node;
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'if ($this->global->permissionManager->isAllowed(%node, %node, %node)) { %node }',
            $this->args->items[0]->value,
            $this->args->items[1]->value,
            $this->args->items[2]->value,
            $this->content,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->args;
    }
}
