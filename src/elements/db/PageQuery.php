<?php
namespace osim\craft\focus\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class PageQuery extends ElementQuery
{
    public ?int $projectId = null;
    public ?string $pageUrl = null;

    public function projectId(?int $value): static
    {
        $this->projectId = $value;
        return $this;
    }

    public function pageUrl(?string $value): static
    {
        $this->pageUrl = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('osim_focus_pages');

        $this->query->select([
            'osim_focus_pages.projectId',
            'osim_focus_pages.pageTitle',
            'osim_focus_pages.pageUrl',
            'osim_focus_pages.wcagAIssues',
            'osim_focus_pages.wcagAaIssues',
            'osim_focus_pages.wcagAaaIssues',
            'osim_focus_pages.bestPracticeIssues',
            'osim_focus_pages.totalIssues',
        ]);

        if ($this->projectId) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_pages.projectId', $this->projectId));
        }

        if ($this->pageUrl) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_pages.pageUrl', $this->pageUrl));
        }

        return parent::beforePrepare();
    }
}
