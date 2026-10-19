<?php

namespace App\Controllers;

use App\Models\DepartementModel;
use CodeIgniter\RESTful\ResourceController;

class Departement extends ResourceController
{
    protected $modelName = DepartementModel::class;
    protected $format = 'json';

    public function index()
    {
        $model = new DepartementModel();
        $departements = $model->getAll();
        return $this->respond($departements);
    }

    public function create()
    {
        $model = new DepartementModel();
        $data = $this->request->getJSON();
        $id = $model->create((array) $data);
        return $this->respondCreated(['id' => $id]);
    }
}
