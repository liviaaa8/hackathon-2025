<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use http\Params;
use PDO;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();

        return $data ? $this->createExpenseFromData($data) : null;
    }

    public function save(Expense $expense): void
    {
        // TODO: Implement save() method.
        if($expense->id===null){
            $query='INSERT INTO expenses (user_id, date, category, amount_cents, description)
            VALUES (:user_id, :date, :category, :amount_cents, :description)';
            $params= [
                'user_id'=>$expense->userId,
                'date'=>$expense->date->format('Y-m-d'),
                'category'=>$expense->category,
                'amount_cents' =>$expense->amountCents,
                'description'=>$expense->description];
        }else{
            $query='UPDATE expenses SET 
                    date=:date, 
                    category= :category, 
                    amount_cents= :amount_cents, 
                    description= :description 
                    WHERE id= :id AND user_id=:user_id';

            $params=[ 'id'=>$expense->id,
                'user_id'=>$expense->userId,
                'date'=>$expense->date->format('Y-m-d'),
                'category'=>$expense->category,
                'amount_cents'=>$expense->amountCents,
                'description'=>$expense->description];
        }
        $statement=$this->pdo->prepare($query);
        $statement->execute($params);

        if($expense->id===null){
            $expense->id=(int)$this->pdo->lastInsertId();
        }

    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.
        $where = [];
        $parameters = [];

        foreach ($criteria as $key => $value) {
            if ($key === 'year') {
                $where[] = "date >= :year_start AND date < :year_end";
                $parameters['year_start'] = $value . '-01-01';
                $parameters['year_end'] = ($value + 1) . '-01-01';
            } elseif ($key === 'month') {
                $monthPadded = str_pad((string)$value, 2, '0', STR_PAD_LEFT);
                $where[] = "strftime('%m', date) = :month";
                $parameters['month'] = $monthPadded;
            } else {
                $where[] = "$key = :$key";
                $parameters[$key] = $value;
            }
        }

        $query = 'SELECT * FROM expenses';
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        $query .= ' ORDER BY date DESC LIMIT :limit OFFSET :offset';

        $parameters['limit'] = $limit;
        $parameters['offset'] = $from;

        $statement = $this->pdo->prepare($query);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->execute();

        $expenses = [];
        while ($data = $statement->fetch()) {
            $expenses[] = $this->createExpenseFromData($data);
        }
        return $expenses;
    }
    public function countBy(array $criteria): int
    {
        $where=[];
        $params=[];
        // TODO: Implement countBy() method.
        foreach ($criteria as $key => $value){
            if($key==='year'){
                $where[]='strftime("%Y", date) = :year';
                $params['year']=$value;
            }elseif($key==='month'){
                $where[]='strftime("%m", date) = :month';
                $params['month']=str_pad((string)$value, 2, '0', STR_PAD_LEFT);
            }else{
                $where[]="$key= :$key";
                $params[$key]=$value;
            }
        }

        $query= 'SELECT COUNT(*) FROM expenses';
        if(!empty($where)){
            $query.=' WHERE ' .implode(' AND ', $where);
        }
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public function listExpenditureYears(int $userId): array
    {
        $query='SELECT strftime("%Y", date) AS year FROM expenses WHERE user_id = :user_id GROUP BY year ORDER BY year DESC';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['user_id'=>$userId]);
        $years=[];
        while($data = $statement->fetch()){
            $years[]=(int) $data['year'];
        }
        $currentYear=date('Y');
        if(!in_array($currentYear, $years)){
            $years[]=$currentYear;
            sort($years);
        }
        return $years;
        // TODO: Implement listExpenditureYears() method.
    }

    public function getAvailableYears(int $userId): array{
        return $this->listExpenditureYears($userId);
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        $where=[];
        $params=[];
        foreach ($criteria as $key => $value){
            if($key==='year'){
                $where[]='strftime("%Y", date) = :year';
                $params['year']=(string)$value;
            }elseif($key==='month'){
                $monthPadded = str_pad((string)$value, 2, '0', STR_PAD_LEFT);
                $where[]='strftime("%m", date) = :month';
                $params['month']=$monthPadded;
            }else{
                $where[]="$key= :$key";
                $params[$key]=$value;
            }
        }

        $query='SELECT category, SUM(amount_cents) AS total_cents FROM expenses ';
        if(!empty($where)){
            $query.=' WHERE ' .implode(' AND ', $where);
        }
        $query.=' GROUP BY category ORDER BY total_cents DESC';

        $statement = $this->pdo->prepare($query);
        $statement->execute($params);

        $results=[];

        while($data = $statement->fetch()){
            $results[]=['category'=>$data['category'], 'value'=>(float)$data['total_cents'] /100];
        }
        return $results;
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        $where=[];
        $params=[];
        foreach ($criteria as $key => $value){
            if($key==='year'){
                $where[]='strftime("%Y", date) = :year';
                $params['year']=(string)$value;
            }elseif($key==='month'){
                $monthPadded = str_pad((string)$value, 2, '0', STR_PAD_LEFT);
                $where[]='strftime("%m", date) = :month';
                $params['month']=$monthPadded;
            }else{
                $where[]="$key= :$key";
                $params[$key]=$value;
            }
        }

        $query='SELECT category, AVG(amount_cents) AS average_cents FROM expenses ';
        if(!empty($where)){
            $query.=' WHERE ' .implode(' AND ', $where);
        }
        $query.=' GROUP BY category ORDER BY average_cents DESC';

        $statement = $this->pdo->prepare($query);
        $statement->execute($params);

        $results=[];
        while($data = $statement->fetch()){
            $results[]=['category'=>$data['category'], 'value'=>(float)$data['average_cents'] /100];
        }

        return $results;
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
        $where=[];
        $params=[];
        foreach ($criteria as $key => $value){
            if($key==='year'){
                $where[]='strftime("%Y", date) = :year';
                $params['year']=(string)$value;
            }elseif($key==='month'){
                $monthPadded = str_pad((string)$value, 2, '0', STR_PAD_LEFT);
                $where[]='strftime("%m", date) = :month';
                $params['month']=$monthPadded;
            }else{
                $where[]="$key= :$key";
                $params[$key]=$value;
            }
        }

        $query='SELECT SUM(amount_cents) AS total_cents FROM expenses ';
        if(!empty($where)){
            $query.=' WHERE ' .implode(' AND ', $where);
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute($params);

        $result = $statement->fetchColumn();
        return $result ? (float)$result /100 : 0.0;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(array $data): Expense
    {
        return new Expense(
            (int)$data['id'],
            (int)$data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            (int)$data['amount_cents'],
            $data['description'],
        );
    }
}
