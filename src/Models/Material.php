<?php
namespace App\Models;

use App\Traits\Auditoria;

// Reuneix les dades i el comportament compartit dels materials de la biblioteca.
abstract class Material
{
    // Tots els tipus de material poden registrar préstecs i retorns.
    use Auditoria;
    protected bool $disponible = true;

    public function __construct(
        protected int $id,
        protected string $titol,
        protected string $autor,
        protected int $anyPublicacio
    ) {}

    abstract public function calcularMulta(int $diesRetard): float;
    abstract public function getTipus(): string;

    // Només canvia l'estat i registra l'acció quan el material es pot prestar.
    public function prestar(): bool
    {
        if (!$this->disponible) return false;
        $this->disponible = false;
        $this->registrarAccio('prestat');
        return true;
    }
    public function retornar(): void { $this->disponible = true; $this->registrarAccio('retornat'); }
    // Facilita mostrar les dades principals del material en proves i demos.
    public function __toString(): string
    {
        return sprintf('[%s] #%d - %s (%s, %d) - %s', $this->getTipus(), $this->id, $this->titol, $this->autor, $this->anyPublicacio, $this->disponible ? 'Disponible' : 'Prestat');
    }
    public function getId(): int { return $this->id; }
    public function getTitol(): string { return $this->titol; }
    public function getAutor(): string { return $this->autor; }
    public function getAnyPublicacio(): int { return $this->anyPublicacio; }
    public function isDisponible(): bool { return $this->disponible; }
}
