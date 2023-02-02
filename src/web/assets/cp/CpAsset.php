<?php
namespace osim\craft\focus\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;

class CpAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = __dir__ . '/dist';

        $this->depends = [
            CraftCpAsset::class,
        ];

        $this->js = [
            'js/osim-focus.js',
        ];

        $this->css = [
            'css/osim-focus.css',
        ];

        parent::init();
    }
}
