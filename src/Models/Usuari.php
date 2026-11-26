<?php
namespace App\Models;
use App\Exceptions\EmailInvalidException;

// Emmagatzema les dades del lector, els seus préstecs i notificacions temporals.
class Usuari
{
    private array $materialsPrestats = [];
    private \DateTime $dataRegistre;
    private array $notificacions = [];
    public function __construct(private int $id, private string $nom, private string $email)
    {
        // L'email es valida en crear el compte i la data queda registrada automàticament.
        $this->validarEmailOrThrow($email);
        $this->dataRegistre = new \DateTime();
    }
    // Exposem només els camps permesos sense fer públiques les propietats.
    public function __get(string $propietat): mixed { return in_array($propietat, ['id','nom','email','dataRegistre'], true) ? $this->$propietat : null; }
    public function __set(string $propietat, mixed $valor): void
    {
        // L'email és l'única propietat modificable i es valida abans de guardar-lo.
        if ($propietat !== 'email') throw new \LogicException('Només es pot modificar l’email');
        $this->validarEmailOrThrow((string)$valor); $this->email = (string)$valor;
    }
    // Les notificacions són temporals i no s'han de persistir dins la sessió.
    public function __sleep(): array { return ['id', 'nom', 'email', 'materialsPrestats', 'dataRegistre']; }
    // Després de recuperar l'objecte, comencem amb una llista de notificacions buida.
    public function __wakeup(): void { $this->notificacions = []; }
    public function __toString(): string { return $this->nom . ' <' . $this->email . '> - ' . count($this->materialsPrestats) . ' préstecs'; }
    public function afegirPrestec(Material $material): void { $this->materialsPrestats[$material->getId()] = $material; }
    public function eliminarPrestec(int $materialId): void { unset($this->materialsPrestats[$materialId]); }
    public function getMaterialsPrestats(): array { return array_values($this->materialsPrestats); }
    public function getNumeroMaterialsPrestats(): int { return count($this->materialsPrestats); }
    public function afegirNotificacio(string $missatge): void { $this->notificacions[] = $missatge; }
    // Les notificacions es consumeixen en llegir-les perquè són missatges d'un sol ús.
    public function obtenirNotificacions(): array { $resultat = $this->notificacions; $this->notificacions = []; return $resultat; }
    private function validarEmail(string $email): bool { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
    private function validarEmailOrThrow(string $email): void { if (!$this->validarEmail($email)) throw new EmailInvalidException('L’email no és vàlid'); }
    public function getId(): int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getEmail(): string { return $this->email; }
}
