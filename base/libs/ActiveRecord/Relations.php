<?php
namespace Vendimia\ActiveRecord;

/**
 * Trait for building relations between models.
 */
trait Relations
{
    protected $relations_built = false;

    /**
     * Creates the objects according the fk values
     */
    protected function buildRelations()
    {
        if ($this->relations_built) {
            return;
        }
        $this_class = $this->base_class;

        foreach ($this_class::$relations as $field => $options) {
            $object = null;

            $target_class = $options['rel_class'];

            // Segun el lugar de la llave foránea, creamos el objeto
            if ($options['fk_location'] == self::FK_THIS) {
                // Si existe la llave foranea, y tiene un valor
                if (isset($this->fields[$options['foreing_key']])) {
                    if ($options['rel_type'] == 'one') {
                        $object  = $target_class::get(
                            $this->fields[$options['foreing_key']]
                        );
                    } else {
                        throw new \LogicException ("UNIMPLEMENTED FK_THIS with 'many'");
                    }
                } else {
                    // No existe la llave. Creamos objetos vacíos
                    if ($options['rel_type'] == 'one') {
                        $object = new $target_class;
                    } else {
                        throw new \LogicException ("UNIMPLEMENTED FK_THIS with 'many'");
                    }
                }
            } else {
                // La llave foránea está en el target_class
                if ($options['rel_type'] == 'one') {
                    $object = $target_class::get([
                        $options['foreing_key'] => $this->fields[$options['primary_key']]
                    ]);
                } else {
                    $object = new RecordSet($target_class, 
                        [$options['foreing_key'] => $this->fields[$options['primary_key']]]);
                }


            }
            //var_dump($object); exit;


            $this->object_fields[$field] = $object;
        }
    }
}
