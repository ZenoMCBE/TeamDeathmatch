<?php

namespace tdm\utils;

use Closure;

interface Serializable {

    /**
     * @return Closure
     */
    public function deserializer(): Closure;

    /**
     * @return Closure
     */
    public function serialize(): Closure;

}
