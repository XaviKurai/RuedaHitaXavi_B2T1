<?php
namespace App\Exceptions;
use Exception;
// Afegeix l'identificador del material a l'error de disponibilitat.
class MaterialNoDisponibleException extends Exception
{
    private int $materialId;
    // Conservem l'ID per poder identificar el material que ha provocat l'error.
    public function __construct(int $materialId, string $message = 'Material no disponible') { $this->materialId = $materialId; parent::__construct($message); }
    public function getMaterialId(): int { return $this->materialId; }
    // La vista del router converteix aquesta excepció a text amb aquest format.
    public function __toString(): string { return 'Material #' . $this->materialId . ': ' . $this->getMessage(); }
}
