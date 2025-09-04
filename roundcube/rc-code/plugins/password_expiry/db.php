<?php
 
class DaabaseConnection extends PDO{
 
 
        public function __construct(){
 
                try{
                        parent::__construct("mysql:host=172.24.0.3;dbname=roundcubemail;port=3306","root","password");
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
