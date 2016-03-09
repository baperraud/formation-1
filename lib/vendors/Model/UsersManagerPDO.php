<?php
/**
 * Created by PhpStorm.
 * User: alamrous
 * Date: 08/03/2016
 * Time: 11:23
 */

namespace Model;

use Entity\OutsideUser;


class UsersManagerPDO extends UsersManager
{
     public function addUser(OutsideUser $outsideUser)
     {  date_default_timezone_set("Europe/Paris");

         $q=$this->dao->prepare('INSERT INTO t_app_userc SET AUC_login = :login ,AUC_password = :pass, AUC_email = :email,  AUC_state = :state, AUC_dateAdd = NOW()');
         $q->bindValue(':login', $outsideUser->login());
         $q->bindValue(':pass', $outsideUser->password());
         $q->bindValue(':email', $outsideUser->email());
        $q->bindValue('state', $outsideUser->state(), \PDO::PARAM_INT);
         $q->execute();
     }
     public function getUser($login)
     {
         $q=$this->dao->prepare('SELECT AUC_login, AUC_password, AUC_email FROM t_app_userc WHERE AUC_login = :login');
         $q->bindValue(':login', $login);
         $q->execute();
       $q->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, '\Entity\OutsideUser');

         if ($user = $q->fetch())
         {
               return $user;
         }

         return null;
     }


     public function deleteUser($id)
     {
         $this->dao->exec('DELETE FROM t_app_userc WHERE AUC_id = '.(int) $id);

     }


}