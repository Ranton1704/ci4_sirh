<?php

namespace App\Controllers;

use App\Models\UtilisateurModel;
use CodeIgniter\RESTful\ResourceController;

class Utilisateur extends ResourceController
{
    protected $modelName = UtilisateurModel::class;
    protected $format = 'json';

    public function index()
    {
        $model = new UtilisateurModel();
        $utilisateurs = $model->getAll();
        return $this->respond($utilisateurs);
    }

    public function create()
    {
        $model = new UtilisateurModel();
        $data = $this->request->getJSON();
        $id = $model->create((array) $data);
        return $this->respondCreated(['id' => $id]);
    }

    public function update($id = null)
    {
        $model = new UtilisateurModel();
        $data = $this->request->getJSON();
        $model->updateUtilisateur($id, (array) $data);
        return $this->respond(['message' => 'Utilisateur mis à jour']);
    }
}
