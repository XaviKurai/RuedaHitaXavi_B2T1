<?php
namespace App\Models;
use App\Interfaces\Reservable;
use App\Exceptions\MaterialNoTrobatException;
use App\Exceptions\MaterialNoDisponibleException;
use App\Exceptions\MaterialNoPrestatException;
use App\Exceptions\UsuariNoTrobatException;

// Centralitza els materials, usuaris i préstecs i aplica les regles de gestió.
class Biblioteca
{
    // Els materials i usuaris s'indexen per ID per facilitar-ne la cerca directa.
    private array $materials = [];
    private array $usuaris = [];
    private array $prestecs = [];
    public function __construct(private string $nom) {}
    public function afegirMaterial(Material $material): void { $this->materials[$material->getId()] = $material; }
    public function cercarPerId(int $id): ?Material { return $this->materials[$id] ?? null; }
    // Les cerques parcials ignoren majúscules i retornen una llista numerada.
    public function cercarPerTitol(string $titol): array { return array_values(array_filter($this->materials, fn(Material $m) => stripos($m->getTitol(), $titol) !== false)); }
    public function cercarPerAutor(string $autor): array { return array_values(array_filter($this->materials, fn(Material $m) => stripos($m->getAutor(), $autor) !== false)); }
    public function llistarDisponibles(): array { return array_values(array_filter($this->materials, fn(Material $m) => $m->isDisponible())); }
    public function llistarPrestats(): array { return array_values(array_filter($this->materials, fn(Material $m) => !$m->isDisponible())); }
    public function llistarPerTipus(string $tipus): array { return array_values(array_filter($this->materials, fn(Material $m) => strtolower($m->getTipus()) === strtolower($tipus))); }
    public function llistarPrestecsActius(): array { return array_values(array_filter($this->prestecs, fn(Prestec $p) => $p->estaActiu())); }
    public function llistarReserves(): array { return array_values(array_filter($this->materials, fn(Material $m) => $m instanceof Reservable && $m->estaReservat())); }
    public function llistarPrestecsUsuari(int $usuariId): array { return array_values(array_filter($this->prestecs, fn(Prestec $p) => $p->getUsuari()->getId() === $usuariId)); }
    public function afegirUsuari(Usuari $usuari): void { $this->usuaris[$usuari->getId()] = $usuari; }
    // El tipus de l'identificador decideix si es cerca per ID o pel nom exacte.
    public function cercarUsuari(int|string $identificador): ?Usuari
    { foreach ($this->usuaris as $usuari) if (is_int($identificador) ? $usuari->getId() === $identificador : $usuari->getNom() === $identificador) return $usuari; return null; }
    public function prestarMaterial(int $materialId, int $usuariId, ?\DateTime $dataPrestec = null): Prestec
    {
        // Comprovem les dues entitats abans de canviar l'estat de cap objecte.
        $material = $this->cercarPerId($materialId); if ($material === null) throw new MaterialNoTrobatException("No existeix el material #$materialId");
        $usuari = $this->cercarUsuari($usuariId); if ($usuari === null) throw new UsuariNoTrobatException("No existeix l’usuari #$usuariId");
        if (!$material->isDisponible()) throw new MaterialNoDisponibleException($materialId, 'El material ja està prestat');
        // Només qui té la reserva pot emportar-se el material reservat.
        if ($material instanceof Reservable && $material->estaReservat() && $material->getUsuariReserva() !== $usuari->getNom()) throw new MaterialNoDisponibleException($materialId, 'El material està reservat per un altre usuari');
        // Si el reserva el mateix lector, la reserva es consumeix en fer el préstec.
        $material->prestar(); if ($material instanceof Reservable && $material->getUsuariReserva() === $usuari->getNom()) $material->cancelarReserva();
        $prestec = new Prestec($material, $usuari, 14, $dataPrestec); $this->prestecs[] = $prestec; $usuari->afegirPrestec($material); $usuari->afegirNotificacio('Préstec realitzat'); return $prestec;
    }
    public function retornarMaterial(int $materialId): float
    {
        // Tanquem el primer préstec actiu del material i actualitzem també el lector.
        foreach ($this->prestecs as $prestec) if ($prestec->getMaterial()->getId() === $materialId && $prestec->estaActiu()) { $prestec->retornar(); $prestec->getMaterial()->retornar(); $prestec->getUsuari()->eliminarPrestec($materialId); return $prestec->calcularMulta(); }
        throw new MaterialNoPrestatException("El material #$materialId no està prestat");
    }
    public function reservarMaterial(int $materialId, int $usuariId): bool
    {
        $material = $this->cercarPerId($materialId); if ($material === null) throw new MaterialNoTrobatException("No existeix el material #$materialId");
        $usuari = $this->cercarUsuari($usuariId); if ($usuari === null) throw new UsuariNoTrobatException("No existeix l’usuari #$usuariId");
        // instanceof impedeix reservar materials com les revistes.
        if (!$material instanceof Reservable) throw new MaterialNoDisponibleException($materialId, 'Les revistes no es poden reservar');
        if (!$material->reservar($usuari->getNom())) throw new MaterialNoDisponibleException($materialId, 'El material ja està reservat');
        return true;
    }
    public function __call(string $nom, array $arguments): mixed
    {
        // Aquests getters màgics ofereixen llistes de tipus sense duplicar mètodes.
        return match ($nom) {
            'getLlibres' => $this->llistarPerTipus('Llibre'),
            'getRevistes' => $this->llistarPerTipus('Revista'),
            'getDVDs' => $this->llistarPerTipus('DVD'),
            'getMaterials' => array_values($this->materials),
            'getUsuaris' => array_values($this->usuaris),
            'getNom' => $this->nom,
            default => throw new \BadMethodCallException("Mètode inexistent: $nom")
        };
    }
    public function obtenirEstadistiques(): array
    {
        $perTipus = ['Llibre' => count($this->llistarPerTipus('Llibre')), 'Revista' => count($this->llistarPerTipus('Revista')), 'DVD' => count($this->llistarPerTipus('DVD'))];
        // Les multes pendents es calculen només per als préstecs que continuen actius.
        $multes = 0.0; foreach ($this->llistarPrestecsActius() as $prestec) $multes += $prestec->calcularMulta();
        return ['total' => count($this->materials), 'disponibles' => count($this->llistarDisponibles()), 'prestats' => count($this->llistarPrestats()), 'reservats' => count($this->llistarReserves()), 'perTipus' => $perTipus, 'multesPendents' => $multes];
    }
}
