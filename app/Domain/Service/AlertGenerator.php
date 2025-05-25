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
    public function __construct(private readonly MonthlySummaryService $SummaryService){

        $budgetJson=$_ENV['CATEGORY_BUDGETS'] ?? '{"Groceries": 300.00,"Utilities": 200.00,"Transport": 500.00,"Entertainment" : 100.00,"Housing" : 1000.00,"Health" : 100.00,"Other" : 50.00}';
        $this->categoryBudgets=json_decode($budgetJson, true) ?? [
            'Groceries' => 300.00,
            'Utilities' => 200.00,
            'Transport' => 500.00,
            'Entertainment' => 100.00,
            'Housing' => 1000.00,
            'Health' => 100.00,
            'Other' => 50.00
        ];

    }
    private array $categoryBudgets;
    public function generate(int $userId, int $year, int $month): array
    {
        // TODO: implement this to generate alerts for overspending by category
        $categoryTotals = $this->SummaryService->computePerCategoryTotals($userId, $year, $month);
        $alerts = [];
        $overBudget = 0;
        $totalSpent = 0;

        foreach ($this->categoryBudgets as $category => $budget) {
            $spent = 0;

            // Find matching category (case-insensitive)
            foreach ($categoryTotals as $categoryData) {
                if (strtolower($categoryData['category']) === strtolower($category)) {
                    $spent = $categoryData['value'];
                    break;
                }
            }

            $totalSpent += $spent;

            if ($spent > $budget) {
                $overBudget++;
                $overAmount = $spent - $budget;
                $alerts[] = [
                    'type' => 'danger',
                    'message' => sprintf(
                        'You spent %.2f on %s, which is more than your budget of %.2f (%.2f over budget)',
                        $spent,
                        strtolower($category),
                        $budget,
                        $overAmount
                    )
                ];
            }
        }

        if ($overBudget === 0 && count($this->categoryBudgets) > 0) {
            $alerts[] = [
                'type' => 'success',
                'message' => sprintf('You are on budget! Total spent: %.2f', $totalSpent)
            ];
        }

        return $alerts;
    }
}
