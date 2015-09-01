<?php

/*
 * This file is part of the RzSearchBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\SearchBundle\FieldProcessor;

class DateFieldProcessor extends  AbstractFieldProcessor
{
    protected $dateFormat;

    public function __construct($dateFormat = 'Y-m-d H:i:s') {
        $this->dateFormat = $dateFormat;
    }
    public function processFieldIndexValue($entityId, $object, $field, $options = array()) {
        $getter = 'get'.ucfirst($this->configManager->getFieldMap($entityId, $field));
        if($object->$getter() instanceof \DateTime) {
            return $object->$getter()->format($this->dateFormat);
        } else {
            return;
        }
    }
}