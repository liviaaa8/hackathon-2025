<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(int $userId, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        $criteria=['user_id'=>$userId, 'year'=>$year, 'month'=>$month];
        return $this->expenses->findBy($criteria, ($pageNumber - 1)*$pageSize, $pageSize);

        // TODO: implement this and call from controller to obtain paginated list of expenses
    }
    public function countExpenses( int $userId, int $year, int $month):int{
        return $this->expenses->countBy([
           'user_id'=>$userId,
           'year'=>$year,
            'month'=>$month
        ]);
    }
    public function getAvailableYears(int $userId):array
    {
        return $this->expenses->listExpenditureYears($userId);
    }

    public function findById(int $id): ?Expense{
        return $this->expenses->find($id);
    }
    public function create(
        int $userId,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to create a new expense entity, perform validation, and persist
         $this->validateExpenseData($amount, $description, $date, $category);
        // TODO: here is a code sample to start with
        $expense = new Expense(null, $userId, $date, $category, $amount*100, $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
        $this->validateExpenseData($amount, $description, $date, $category);

        $expense->date = $date;
        $expense->category = $category;
        $expense->amountCents = (int)($amount * 100);
        $expense->description = $description;

        $this->expenses->save($expense);
    }

    public function delete(int $id):void{
        $this->expenses->delete($id);
    }

    private function validateExpenseData(
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category
    ): void {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Introduce a valid amount');
        }

        if (empty($description)) {
            throw new InvalidArgumentException('Write a description!');
        }

        if ($date > new DateTimeImmutable()) {
            throw new InvalidArgumentException('Introduce the current date.');
        }

        if (empty($category)) {
            throw new InvalidArgumentException('Select a category');
        }
    }
    public function importFromCsv(int $userId, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        return 0;
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails
        // number of imported rows
    }

}
