<?php

namespace App\Controllers;

use App\Models\PosteModel;
use CodeIgniter\RESTful\ResourceController;

class Poste extends ResourceController
{
    protected $modelName = PosteModel::class;
    protected $format = 'json';

    public function index()
    {
        $model = new PosteModel();
        $postes = $model->getAll();
        return $this->respond($postes);
    }

    public function create()
    {
        $model = new PosteModel();
        $data = $this->request->getJSON();
        $id = $model->create((array) $data);
        return $this->respondCreated(['id' => $id]);
    }
}
