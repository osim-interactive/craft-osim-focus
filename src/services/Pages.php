<?php
namespace osim\craft\focus\services;

use Craft;
use craft\db\Query;
use osim\craft\focus\elements\Page as PageElement;
use yii\base\Component;

class Pages extends Component
{
    public function getPageById(int $pageId): PageElement
    {
        return PageElement::find()
            ->id($pageId)
            ->one();
    }

    public function getPageByUrl(
        int $projectId,
        string $pageUrl,
        bool $restoreTrashed = false
    ): PageElement {
        $element = PageElement::find()
            ->projectId($projectId)
            ->pageUrl($pageUrl)
            ->one();

        if ($element) {
            return $element;
        }

        // Check for trashed element and restore
        if ($restoreTrashed) {
            $element = PageElement::find()
                ->projectId($projectId)
                ->pageUrl($pageUrl)
                ->trashed(true)
                ->one();

            if ($element) {
                Craft::$app->getElements()->restoreElement($element);

                return $element;
            }
        }

        $element = new PageElement();
        $element->projectId = $projectId;
        $element->pageUrl = $pageUrl;

        return $element;
    }

    public function deletePageById(int $pageId): bool
    {
        $pageElement = $this->getPageById($pageId);

        if (!$pageElement) {
            return false;
        }

        return $this->deletePage($pageElement);
    }
    public function deletePage(PageElement $pageElement): bool
    {
        return Craft::$app->getElements()->deleteElement($pageElement, true);
    }

    public function savePage(
        PageElement $pageElement,
        bool $runValidation = true
    ): bool
    {
        return Craft::$app->getElements()->saveElement(
            $pageElement,
            $runValidation
        );
    }

    public function getPageCount(): int
    {
        return PageElement::find()
            ->status(null)
            ->count();
    }

    public function updateIssueCount(int $pageId): void
    {
        $pageElement = PageElement::find()
            ->id($pageId)
            ->one();

        $wcagAIssues = (new Query())
            ->from('osim_focus_issues')
            ->where([
                'pageId' => $pageId,
                'wcag' => true,
                'wcagLevel' => 'A',
                'resolved' => false,
            ])
            ->count();

        $wcagAaIssues = (new Query())
            ->from('osim_focus_issues')
            ->where([
                'pageId' => $pageId,
                'wcag' => true,
                'wcagLevel' => 'AA',
                'resolved' => false,
            ])
            ->count();

        $wcagAaaIssues = (new Query())
            ->from('osim_focus_issues')
            ->where([
                'pageId' => $pageId,
                'wcag' => true,
                'wcagLevel' => 'AAA',
                'resolved' => false,
            ])
            ->count();

        $bestPracticeIssues = (new Query())
            ->from('osim_focus_issues')
            ->where([
                'pageId' => $pageId,
                'bestPractice' => true,
                'resolved' => false,
            ])
            ->count();

        $totalIssues = (new Query())
            ->from('osim_focus_issues')
            ->where([
                'pageId' => $pageId,
                'resolved' => false,
            ])
            ->count();

        $pageElement->wcagAIssues = $wcagAIssues;
        $pageElement->wcagAaIssues = $wcagAaIssues;
        $pageElement->wcagAaaIssues = $wcagAaaIssues;
        $pageElement->bestPracticeIssues = $bestPracticeIssues;
        $pageElement->totalIssues = $totalIssues;

        $this->savePage($pageElement);
    }
}
