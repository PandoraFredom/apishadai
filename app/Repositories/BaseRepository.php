<?php
namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected $model;


    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $model = $this->find($id);
        return $model?->update($data);
    }


    public function delete($id)
    {
        $model = $this->find($id);
        return $model?->delete();
    }

    public function with($relations)
    {
        return $this->model->with($relations);
    }
    
    /**
     * Métodos permitidos para las acciones.
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return [];
    }

}
