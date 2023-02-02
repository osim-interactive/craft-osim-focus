<?php
namespace osim\craft\focus\web\assets\overlay;

use craft\web\AssetBundle;

class OverlayAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@osim/craft/focus/web/assets/overlay/dist';

        $this->js = [
            'js/osim-focus.js',
        ];

        $this->css = [
            'css/osim-focus.css',
        ];

        parent::init();
    }
}
