<?php


class DBConnection extends PDO
{
     private $user = 'root';
     private $pass = "password";
     private $host = "mysql";
     private $dbname = "roundcubemail";

     public function __construct(){
     try{
           parent::__construct("mysql:host=".$this->host.";dbname=".$this->dbname.";port=3306","$this->user","$this->pass");
           $this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
           $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
        catch(PDOException $e){
           error_log($e->getMessage(), 3,"/var/tmp/my-errors.log");
           echo $e->getMessage();
        }
    }
}

?>
