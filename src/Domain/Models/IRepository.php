<?php


namespace App\Domain\Models;


interface IRepository
{
    public function get(int $id):?IModel;
    /**
     * @return IModel[]
    */
    public function getAll():?array ;
    public function add(IModel $model):int;
    public function delete(int $id);
    public function update(IModel $model);
}