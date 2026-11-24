<?php
namespace App\Traits;

// Comparteix un historial senzill entre tots els tipus de material.
trait Auditoria
{
    private array $historial = [];

    public function registrarAccio(string $accio, string $detalls = ''): void
    {
        // Guardem la data amb el format que es mostra a la vista d'auditoria.
        $this->historial[] = [
            'data' => date('Y-m-d H:i:s'),
            'accio' => $accio,
            'detalls' => $detalls
        ];
    }

    public function obtenirHistorial(): array { return $this->historial; }
    // L'última entrada és l'element final de l'array; si és buit, no hi ha cap acció.
    public function obtenirUltimaAccio(): ?array { return $this->historial ? $this->historial[count($this->historial) - 1] : null; }
    public function netejarHistorial(): void { $this->historial = []; }
}
