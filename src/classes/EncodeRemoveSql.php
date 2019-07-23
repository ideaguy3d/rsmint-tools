<?php
declare(strict_types=1);

namespace Redstone\Tools;

use PDO;

class EncodeRemoveSql
{
    private $dbRSMint_1;
    private $ngid;
    private $removedEncodesTable;
    
    public function __construct(PDO $dbRSMint_1, string $ngid) {
        $this->dbRSMint_1 = $dbRSMint_1;
        $this->ngid = $ngid;
    }
    
    public function getRemovedEncodes(): array {
        $query = "
            SELECT * FROM {$this->removedEncodesTable}
            WHERE [angularjs_id] = ':ngid'
        ";
        
        $statement = $this->dbRSMint_1->prepare($query);
        $statement->bindValue(':ngid', $this->ngid);
        $statement->execute();
        return $statement->fetchAll();
    }
}