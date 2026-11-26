<?php
namespace App\Models;

// Guarda el període d'un préstec i calcula si hi ha retard o multa.
class Prestec
{
    private \DateTime $dataPrestec;
    private ?\DateTime $dataRetorn = null;
    public function __construct(private Material $material, private Usuari $usuari, private int $diesLimitPrestec = 14, ?\DateTime $dataPrestec = null)
    {
        // Permet fixar una data antiga a les dades de prova; en ús normal s'empra avui.
        $this->dataPrestec = $dataPrestec ?? new \DateTime();
    }
    public function retornar(): void { $this->dataRetorn = new \DateTime(); }
    public function estaActiu(): bool { return $this->dataRetorn === null; }
    public function calcularDiesRetard(): int
    {
        $final = $this->dataRetorn ?? new \DateTime();
        $dies = (int)$this->dataPrestec->diff($final)->format('%r%a') - $this->diesLimitPrestec;
        // Un préstec retornat a temps no pot generar dies de retard negatius.
        return max(0, $dies);
    }
    public function calcularMulta(): float { return $this->material->calcularMulta($this->calcularDiesRetard()); }
    public function estaVencut(): bool { return $this->getDiesPendents() < 0; }
    // Un resultat negatiu indica quants dies fa que s'ha vençut el préstec.
    public function getDiesPendents(): int
    { return $this->diesLimitPrestec - (int)$this->dataPrestec->diff($this->dataRetorn ?? new \DateTime())->format('%r%a'); }
    public function getMaterial(): Material { return $this->material; }
    public function getUsuari(): Usuari { return $this->usuari; }
    public function getDataPrestec(): \DateTime { return $this->dataPrestec; }
    public function getDataRetorn(): ?\DateTime { return $this->dataRetorn; }
    public function getDiesLimitPrestec(): int { return $this->diesLimitPrestec; }
}
