<?php
declare(strict_types=1);

namespace Redstone\Tools;

use PDO;

class ComAutoSqlServerModel
{
    private $dbComAuto;
    private $log;
    
    public function __construct(PDO $dbComAuto, $log) {
        $this->dbComAuto = $dbComAuto;
        $this->log = $log;
    }
    
    /**
     * this function will invoke the stored procedure that groups
     * all the jobs by month so this data can be seen in the "overview"
     * homepage of the ComAuto app
     */
    public function getJobMyMonthCount(): array {
        $query = "EXEC simple_one";
        
        try {
            $statement = $this->dbComAuto->prepare($query);
            $statement->execute();
            $resultSet = $statement->fetchAll();
        }
        catch(\Exception $e) {
            $message = $e->getMessage();
            return ['error' => "EXCEPTION: $message"];
        }
        
        return $resultSet;
    }
}