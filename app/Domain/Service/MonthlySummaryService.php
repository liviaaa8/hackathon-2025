<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function computeTotalExpenditure(int $userId, int $year, int $month): float
    {
        return $this->expenses->sumAmounts(['user_id'=>$userId, 'year'=>$year, 'month'=>$month]);

        // TODO: compute expenses total for year-month for a given user

    }

    public function computePerCategoryTotals(int $userId, int $year, int $month): array
    {
        // TODO: compute totals for year-month for a given user
        $totals=$this->expenses->sumAmountsByCategory(['user_id'=>$userId, 'year'=>$year, 'month'=>$month]);

        $totalExpenditure=$this->computeTotalExpenditure($userId, $year, $month);
        foreach($totals as &$category){
            $category['percentage']=$totalExpenditure > 0 ? ($category['value']/$totalExpenditure)*100 : 0;
        }
        return $totals;
    }

    public function computePerCategoryAverages(int $userId, int $year, int $month): array
    {
        // TODO: compute averages for year-month for a given user
        $averages=$this->expenses->averageAmountsByCategory(['user_id'=>$userId, 'year'=>$year, 'month'=>$month]);
        $totalExpenditure=$this->computeTotalExpenditure($userId, $year, $month);
        foreach($averages as $category){
            $category['percentage']=$totalExpenditure>0 ? ($category['value']/$totalExpenditure)*100 : 0;
        }
        return $averages;
    }

    public function getAvailableYears(int $userId):array{
        return $this->expenses->getAvailableYears($userId);
    }
}
