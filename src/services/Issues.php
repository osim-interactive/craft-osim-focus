<?php
namespace osim\craft\focus\services;

use Craft;
use craft\db\Query;
use osim\craft\focus\records\Issue as IssueRecord;
use osim\craft\focus\records\Page as PageRecord;
use osim\craft\focus\elements\Issue as IssueElement;
use yii\base\Component;

class Issues extends Component
{

    public function getIssueById(int $issueId): ?IssueElement
    {
        return IssueElement::find()
            ->id($issueId)
            ->one();
    }
    public function getIssueByRule(
        int $pageId,
        int $viewportId,
        int $ruleId,
        string $xpath
    ): ?IssueElement {
        return IssueElement::find()
            ->pageId($pageId)
            ->viewportId($viewportId)
            ->ruleId($ruleId)
            ->xpath($xpath)
            ->one();
    }

    public function deleteIssueById(int $issueId): bool
    {
        $issueElement = $this->getIssueById($issueId);

        if (!$issueElement) {
            return false;
        }

        return $this->deleteIssue($issueElement);
    }
    public function deleteIssue(IssueElement $issueElement): bool
    {
        return Craft::$app->getElements()->deleteElement($issueElement, true);
    }
    public function resolveIssuesByPageId(int $pageId): void
    {
        Craft::$app->db->createCommand()
            ->update(
                IssueRecord::TABLE,
                ['resolved' => true],
                ['pageId' => $pageId]
            )
            ->execute();
    }
    public function deleteIssuesByPageId(int $pageId): void
    {
        $query = (new Query())
            ->select([
                'id',
            ])
            ->from([IssueRecord::TABLE])
            ->where(['pageId' => $pageId]);

        foreach ($query->all() as $row) {
            $this->deleteIssueById($row['id']);
        }
    }

    public function saveIssue(
        IssueElement $issueElement,
        bool $runValidation = true
    ): bool
    {
        return Craft::$app->getElements()->saveElement(
            $issueElement,
            $runValidation
        );
    }

    public function getIssueCount(
        ?bool $resolved = null,
        ?string $type = null
    ): int
    {
        $query = IssueElement::find();

        if ($resolved === true) {
            $query->status(IssueElement::STATUS_RESOLVED);
        } elseif ($resolved === false) {
            $query->status(IssueElement::STATUS_UNRESOLVED);
        } else {
            $query->status(null);
        }

        if ($type === 'A') {
            $query->wcag(true)->wcagLevel('A');
        } elseif ($type === 'AA') {
            $query->wcag(true)->wcagLevel('AA');
        } elseif ($type === 'AAA') {
            $query->wcag(true)->wcagLevel('AAA');
        } elseif ($type === 'BP') {
            $query->bestPractice(true);
        }

        return $query->count();
    }
}
