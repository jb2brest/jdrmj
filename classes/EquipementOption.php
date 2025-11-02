<?php

/**
 * Classe EquipementOption - Représente une option d'équipement de départ
 */
class EquipementOption
{
    public $id;
    public $type;
    public $type_id;
    public $type_filter;
    public $nombre; // nb dans la base
    
    /**
     * Constructeur
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = $data['id'] ?? null;
            $this->type = $data['type'] ?? null;
            $this->type_id = $data['type_id'] ?? null;
            $this->type_filter = $data['filter'] ?? ($data['type_filter'] ?? null);
            $this->nombre = $data['nb'] ?? ($data['nombre'] ?? 1);
        }
    }
    
    /**
     * Convertir en tableau
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_id' => $this->type_id,
            'type_filter' => $this->type_filter,
            'nombre' => $this->nombre
        ];
    }
}

