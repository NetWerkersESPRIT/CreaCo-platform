<?php

namespace App\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<New_>
 */
class NoEntityInstantiationInControllerRule implements Rule
{
    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        /** @var New_ $node */
        if (!$node->class instanceof Name) {
            return [];
        }

        $className = $scope->resolveName($node->class);
        $namespace = $scope->getNamespace();

        if ($namespace !== null && strpos($namespace, 'App\Controller\Collab') === 0) {
            if (strpos($className, 'App\Entity\\') === 0) {
                return [
                    RuleErrorBuilder::message(
                        sprintf('Instantiating entity "%s" directly in a Collaboration controller is discouraged. Use factories or services instead.', $className)
                    )->build(),
                ];
            }
        }

        return [];
    }
}
