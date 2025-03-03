<?php

namespace Shopware\PhpStan\Rule\BestPractise;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Shopware\PhpStan\Collector\DALDefinitionCollector;
use Shopware\PhpStan\Collector\DALEntityCollector;

/**
 * @implements Rule<CollectedDataNode>
 * 
 * @phpstan-import-type Property from DALEntityCollector
 * @phpstan-import-type EntityFields from DALDefinitionCollector
 */
class DALDefinitionRule implements Rule
{
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $definitions = $this->mappedDefinitions($node);
        $entities = $this->mappedEntities($node);

        $errors = [];

        foreach ($definitions as $definition) {
            $entity = $entities[$definition['entity']] ?? null;

            if ($entity == null) {
                continue;
            }

            foreach ($definition['fields'] as $fieldName => $definitionField) {
                if (!isset($entity['properties'][$fieldName])) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf('The field "%s" in the definition "%s" is not defined in the entity "%s".', $fieldName, $definition['name'], $entity['name'])
                    )
                    ->identifier('shopware.bestPractise.dal.propertyMissing')
                        ->line(1)
                        ->file($entity['file'])
                        ->build();
                }

                $field = $entity['properties'][$fieldName];

                if ($field['readonly'] === true) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf('The field "%s" in the definition "%s" is readonly in the entity "%s".', $fieldName, $definition['name'], $entity['name'])
                    )
                        ->identifier('shopware.bestPractise.dal.propertyReadonly')
                        ->line($field['line'])
                        ->file($entity['file'])
                        ->build();
                }

                if ($field['visibility'] === 'private') {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf('The field "%s" in the definition "%s" is private. The EntityHydrator cannot fill private fields', $fieldName, $definition['name'])
                    )
                        ->identifier('shopware.bestPractise.dal.propertyPrivate')
                        ->line($field['line'])
                        ->file($entity['file'])
                        ->build();
                }

                if ($field['visibility'] === 'protected') {
                    $getterMethods = [
                        'get' . ucfirst($fieldName),
                        'is' . ucfirst($fieldName),
                        'has' . ucfirst($fieldName),
                        'has' . (string) preg_replace('/^has/', '', ucfirst($fieldName)),
                    ];

                    $getterExists = array_any($getterMethods, function ($method) use ($entity) {
                        return isset($entity['methods'][$method]);
                    });

                    if (!$getterExists) {
                        $errors[] = RuleErrorBuilder::message(
                            sprintf('The field "%s" in the definition "%s" is protected, but has no getter method', $fieldName, $definition['name'])
                        )
                            ->identifier('shopware.bestPractise.dal.noGetter')
                            ->line($field['line'])
                            ->file($entity['file'])
                            ->build();
                    }

                    if (!$definitionField['runtime'] && !$definitionField['computed']) {
                        if (!isset($entity['methods']['set' . ucfirst($fieldName)])) {
                            $errors[] = RuleErrorBuilder::message(
                                sprintf('The field "%s" in the definition "%s" is protected, but has no setter method', $fieldName, $definition['name'])
                            )
                                ->identifier('shopware.bestPractise.dal.noSetter')
                                ->line($field['line'])
                                ->file($entity['file'])
                                ->build();
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @return array<string, array{file: string, name: string, entity: string, fields: EntityFields}>
     */
    private function mappedDefinitions(CollectedDataNode $collectedDataNode): array
    {
        $definitions = [];

        foreach ($collectedDataNode->get(DALDefinitionCollector::class) as $file => $definition) {
            $definitions[$definition[0]['name']] = $definition[0] + ['file' => $file];
        }

        return $definitions;
    }

    /**
     * @return array<string, array{file: string, name: string, entity: string|null, fields: array<string, array{required: bool, computed: bool, runtime: bool}>}>
     */
    private function mappedEntities(CollectedDataNode $collectedDataNode): array
    {
        $entities = [];

        foreach ($collectedDataNode->get(DALEntityCollector::class) as $file => $entity) {
            $entities[$entity[0]['name']] = $entity[0] + ['file' => $file];
        }

        return $entities;
    }
}
