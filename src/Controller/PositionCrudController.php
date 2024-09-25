<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Order\ByBit\OrderFilter;
use App\Entity\Position;
use App\Entity\Position\Status;
use Doctrine\Common\Collections\Collection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PositionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Position::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield AssociationField::new('coin', 'Монета');
        yield CollectionField::new('orders', 'Приказы')
            ->formatValue(fn (Collection $collection) => implode('</br>', $collection->map(fn (Order $order) => $order->__toString())->toArray()))
            ->allowAdd(false)
            ->allowDelete(false)
        ;
        yield ChoiceField::new('status', 'Статус')->setChoices(Status::cases());
        yield DateTimeField::new('createdAt', 'Дата создания');
        yield DateTimeField::new('updatedAt', 'Дата обновления');
    }
}
