<?php

namespace Shopware\PhpStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AutoIncrementField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BreadcrumbField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ChildCountField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedByField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Computed;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LockedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedByField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\CustomEntity\Schema\DynamicMappingEntityDefinition;

/**
 * @phpstan-type EntityFields array<string, array{required: bool, computed: bool, runtime: bool}>
 * @implements Collector<InClassNode,  array{name: string, entity: string|null, fields: EntityFields}>
 */
class DALDefinitionCollector implements Collector
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        if (!$classReflection->isSubclassOf(EntityDefinition::class)) {
            return null;
        }

        if ($classReflection->is(DynamicMappingEntityDefinition::class)) {
            return null;
        }

        $entityName = $this->getEntityName($node, $scope);

        if ($entityName === null) {
            return null;
        }

        return [
            'name' => $entityName,
            'entity' => $this->getEntityClass($node, $scope),
            'fields' => $this->getFields($node, $scope)
        ];
    }

    private function getFieldName(New_ $newInstance, Scope $scope): string
    {
        $class = $this->reflectionProvider->getClass((string) $newInstance->class);

        if ($class->is(ChildCountField::class)) {
            return 'childCount';
        }

        if ($class->is(AutoIncrementField::class)) {
            return 'autoIncrement';
        }

        if ($class->is(VersionField::class)) {
            return 'versionId';
        }
        ;

        if ($class->is(ParentFkField::class)) {
            return 'parentId';
        }

        if ($class->is(ParentAssociationField::class)) {
            return 'parent';
        }

        if ($class->is(TranslationsAssociationField::class)) {
            return 'translations';
        }

        if ($class->is(LockedField::class)) {
            return 'locked';
        }

        if ($class->is(CustomFields::class)) {
            return 'customFields';
        }

        if ($class->is(CreatedAtField::class)) {
            return 'createdAt';
        }

        if ($class->is(UpdatedAtField::class)) {
            return 'updatedAt';
        }

        if ($class->is(CreatedByField::class)) {
            return 'createdById';
        }

        if ($class->is(UpdatedByField::class)) {
            return 'updatedById';
        }

        if ($class->is(BreadcrumbField::class)) {
            return 'breadcrumb';
        }

        $args = $newInstance->getArgs();

        if ($class->is(ReferenceVersionField::class)) {
            $firstValue = $scope->getType($args[0]->value);

            // @phpstan-ignore-next-line phpstanApi.instanceofType
            if ($firstValue instanceof ConstantStringType) {
                $entityName = $firstValue->getValue();

                if ($firstValue->isClassString()) {
                    $entityName = (new $entityName())->getEntityName();
                }

                $storageName = $entityName . '_version_id';

                $propertyName = explode('_', $storageName);
                $propertyName = array_map('ucfirst', $propertyName);
                $propertyName = lcfirst(implode('', $propertyName));

                return $propertyName;
            } else {
                throw new \RuntimeException('Reference version field must be a constant string');
            }
        }

        if ($class->is(TranslatedField::class) || $class->is(OneToManyAssociationField::class) || $class->is(ManyToOneAssociationField::class) || $class->is(OneToOneAssociationField::class) || $class->is(ManyToManyAssociationField::class)) {
            if ($args[0]->value->value === null) {
                if ($class->is(ChildrenAssociationField::class)) {
                    return 'children';
                }

                throw new \RuntimeException(sprintf("Cannot handle field %s", $class->getName()));
            }

            return $args[0]->value->value;
        }

        if ($args[1]->value->value === null) {
            throw new \RuntimeException(sprintf("Cannot handle field %s", $class->getName()));
        }

        return $args[1]->value->value;
    }

    /**
     * @param EntityFields $fields
     */
    private function handleField(array &$fields, MethodCall|New_ $methodCall, Scope $scope): void
    {
        if ($methodCall instanceof MethodCall) {
            $flags = ['required' => false, 'computed' => false, 'runtime' => false];

            while ($methodCall->var instanceof MethodCall) {
                if ((string) $methodCall->name === 'addFlag') {
                    foreach ($methodCall->getArgs() as $arg) {
                        if ($arg->value instanceof New_) {
                            $className = $arg->value->class->toString();
                            if ($className === Required::class) {
                                $flags['required'] = true;
                            }

                            if ($className === Runtime::class) {
                                $flags['runtime'] = true;
                            }

                            if ($className === Computed::class) {
                                $flags['computed'] = true;
                            }
                        }
                    }
                }

                $methodCall = $methodCall->var;
            }

            $fieldName = $this->getFieldName($methodCall->var, $scope);
            $fields[$fieldName] = $flags;
        }

        if ($methodCall instanceof New_) {
            $fieldName = $this->getFieldName($methodCall, $scope);
            $fields[$fieldName] = ['required' => false, 'computed' => false, 'runtime' => false];
        }
    }

    /**
     * @return EntityFields
     */
    private function getFields(InClassNode $node, Scope $scope): array
    {
        $fields = [];

        foreach ($node->getOriginalNode()->stmts as $method) {
            if ($method instanceof ClassMethod && (string) $method->name === 'defineFields') {
                /** @var New_|null $fieldCollection */
                $fieldCollection = (new NodeFinder())->findFirst($method->stmts, function (Node $node) {
                    return $node instanceof New_ && $node->class instanceof FullyQualified && $node->class->toString() === FieldCollection::class;
                });

                if ($fieldCollection) {
                    $arrayArgument = $fieldCollection->getArgs()[0];

                    if ($arrayArgument->value instanceof Array_) {
                        foreach ($arrayArgument->value->items as $item) {
                            if ($item instanceof ArrayItem) {
                                if ($item->value instanceof MethodCall || $item->value instanceof New_) {
                                    $this->handleField($fields, $item->value, $scope);
                                }
                            }
                        }
                    }
                }

                $calls = (new NodeFinder())->find($method->stmts, function (Node $node) use ($scope) {
                    if ($node instanceof MethodCall && $node->name->toString() === 'add') {
                        $target = $scope->getType($node->var);
                    }

                    return $node instanceof MethodCall && $node->name->toString() === 'add' && $target && $target->accepts(new ObjectType(FieldCollection::class), true)->yes();
                });

                foreach ($calls as $call) {
                    foreach ($call->getArgs() as $arg) {
                        $this->handleField($fields, $arg->value, $scope);
                    }
                }
            }
        }

        return $fields;
    }

    private function getEntityName(InClassNode $node, Scope $scope): ?string
    {
        foreach ($node->getOriginalNode()->stmts as $method) {
            if ($method instanceof ClassMethod && (string) $method->name === 'getEntityName') {
                $returnNode = (new NodeFinder())->findFirstInstanceOf($method->stmts, Return_::class);

                if ($returnNode === null) {
                    continue;
                }

                $entityName = $scope->getType($returnNode->expr);

                if ($entityName instanceof ConstantStringType) {
                    return $entityName->getValue();
                }
            }
        }

        return '';
    }

    private function getEntityClass(InClassNode $node, Scope $scope): ?string
    {
        foreach ($node->getOriginalNode()->stmts as $method) {
            if ($method instanceof ClassMethod && (string) $method->name === 'getEntityClass') {
                $returnNode = (new NodeFinder())->findFirstInstanceOf($method->stmts, Return_::class);

                if ($returnNode === null) {
                    continue;
                }

                $entityName = $scope->getType($returnNode->expr);

                if ($entityName instanceof ConstantStringType) {
                    return $entityName->getValue();
                }
            }
        }

        return '';
    }
}
