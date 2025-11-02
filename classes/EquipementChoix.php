<?php

/**
 * Classe EquipementChoix - Représente un choix d'équipement de départ
 */
class EquipementChoix
{
    public $id;
    public $numero_choix; // no_choix dans la base
    public $options; // Array de EquipementOption
    
    /**
     * Constructeur
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = $data['id'] ?? null;
            $this->numero_choix = $data['no_choix'] ?? ($data['numero_choix'] ?? 0);
            
            // Convertir les options en objets EquipementOption
            $this->options = [];
            if (isset($data['options']) && is_array($data['options'])) {
                foreach ($data['options'] as $optionData) {
                    if ($optionData instanceof EquipementOption) {
                        $this->options[] = $optionData;
                    } else {
                        $this->options[] = new EquipementOption($optionData);
                    }
                }
            }
        }
    }
    
    /**
     * Ajouter une option
     */
    public function addOption(EquipementOption $option)
    {
        $this->options[] = $option;
    }
    
    /**
     * Convertir en tableau
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'numero_choix' => $this->numero_choix,
            'options' => array_map(function($option) {
                return $option instanceof EquipementOption ? $option->toArray() : $option;
            }, $this->options)
        ];
    }
}

