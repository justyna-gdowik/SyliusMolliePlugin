<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Refund\Units;

use BitBag\SyliusMolliePlugin\DTO\PartialRefundItem;
use BitBag\SyliusMolliePlugin\DTO\PartialRefundItems;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\RefundPlugin\Model\OrderItemUnitRefund;
use Sylius\RefundPlugin\Model\RefundType;

final class UnitsItemOrderRefund implements UnitsItemOrderRefundInterface
{
    /** @var RepositoryInterface */
    private $refundUnitsRepository;

    public function __construct(RepositoryInterface $refundUnitsRepository)
    {
        $this->refundUnitsRepository = $refundUnitsRepository;
    }

    public function refund(OrderInterface $order, PartialRefundItems $partialRefundItems): array
    {
        $units = $order->getItemUnits();

        $unitsToRefund = [];
        /** @var PartialRefundItem $item */
        foreach ($units as $unit) {
            if (true === $this->hasUnitRefunded($order, $unit->getId())) {
                continue;
            }

            $partialItem = $partialRefundItems->findById($unit->getOrderItem()->getId());
            if (null !== $partialItem) {
                $unitsToRefund[] = new OrderItemUnitRefund(
                    $unit->getId(),
                    $unit->getOrderItem()->getUnitPrice()
                );

                $partialRefundItems->removeItem($partialItem);
            }
        }

        return $unitsToRefund;
    }

    public function getActualRefundedQuantity(OrderInterface $order, $itemId): int
    {
        $allItems = array_filter($this->getActualRefunded($order, $itemId));

        return  count($allItems);
    }

    private function getActualRefunded(OrderInterface $order, int $itemId): array
    {
        $units = $order->getItemUnits();

        foreach ($units as $unit) {
            if ($itemId === $unit->getOrderItem()->getId()) {
                $refundedUnits[] = $this->refundUnitsRepository->findOneBy([
                    'orderNumber' => $order->getNumber(),
                    'refundedUnitId' => $unit->getId(),
                    'type' => RefundType::orderItemUnit(),
                ]);
            }
        }

        return $refundedUnits ?? [];
    }

    private function hasUnitRefunded(OrderInterface $order, int $unitId): bool
    {
        $unitRefunded = $this->refundUnitsRepository->findOneBy([
            'orderNumber' => $order->getNumber(),
            'refundedUnitId' => $unitId,
            'type' => RefundType::orderItemUnit(),
        ]);

        return $unitRefunded ? true : false;
    }
}
