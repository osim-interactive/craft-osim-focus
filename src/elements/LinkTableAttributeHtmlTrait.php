<?php
namespace osim\craft\focus\elements;

use Craft;
use craft\helpers\Html;

trait LinkTableAttributeHtmlTrait
{
    protected function linkTableAttributeHtml(
        ?string $url,
        bool $relative = false
    ): string
    {
        if ($url === null) {
            return '';
        }

        $urlText = $url;

        if ($relative) {
            $parts = explode('://', $url, 2);
            if (count($parts) === 2) {
                if ($parts[1] === '') {
                    $urlText = '/';
                } else {
                    $parts = explode('/', $parts[1], 2);
                    $urlText = '/' . $parts[1];
                }
            }
        }

        $find = ['/'];
        $replace = ['/<wbr>'];

        $wordSeparator = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;

        if ($wordSeparator) {
            $find[] = $wordSeparator;
            $replace[] = $wordSeparator . '<wbr>';
        }

        $urlText = str_replace($find, $replace, $urlText);

        return Html::a(Html::tag('span', $urlText, ['dir' => 'ltr']), $url, [
            'href' => $url,
            'rel' => 'noopener',
            'target' => '_blank',
            'class' => 'go',
            'title' => Craft::t('app', 'Visit webpage'),
        ]);
    }
}
