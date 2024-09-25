<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Order\ByBit\OrderFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield ChoiceField::new('side', 'Покупка/продажа')->setChoices(Order\ByBit\Side::cases());
        yield AssociationField::new('coin', 'Монета');
        yield TextField::new('quantity', 'Количество');
        yield TextField::new('cumExecutedQuantity', 'Выполненное количество');
        yield TextField::new('price', 'Цена');
        yield TextField::new('triggerPrice', 'Цена условного приказа');
        yield ChoiceField::new('orderFilter', 'Тип приказа')->setChoices(OrderFilter::cases());
        yield ChoiceField::new('byBitStatus', 'Статус в ByBit')->setChoices(Order\ByBit\Status::cases());
        yield DateTimeField::new('createdAt', 'Дата создания');
        yield DateTimeField::new('updatedAt', 'Дата обновления');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['createdAt' => 'DESC']);
    }
}
