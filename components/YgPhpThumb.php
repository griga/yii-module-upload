<?php
/**
 * @author Griga Yura <grigach@gmail.com>
 */

/**
 * Class YgPhpThumb
 */
class YgPhpThumb extends CApplicationComponent
{
    public $options = [];
    public $plugins = [];

    /**
     * @param string $path
     * @param array $options
     * @param array $plugins
     * @return \PHPThumb\GD
     */
    public function create($path, $options = [], $plugins = [])
    {
        return new PHPThumb\GD($path,
            CMap::mergeArray($this->options, $options),
            CMap::mergeArray($this->plugins, $plugins)
        );
    }
}