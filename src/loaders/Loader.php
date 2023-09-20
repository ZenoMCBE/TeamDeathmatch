<?php

namespace zenogames\loaders;

interface Loader {

    /**
     * @return void
     */
    public function onLoad(): void;

    /**
     * @return void
     */
    public function onUnload(): void;

}
