<?php

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AuditLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AuditLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Audit Log Entry')
            ->setEntityLabelInPlural('Audit Log')
            ->setDefaultSort(['occurredAt' => 'DESC'])
            ->setSearchFields(['action', 'entityType', 'entityId', 'actorIdentifier']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::SAVE_AND_ADD_ANOTHER, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('occurredAtFormatted', 'Occurred At');
        yield TextField::new('action');
        yield TextField::new('entityShortName', 'Entity');
        yield TextField::new('entityType')->hideOnIndex();
        yield TextField::new('entityId');
        yield TextField::new('actorIdentifier')->hideOnForm();
        yield TextEditorField::new('changesPretty', 'Changes')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true)
            ->setNumOfRows(24);
        yield TextEditorField::new('contextPretty', 'Context')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true)
            ->setNumOfRows(8);
    }
}
