<?php
namespace osim\craft\focus\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use osim\craft\focus\elements\Issue as IssueElement;
use osim\craft\focus\records\Page as PageRecord;
use osim\craft\focus\records\Viewport as ViewportRecord;

class IssueQuery extends ElementQuery
{
    public ?int $pageId = null;
    public ?int $projectId = null;
    public ?int $viewportId = null;
    public ?int $certainty = null;
    public ?int $priority = null;
    public ?int $ruleId = null;
    public ?int $ruleName = null;
    public ?string $xpath = null;
    public ?string $selector = null;
    public ?bool $wcag = null;
    public ?string $wcagLevel = null;
    public ?bool $bestPractice = null;

    public function pageId(?int $value): static
    {
        $this->pageId = $value;
        return $this;
    }

    public function projectId(?int $value): static
    {
        $this->projectId = $value;
        return $this;
    }

    public function viewportId(?int $value): static
    {
        $this->viewportId = $value;
        return $this;
    }

    public function certainty(?int $value): static
    {
        $this->certainty = $value;
        return $this;
    }

    public function priority(?int $value): static
    {
        $this->priority = $value;
        return $this;
    }

    public function ruleId(?int $value): static
    {
        $this->ruleId = $value;
        return $this;
    }

    public function ruleName(?int $value): static
    {
        $this->ruleName = $value;
        return $this;
    }

    public function xpath(?string $value): static
    {
        $this->xpath = $value;
        return $this;
    }

    public function selector(?string $value): static
    {
        $this->selector = $value;
        return $this;
    }

    public function wcag(?bool $value): static
    {
        $this->wcag = $value;
        return $this;
    }

    public function wcagLevel(?string $value): static
    {
        $this->wcagLevel = $value;
        return $this;
    }

    public function bestPractice(?bool $value): static
    {
        $this->bestPractice = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('osim_focus_issues');

        $this->query->select([
            'osim_focus_pages.projectId',

            'osim_focus_issues.pageId',
            'osim_focus_pages.pageTitle',
            'osim_focus_pages.pageUrl',

            'osim_focus_issues.viewportId',
            'osim_focus_viewports.name AS viewportName',
            'osim_focus_viewports.width AS viewportWidth',
            'osim_focus_viewports.height AS viewportHeight',

            'osim_focus_issues.certainty',
            'osim_focus_issues.priority',
            'osim_focus_issues.ruleId',
            'osim_focus_issues.ruleName',
            'osim_focus_issues.ruleDescription',
            'osim_focus_issues.snippet',
            'osim_focus_issues.xpath',
            'osim_focus_issues.selector',
            'osim_focus_issues.wcag',
            'osim_focus_issues.wcagLevel',
            'osim_focus_issues.bestPractice',
            'osim_focus_issues.summary',
            'osim_focus_issues.resolved',
        ]);

        // Page
        $this->query
            ->innerJoin(
                ['osim_focus_pages' => PageRecord::TABLE],
                '[[osim_focus_pages.id]] = [[osim_focus_issues.pageId]]'
            );
        $this->subQuery
            ->innerJoin(
                ['osim_focus_pages' => PageRecord::TABLE],
                '[[osim_focus_pages.id]] = [[osim_focus_issues.pageId]]'
            );

        // Viewport
        $this->query
            ->innerJoin(
                ['osim_focus_viewports' => ViewportRecord::TABLE],
                '[[osim_focus_viewports.id]] = [[osim_focus_issues.viewportId]]'
            );
        $this->subQuery
            ->innerJoin(
                ['osim_focus_viewports' => ViewportRecord::TABLE],
                '[[osim_focus_viewports.id]] = [[osim_focus_issues.viewportId]]'
            );

        if ($this->pageId) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.pageId', $this->pageId));
        }

        if ($this->projectId) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_pages.projectId', $this->projectId));
        }

        if ($this->viewportId) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.viewportId', $this->viewportId));
        }

        if ($this->certainty) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.certainty', $this->certainty));
        }

        if ($this->priority) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.priority', $this->priority));
        }

        if ($this->ruleId) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.ruleId', $this->ruleId));
        }

        if ($this->ruleName) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.ruleName', $this->ruleName));
        }

        if ($this->xpath) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.xpath', $this->xpath));
        }

        if ($this->selector) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.selector', $this->selector));
        }

        if ($this->wcag) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.wcag', $this->wcag));
        }

        if ($this->wcagLevel) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.wcagLevel', $this->wcagLevel));
        }

        if ($this->bestPractice) {
            $this->subQuery->andWhere(Db::parseParam('osim_focus_issues.bestPractice', $this->bestPractice));
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            IssueElement::STATUS_RESOLVED => [
                'osim_focus_issues.resolved' => true
            ],
            IssueElement::STATUS_UNRESOLVED => [
                'osim_focus_issues.resolved' => false
            ],
            default => parent::statusCondition($status),
        };
    }
}
