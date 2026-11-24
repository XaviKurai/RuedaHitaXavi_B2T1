<?php
namespace App\Exceptions;
use Exception;
// Es llança quan es demana retornar un material sense préstec actiu.
class MaterialNoPrestatException extends Exception {}
