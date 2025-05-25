<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class AlertGenerator
{
    // TODO: refactor the array below and make categories and their budgets configurable in .env
    // Hint: store them as JSON encoded in .env variable, inject them manually in a dedicated service,
    // then inject and use use that service wherever you need category/budgets information.
    public function __construct(private readonly MonthlySummaryService $monthlySummaryService){

        $bugetJson=$_ENV['CATEGORY_BUDGETS'] ?? '{"Groceries": 300.00,"Utilities": 200.00,"Transport": 500.00,"Entertainment" : 100.00,"Housing" : 1000.00,"Health" : 100.00,"Other" : 50.00}';
        $this->categoryBugets=json_decode($bugetJson, true) ?? ['Groceries' => 300.00,
            'Utilities' => 200.00,
            'Transport' => 500.00,
            'Entertainment' => 100.00,
            'Housing' => 1000.00,
            'Health' => 100.00,
            'Other' => 50.00
        ];

    }
    private array $categoryBugets;
    public function generate(int $userId, int $year, int $month): array
    {
        // TODO: implement this to generate alerts for overspending by category
        $categoryTotals=$this->monthlySummaryService->computePerCategoryTotals($userId, $year, $month);
        $alerts=[];

        $overBuget=0;

        foreach($this->categoryBugets as $category=>$budget){
            $spent=0;

            foreach($categoryTotals as $categoryData){
                if($categoryData['category']==$category){
                    $spent=$categoryData['value'];
                    break;
                }
            }

            if($spent>$budget){
                $overBuget++;
                $overAmount=$spent-$budget;
                $alerts[]=['type'=>'warning', 'message'=>"You spent $overAmount on $category category, you have $budget budget left."];
            }
        }

        if($overBuget===0){
            $alerts[]=['type'=>'success', 'message'=>'You are on budget!'];
        }
        return $alerts;
    }
}
