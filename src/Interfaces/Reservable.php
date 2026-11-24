<?php
namespace App\Interfaces;

// Defineix les operacions que només ofereixen els materials reservables.
interface Reservable
{
    public function reservar(string $nomUsuari): bool;
    public function cancelarReserva(): bool;
    public function estaReservat(): bool;
    public function getUsuariReserva(): ?string;
}
