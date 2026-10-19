<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/** Se intentó eliminar un registro que todavía tiene datos dependientes no eliminados. */
class DeletionBlockedException extends RuntimeException
{
    public function __construct(public readonly Model $model, public readonly string $summary)
    {
        parent::__construct(class_basename($model).' #'.$model->getKey().' tiene datos asociados: '.$summary.'.');
    }
}
