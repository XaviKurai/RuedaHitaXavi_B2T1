<?php
namespace App\Models;
use App\Interfaces\Reservable;

// Representa un DVD reservable i amb duració expressada inicialment en minuts.
class DVD extends Material implements Reservable
{
    private int $duracio;
    private ?string $usuariReserva = null;
    public function __construct(int $id, string $titol, string $autor, int $anyPublicacio, int $duracio)
    { parent::__construct($id, $titol, $autor, $anyPublicacio); $this->setDuracio($duracio); }
    public function calcularMulta(int $diesRetard): float { return max(0, $diesRetard) * 1.00; }
    public function getTipus(): string { return 'DVD'; }
    public function getDuracio(): int { return $this->duracio; }
    // La duració s'ha de mantenir positiva tant en crear com en modificar el DVD.
    public function setDuracio(int $minuts): void { if ($minuts <= 0) throw new \InvalidArgumentException('La duració ha de ser positiva'); $this->duracio = $minuts; }
    // Separem els minuts en hores i minuts, mantenint dos dígits en la segona part.
    public function getDuracioFormatada(): string { return intdiv($this->duracio, 60) . 'h ' . str_pad((string)($this->duracio % 60), 2, '0', STR_PAD_LEFT) . 'min'; }
    // No substituïm una reserva existent d'un altre usuari.
    public function reservar(string $nomUsuari): bool { if ($this->usuariReserva !== null) return false; $this->usuariReserva = $nomUsuari; $this->registrarAccio('reservat', $nomUsuari); return true; }
    // Registrem la cancel·lació només quan hi havia una reserva activa.
    public function cancelarReserva(): bool { if ($this->usuariReserva === null) return false; $this->usuariReserva = null; $this->registrarAccio('reserva_cancel·lada'); return true; }
    public function estaReservat(): bool { return $this->usuariReserva !== null; }
    public function getUsuariReserva(): ?string { return $this->usuariReserva; }
}
